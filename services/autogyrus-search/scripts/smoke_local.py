"""Read-only MySQL + real CPU model + ephemeral Qdrant smoke; never changes MySQL."""
import json
import os

from qdrant_client import QdrantClient
from sentence_transformers import SentenceTransformer

from autogyrus_search.models import QueryRequest
from autogyrus_search.parser import IntentParser, load_ontology
from autogyrus_search.repository import VehicleRepository
from autogyrus_search.service import SearchService
from autogyrus_search.vector import VectorIndex


def main() -> None:
    dsn = os.environ["MYSQL_DSN"]
    repository = VehicleRepository.from_dsn(dsn)
    try:
        ontology = load_ontology()
        model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2", device="cpu")
        client = QdrantClient(":memory:")
        index = VectorIndex(client, "smoke_vehicles", int(model.get_embedding_dimension()))
        index.ensure_collection()
        service = SearchService(repository, IntentParser(repository.lexicon(), ontology), model, index, ontology)
        counts = service.rebuild()
        response = service.search(QueryRequest(query="Toyota SUV under $50,000"), "local-smoke")
        print(json.dumps({"index": counts, "hits": len(response.hits),
                          "candidate_count": response.total_candidates,
                          "query_embedding_dimension": int(model.get_embedding_dimension())}))
        client.close()
    finally:
        repository.close()


if __name__ == "__main__":
    main()
