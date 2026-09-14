"""Search orchestration and idempotent indexing, independent of WordPress."""
import logging
import time
from typing import Any

from qdrant_client import QdrantClient
from sentence_transformers import SentenceTransformer

from .config import Settings
from .documents import SearchDocument, build_document
from .models import QueryRequest, SearchIntent, SearchResponse, Timing
from .parser import IntentParser, load_ontology
from .ranking import rank_candidate, satisfies_hard_filters
from .repository import VehicleRepository
from .vector import VectorIndex

logger = logging.getLogger(__name__)


class SearchService:
    def __init__(self, repository: VehicleRepository, parser: IntentParser,
                 embedder: Any, index: VectorIndex, ontology: dict[str, Any]):
        self.repository, self.parser, self.embedder, self.index, self.ontology = (
            repository, parser, embedder, index, ontology
        )
        self._parse_cache: dict[tuple[str, int, int, str], SearchIntent] = {}

    @classmethod
    def from_settings(cls, settings: Settings) -> "SearchService":
        repository = VehicleRepository.from_dsn(settings.mysql_dsn)
        lexicon = repository.lexicon()
        ontology = load_ontology()
        parser = IntentParser(lexicon, ontology)
        embedder = SentenceTransformer(settings.embedding_model, device="cpu")
        dimensions = int(embedder.get_sentence_embedding_dimension())
        client = QdrantClient(url=settings.qdrant_url, api_key=settings.qdrant_api_key or None, timeout=10)
        index = VectorIndex(client, settings.qdrant_collection, dimensions)
        index.ensure_collection()
        return cls(repository, parser, embedder, index, ontology)

    def parse_cached(self, query: str, page: int, page_size: int, sort_order: str) -> SearchIntent:
        key = (query, page, page_size, sort_order)
        if key not in self._parse_cache:
            if len(self._parse_cache) >= 2048:
                self._parse_cache.pop(next(iter(self._parse_cache)))
            self._parse_cache[key] = self.parser.parse(QueryRequest(query=query, page=page, page_size=page_size,
                                                                     sort_order=sort_order))
        return self._parse_cache[key]

    def parse(self, request: QueryRequest) -> SearchIntent:
        return self.parse_cached(request.query, request.page, request.page_size,
                                 request.sort_order).model_copy(deep=True)

    def ready(self) -> None:
        self.repository.ping()
        self.index.ping()

    def close(self) -> None:
        self.repository.close()
        self.index.client.close()

    def search(self, request: QueryRequest, request_id: str) -> SearchResponse:
        started = time.perf_counter()
        intent = self.parse(request)
        parsed = time.perf_counter()
        vector = self.embedder.encode(request.query, normalize_embeddings=True).tolist()
        embedded = time.perf_counter()
        limit = min(500, max(100, request.page * request.page_size * 4))
        candidates = self.index.search(vector, request.query, intent, limit,
                                       self.ontology["city_coordinates"])
        retrieved = time.perf_counter()
        hits = [rank_candidate(payload, similarity, request.query, intent, self.ontology)
                for payload, similarity in candidates
                if satisfies_hard_filters(payload, intent, self.ontology["city_coordinates"])]
        if intent.sort_order == "relevance":
            hits.sort(key=lambda hit: (-hit.score, hit.vehicle_id))
        else:
            field, reverse = {"price_asc": ("price", False), "price_desc": ("price", True),
                              "year_desc": ("year", True), "mileage_asc": ("mileage_km", False)}[intent.sort_order]
            payload_by_id = {int(p["vehicle_id"]): p for p, _ in candidates}
            hits.sort(key=lambda hit: (payload_by_id[hit.vehicle_id].get(field) is None,
                                       -(payload_by_id[hit.vehicle_id].get(field) or 0) if reverse else
                                       (payload_by_id[hit.vehicle_id].get(field) or 0), hit.vehicle_id))
        ranked = time.perf_counter()
        offset = (request.page - 1) * request.page_size
        return SearchResponse(
            intent=intent, hits=hits[offset:offset + request.page_size], total_candidates=len(hits),
            timing=Timing(parse_ms=(parsed - started) * 1000, embedding_ms=(embedded - parsed) * 1000,
                          retrieval_ms=(retrieved - embedded) * 1000, rerank_ms=(ranked - retrieved) * 1000,
                          total_ms=(ranked - started) * 1000), request_id=request_id,
        )

    def index_vehicle(self, vehicle_id: int) -> str:
        row = self.repository.get_vehicle(vehicle_id)
        if row is None:
            self.index.delete(vehicle_id)
            return "deleted"
        document = build_document(row)
        if self.index.existing_hash(vehicle_id) == document.document_hash:
            return "skipped"
        vector = self.embedder.encode(document.text, normalize_embeddings=True).tolist()
        self.index.upsert(document, vector)
        return "indexed"

    def index_vehicles(self, vehicle_ids: list[int]) -> dict[str, int]:
        stats = {"indexed": 0, "skipped": 0, "deleted": 0, "failed": 0}
        pending: list[SearchDocument] = []
        for vehicle_id in dict.fromkeys(vehicle_ids):
            try:
                row = self.repository.get_vehicle(vehicle_id)
                if row is None:
                    self.index.delete(vehicle_id)
                    stats["deleted"] += 1
                    continue
                document = build_document(row)
                if self.index.existing_hash(vehicle_id) == document.document_hash:
                    stats["skipped"] += 1
                    continue
                pending.append(document)
            except Exception:
                logger.exception("index projection failed", extra={"vehicle_id": vehicle_id})
                stats["failed"] += 1
        for start in range(0, len(pending), 64):
            batch = pending[start:start + 64]
            try:
                vectors = self.embedder.encode([item.text for item in batch], batch_size=32,
                                               normalize_embeddings=True).tolist()
                self.index.upsert_batch(batch, vectors)
                stats["indexed"] += len(batch)
            except Exception:
                logger.exception("index upsert batch failed", extra={"batch_size": len(batch)})
                stats["failed"] += len(batch)
        return stats

    def rebuild(self, batch_size: int = 200) -> dict[str, int]:
        stats = {"indexed": 0, "skipped": 0, "deleted": 0, "failed": 0}
        canonical_ids: set[int] = set()
        for ids in self.repository.iter_vehicle_ids(batch_size):
            canonical_ids.update(ids)
            result = self.index_vehicles(ids)
            for key, value in result.items():
                stats[key] += value
            logger.info("index rebuild batch", extra={"last_vehicle_id": ids[-1], "stats": result})
        for vehicle_id in list(self.index.iter_ids()):
            if vehicle_id not in canonical_ids:
                self.index.delete(vehicle_id)
                stats["deleted"] += 1
        return stats
