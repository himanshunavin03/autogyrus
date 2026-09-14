import os
from types import SimpleNamespace

os.environ.setdefault("APP_ENV", "test")

from fastapi.testclient import TestClient

from autogyrus_search.api import create_app
from autogyrus_search.config import Settings
from autogyrus_search.models import QueryRequest
from autogyrus_search.parser import IntentParser, Lexicon, load_ontology
from autogyrus_search.service import SearchService


class FakeRepository:
    def __init__(self):
        self.row = {"vehicle_id": 1, "year": 2024, "make": "Toyota", "model": "RAV4",
                    "body_style": "SUV", "availability": "in stock", "price": 35000,
                    "mileage_km": 25000, "seating_capacity": 5, "features": []}

    def get_vehicle(self, vehicle_id):
        return self.row if vehicle_id == 1 else None

    def iter_vehicle_ids(self, batch_size):
        yield [1, 2]

    def ping(self):
        pass


class FakeEmbedder:
    def encode(self, text, **kwargs):
        if isinstance(text, list):
            return SimpleNamespace(tolist=lambda: [[1.0, 0.0]] * len(text))
        return SimpleNamespace(tolist=lambda: [1.0, 0.0])


class FakeIndex:
    def __init__(self):
        self.points = {}
        self.client = SimpleNamespace(close=lambda: None)

    def existing_hash(self, vehicle_id):
        return self.points.get(vehicle_id, {}).get("document_hash")

    def upsert(self, doc, vector):
        self.points[doc.vehicle_id] = doc.payload

    def upsert_batch(self, docs, vectors):
        for doc in docs:
            self.points[doc.vehicle_id] = doc.payload

    def delete(self, vehicle_id):
        self.points.pop(vehicle_id, None)

    def iter_ids(self):
        return iter(list(self.points))

    def search(self, vector, query, intent, limit, coords):
        return [(payload, 0.8) for payload in self.points.values()]

    def ping(self):
        pass


def service():
    repo = FakeRepository()
    index = FakeIndex()
    return SearchService(repo, IntentParser(Lexicon(makes=("Toyota",), models=("RAV4",))),
                         FakeEmbedder(), index, load_ontology())


def test_index_idempotency_and_deactivation():
    instance = service()
    assert instance.index_vehicle(1) == "indexed"
    assert instance.index_vehicle(1) == "skipped"
    assert instance.index_vehicle(2) == "deleted"
    instance.repository.row = None
    assert instance.index_vehicle(1) == "deleted"
    assert instance.index.points == {}


def test_search_rechecks_hard_filters():
    instance = service()
    instance.index_vehicle(1)
    assert len(instance.search(QueryRequest(query="SUV under $40,000"), "request").hits) == 1
    assert not instance.search(QueryRequest(query="SUV under $30,000"), "request").hits


def test_api_auth_and_validation():
    instance = service()
    settings = Settings(app_env="test", public_api_key="public-secret", admin_api_key="admin-secret")
    app = create_app(instance, settings)
    with TestClient(app) as client:
        assert client.get("/health").status_code == 200
        assert client.post("/api/v1/parse", json={"query": "SUV"}).status_code == 401
        assert client.post("/api/v1/index/vehicle/1", headers={"X-API-Key": "public-secret"}).status_code == 401
        assert client.post("/api/v1/index/vehicle/1", headers={"X-API-Key": "admin-secret"}).json()["status"] == "indexed"
        result = client.post("/api/v1/search", headers={"X-API-Key": "public-secret"},
                             json={"query": "SUV under $40,000"})
        assert result.status_code == 200
        assert result.json()["hits"][0]["vehicle_id"] == 1
        assert result.headers["X-Request-ID"]
        assert client.post("/api/v1/parse", headers={"X-API-Key": "public-secret"},
                           content=b"x" * 9000).status_code == 413
