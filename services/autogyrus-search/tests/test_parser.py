import pytest
from pydantic import ValidationError

from autogyrus_search.models import HardFilters, QueryRequest, SearchIntent


@pytest.mark.parametrize("query,field,expected", [
    ("SUV under $40,000", "max_price", 40000),
    ("Toyota RAV4 below 80,000 km", "max_mileage_km", 80000),
    ("2022 or newer AWD vehicle", "min_year", 2022),
    ("Vehicle near Edmonton within 50 km", "radius_km", 50),
    ("Seven-seater for a large family", "min_seating", 7),
    ("Car for a family of four", "min_seating", 4),
])
def test_numeric_and_family(parser, query, field, expected):
    intent = parser.parse(QueryRequest(query=query))
    assert getattr(intent.hard_filters, field) == expected


def test_family_profile_is_more_than_seating(parser):
    intent = parser.parse(QueryRequest(query="Give me a car which is good for a family of four."))
    assert intent.hard_filters.min_seating == 4
    assert intent.soft_preferences.preferred_seating == 5
    assert intent.soft_preferences.family_suitability
    assert "family" in intent.intent_profiles
    assert intent.hard_filters.body_style is None


def test_entities_and_features(parser):
    intent = parser.parse(QueryRequest(query="Hybrid Toyota RAV4 SUV with heated seats and blind-spot monitoring"))
    assert intent.hard_filters.make == "Toyota"
    assert intent.hard_filters.model == "RAV4"
    assert intent.hard_filters.body_style == "SUV"
    assert intent.hard_filters.fuel_type == "Hybrid"
    assert "heated seats" in intent.hard_filters.required_features
    assert "blind spot monitoring" in intent.hard_filters.required_features


def test_typos_and_unknown_terms(parser):
    assert parser.parse(QueryRequest(query="Toyotta RAV4" )).hard_filters.make == "Toyota"
    intent = parser.parse(QueryRequest(query="quasar-powered spaceship"))
    assert "quasar" in [term.lower() for term in intent.unresolved_terms]
    assert intent.hard_filters.make is None


def test_negative_constraints(parser):
    intent = parser.parse(QueryRequest(query="SUV without diesel and no Ford"))
    assert intent.hard_filters.body_style == "SUV"
    assert "Diesel" in intent.excluded_values.fuel_types
    assert "Ford" in intent.excluded_values.makes


def test_intent_profiles(parser):
    assert parser.parse(QueryRequest(query="Reliable vehicle for Alberta winters")).soft_preferences.winter_suitability
    assert parser.parse(QueryRequest(query="Fuel-efficient car for commuting")).soft_preferences.fuel_efficiency
    assert parser.parse(QueryRequest(query="Cheap to maintain first car")).soft_preferences.maintenance_cost


def test_validation_rejects_unknown_and_conflicting_fields():
    with pytest.raises(ValidationError):
        SearchIntent.model_validate({"hard_filters": {"arbitrary_sql": "DROP TABLE ag_vehicle_master"}})
    with pytest.raises(ValidationError):
        HardFilters(min_price=50000, max_price=20000)
    with pytest.raises(ValidationError):
        QueryRequest(query="x" * 501)


def test_no_mileage_as_price(parser):
    intent = parser.parse(QueryRequest(query="RAV4 below 80,000 km"))
    assert intent.hard_filters.max_mileage_km == 80000
    assert intent.hard_filters.max_price is None
