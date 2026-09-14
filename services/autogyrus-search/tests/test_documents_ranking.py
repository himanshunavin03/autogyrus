from datetime import UTC, datetime

from autogyrus_search.documents import build_document
from autogyrus_search.models import HardFilters, SearchIntent, SoftPreferences
from autogyrus_search.parser import load_ontology
from autogyrus_search.ranking import rank_candidate, satisfies_hard_filters


def vehicle(**overrides):
    row = {
        "vehicle_id": 42, "year": 2023, "make": "Toyota", "model": "RAV4", "trim": "XLE",
        "body_style": "SUV", "fuel_type": "Hybrid", "drivetrain": "AWD", "transmission": "Automatic",
        "availability": "in stock", "mileage_km": 45000, "price": 35000,
        "seating_capacity": 5, "cargo_capacity_l": 650, "towing_capacity_kg": 1500,
        "city": "Edmonton", "province": "AB", "dealer_id": 2,
        "latitude": 53.54, "longitude": -113.49,
        "features": ["Heated driver and front passenger seats", "Blind spot monitoring", "Rear seat access"],
        "vehicle_updated_at": datetime(2026, 1, 1, tzinfo=UTC),
    }
    row.update(overrides)
    return row


def test_document_is_deterministic_and_avoids_raw_json():
    row = vehicle(raw={"private": "not-indexed"})
    first = build_document(row)
    assert first.vehicle_id == 42
    assert "heated seats" in first.payload["features"]
    assert "private" not in first.text
    assert first.document_hash == build_document(row).document_hash
    assert first.document_hash != build_document(vehicle(price=36000)).document_hash
    assert first.payload["reliability_score"] is None


def test_hard_filters_are_never_overridden_by_semantic_score():
    payload = build_document(vehicle()).payload
    intent = SearchIntent(hard_filters=HardFilters(max_price=30000))
    assert not satisfies_hard_filters(payload, intent, load_ontology())
    intent = SearchIntent(hard_filters=HardFilters(min_seating=4, drivetrain="AWD"))
    assert satisfies_hard_filters(payload, intent, load_ontology()["city_coordinates"])
    assert not satisfies_hard_filters(build_document(vehicle(seating_capacity=None)).payload,
                                     intent, load_ontology()["city_coordinates"])


def test_family_ranking_and_reasons():
    ontology = load_ontology()
    intent = SearchIntent(hard_filters=HardFilters(min_seating=4),
                          soft_preferences=SoftPreferences(family_suitability=True, preferred_seating=5))
    family = rank_candidate(build_document(vehicle()).payload, 0.5, "family of four", intent, ontology)
    coupe = rank_candidate(build_document(vehicle(body_style="Coupe", features=[], cargo_capacity_l=100)).payload,
                           0.5, "family of four", intent, ontology)
    assert family.score > coupe.score
    assert "Seats 5 people" in family.reasons
    assert "Matches your family requirement" in family.reasons


def test_negative_and_radius_filters():
    payload = build_document(vehicle()).payload
    coords = load_ontology()["city_coordinates"]
    assert satisfies_hard_filters(payload, SearchIntent(hard_filters=HardFilters(city="Edmonton", radius_km=50)), coords)
    assert not satisfies_hard_filters(payload, SearchIntent(hard_filters=HardFilters(city="Calgary", radius_km=50)), coords)
