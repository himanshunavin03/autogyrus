import pytest
from qdrant_client import QdrantClient

from autogyrus_search.documents import build_document
from autogyrus_search.models import HardFilters, SearchIntent
from autogyrus_search.parser import load_ontology
from autogyrus_search.vector import VectorIndex, build_filter


def test_qdrant_in_memory_index_and_filter():
    client = QdrantClient(":memory:")
    index = VectorIndex(client, "vehicles", 2)
    index.ensure_collection()
    with pytest.raises(ValueError, match="dimensions"):
        VectorIndex(client, "vehicles", 3).ensure_collection()
    row = {"vehicle_id": 7, "year": 2024, "make": "Toyota", "model": "RAV4",
           "availability": "in stock", "price": 35000, "mileage_km": 20000,
           "seating_capacity": 5, "features": ["Heated driver seats"],
           "city": "Edmonton", "latitude": 53.5461, "longitude": -113.4938}
    doc = build_document(row)
    index.upsert(doc, [1, 0])
    assert index.existing_hash(7) == doc.document_hash
    intent = SearchIntent(hard_filters=HardFilters(max_price=40000, min_seating=4))
    result = index.search([1, 0], "Toyota RAV4", intent, 10, load_ontology()["city_coordinates"])
    assert len(result) == 1
    blocked = SearchIntent(hard_filters=HardFilters(max_price=30000))
    assert index.search([1, 0], "Toyota RAV4", blocked, 10, load_ontology()["city_coordinates"]) == []
    assert list(index.iter_ids()) == [7]
    index.delete(7)
    assert index.existing_hash(7) is None
    client.close()


def test_filter_contains_mandatory_constraints():
    filt = build_filter(SearchIntent(hard_filters=HardFilters(make="Toyota", min_seating=4)),
                        load_ontology()["city_coordinates"])
    keys = {condition.key for condition in filt.must}
    assert {"availability", "make", "seating_capacity"} <= keys
