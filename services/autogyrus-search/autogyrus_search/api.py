"""Authenticated HTTP contract for WordPress and internal indexing workers."""
import asyncio
import hmac
import logging
import time
import uuid
from contextlib import asynccontextmanager

from fastapi import BackgroundTasks, Depends, FastAPI, Header, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import ValidationError

from .config import Settings, get_settings
from .models import IndexVehiclesRequest, QueryRequest, SearchResponse
from .service import SearchService

logger = logging.getLogger(__name__)


def create_app(service: SearchService | None = None, settings: Settings | None = None) -> FastAPI:
    settings = settings or get_settings()

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        if service is None:
            app.state.service = SearchService.from_settings(settings)
        else:
            app.state.service = service
        yield
        if service is None:
            app.state.service.close()

    app = FastAPI(title="AutoGyrus Search", version="1.0.0", lifespan=lifespan)
    app.state.settings = settings
    if service is not None:
        app.state.service = service

    @app.middleware("http")
    async def request_context(request: Request, call_next):
        request_id = request.headers.get("X-Request-ID", "")
        if not request_id or len(request_id) > 80 or not all(c.isalnum() or c in "-_." for c in request_id):
            request_id = str(uuid.uuid4())
        request.state.request_id = request_id
        if request.method in ("POST", "PUT", "PATCH"):
            length = request.headers.get("content-length")
            if length and (not length.isdecimal() or int(length) > settings.max_request_bytes):
                return JSONResponse(status_code=413, content={"error": {"code": "request_too_large", "message": "Request body exceeds limit", "request_id": request_id}})
            body = await request.body()
            if len(body) > settings.max_request_bytes:
                return JSONResponse(status_code=413, content={"error": {"code": "request_too_large", "message": "Request body exceeds limit", "request_id": request_id}})
        started = time.perf_counter()
        try:
            response = await call_next(request)
        except Exception:
            logger.exception("unhandled request error", extra={"request_id": request_id, "path": request.url.path})
            response = JSONResponse(status_code=500, content={"error": {"code": "internal_error", "message": "Internal service error", "request_id": request_id}})
        response.headers["X-Request-ID"] = request_id
        response.headers["X-Process-Time-MS"] = str(round((time.perf_counter() - started) * 1000, 2))
        response.headers["Cache-Control"] = "no-store"
        return response

    @app.exception_handler(HTTPException)
    async def http_error(request: Request, exc: HTTPException):
        return JSONResponse(status_code=exc.status_code, content={"error": {
            "code": "http_error", "message": str(exc.detail), "request_id": request.state.request_id}})

    @app.exception_handler(RequestValidationError)
    async def validation_error(request: Request, exc: RequestValidationError):
        fields = [".".join(str(part) for part in error["loc"]) for error in exc.errors()]
        return JSONResponse(status_code=422, content={"error": {
            "code": "validation_error", "message": "Invalid request", "fields": fields,
            "request_id": request.state.request_id}})

    def require_key(scope: str):
        async def verify(x_api_key: str | None = Header(default=None)) -> None:
            expected = settings.admin_api_key if scope == "admin" else settings.public_api_key
            if not expected or not x_api_key or not hmac.compare_digest(expected, x_api_key):
                raise HTTPException(status_code=401, detail="Invalid service credentials")
        return verify

    public = Depends(require_key("public"))
    admin = Depends(require_key("admin"))

    @app.get("/health")
    async def health():
        return {"status": "ok"}

    @app.get("/ready")
    async def ready(request: Request):
        try:
            await asyncio.to_thread(request.app.state.service.ready)
        except Exception:  # noqa: BLE001 - readiness must fail closed for any dependency error
            raise HTTPException(503, "Dependencies unavailable") from None
        return {"status": "ready"}

    @app.post("/api/v1/parse", dependencies=[public])
    async def parse(body: QueryRequest, request: Request):
        try:
            return await asyncio.wait_for(asyncio.to_thread(request.app.state.service.parse, body),
                                          timeout=settings.request_timeout_seconds)
        except (ValueError, ValidationError) as exc:
            raise HTTPException(422, str(exc)) from None
        except TimeoutError:
            raise HTTPException(504, "Search parsing timed out") from None

    @app.post("/api/v1/search", response_model=SearchResponse, dependencies=[public])
    async def search(body: QueryRequest, request: Request):
        try:
            return await asyncio.wait_for(asyncio.to_thread(request.app.state.service.search,
                                                             body, request.state.request_id),
                                          timeout=settings.request_timeout_seconds)
        except (ValueError, ValidationError) as exc:
            raise HTTPException(422, str(exc)) from None
        except TimeoutError:
            raise HTTPException(504, "Search timed out") from None

    @app.post("/api/v1/index/rebuild", status_code=202, dependencies=[admin])
    async def rebuild(request: Request, background_tasks: BackgroundTasks):
        background_tasks.add_task(request.app.state.service.rebuild)
        return {"status": "accepted", "message": "Rebuild started in this process; use CLI for large production rebuilds"}

    @app.post("/api/v1/index/vehicles", dependencies=[admin])
    async def index_vehicles(body: IndexVehiclesRequest, request: Request):
        return await asyncio.to_thread(request.app.state.service.index_vehicles, body.vehicle_ids)

    @app.post("/api/v1/index/vehicle/{vehicle_id}", dependencies=[admin])
    async def index_vehicle(vehicle_id: int, request: Request):
        if vehicle_id <= 0:
            raise HTTPException(422, "vehicle_id must be positive")
        status = await asyncio.to_thread(request.app.state.service.index_vehicle, vehicle_id)
        return {"vehicle_id": vehicle_id, "status": status}

    @app.delete("/api/v1/index/vehicle/{vehicle_id}", dependencies=[admin])
    async def delete_vehicle(vehicle_id: int, request: Request):
        if vehicle_id <= 0:
            raise HTTPException(422, "vehicle_id must be positive")
        await asyncio.to_thread(request.app.state.service.index.delete, vehicle_id)
        return {"vehicle_id": vehicle_id, "status": "deleted"}

    return app


# The factory delays database/model startup until ASGI lifespan begins.
app = create_app()
