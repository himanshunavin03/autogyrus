"""Configurable business reranking with explicit filter rechecks and factual explanations."""
import math
import re
from datetime import UTC, datetime
from typing import Any

from .models import SearchHit, SearchIntent


def _same(left: Any, right: Any) -> bool:
    return str(left or "").casefold() == str(right or "").casefold()


def _distance_km(a: list[float], b: list[float]) -> float:
    lat1, lon1, lat2, lon2 = map(math.radians, [a[0], a[1], b[0], b[1]])
    delta = math.sin((lat2 - lat1) / 2) ** 2 + math.cos(lat1) * math.cos(lat2) * math.sin((lon2 - lon1) / 2) ** 2
    return 6371.0088 * 2 * math.asin(math.sqrt(delta))


def satisfies_hard_filters(payload: dict[str, Any], intent: SearchIntent,
                           city_coordinates: dict[str, list[float]]) -> bool:
    h = intent.hard_filters
    if str(payload.get("availability") or "").lower() not in ("in stock", "available", "published", "active"):
        return False
    for key in ("make", "model", "trim", "body_style", "fuel_type", "drivetrain", "transmission", "province"):
        value = getattr(h, key)
        payload_key = "body_style_group" if key == "body_style" else key
        if value and not _same(payload.get(payload_key), value):
            return False
    if h.city and h.radius_km is None and not _same(payload.get("city"), h.city):
        return False
    if h.postal_code and h.radius_km is None and not _same(payload.get("postal_code"), h.postal_code):
        return False
    if h.radius_km is not None:
        origin = city_coordinates.get((h.city or "").lower())
        if not origin or payload.get("latitude") is None or payload.get("longitude") is None:
            return False
        if _distance_km(origin, [float(payload["latitude"]), float(payload["longitude"])]) > h.radius_km:
            return False
    for key, lower, upper in (("price", h.min_price, h.max_price), ("year", h.min_year, h.max_year),
                              ("mileage_km", None, h.max_mileage_km), ("seating_capacity", h.min_seating, None)):
        if lower is not None or upper is not None:
            value = payload.get(key)
            if value is None or (lower is not None and float(value) < lower) or (upper is not None and float(value) > upper):
                return False
    features = [str(f).casefold() for f in payload.get("features") or []]
    if any(not any(needle.casefold() in feature for feature in features) for needle in h.required_features):
        return False
    for key, values in (("make", intent.excluded_values.makes),
                        ("body_style_group", intent.excluded_values.body_styles),
                        ("fuel_type", intent.excluded_values.fuel_types)):
        if any(_same(payload.get(key), value) for value in values):
            return False
    return not any(any(value.casefold() in feature for feature in features)
                   for value in intent.excluded_values.features)


def rank_candidate(payload: dict[str, Any], semantic_score: float, query: str,
                   intent: SearchIntent, ontology: dict[str, Any]) -> SearchHit:
    weights = ontology["ranking_weights"]
    terms = {t for t in re.findall(r"[a-z0-9]+", query.casefold()) if len(t) > 2}
    document_terms = set(re.findall(r"[a-z0-9]+", str(payload.get("searchable_text") or "").casefold()))
    keyword = len(terms & document_terms) / max(1, len(terms))
    exact = sum(1 for key in ("make", "model", "trim") if getattr(intent.hard_filters, key) and
                _same(payload.get(key), getattr(intent.hard_filters, key))) / 3
    features = [str(f).casefold() for f in payload.get("features") or []]
    preferred = intent.soft_preferences.preferred_features
    feature_score = sum(any(want.casefold() in feature for feature in features) for want in preferred) / max(1, len(preferred))
    reasons: list[str] = []
    h = intent.hard_filters
    if h.min_seating and payload.get("seating_capacity") is not None:
        reasons.append(f"Seats {int(payload['seating_capacity'])} people")
    if h.max_price is not None:
        reasons.append("Within the requested price")
    if h.drivetrain and payload.get("drivetrain"):
        reasons.append(f"Includes {payload['drivetrain']}")
    if h.required_features:
        reasons.append("Includes requested features")
    if h.radius_km and payload.get("city"):
        reasons.append(f"Available near {h.city}")
    profile = 0.0
    soft = intent.soft_preferences
    if soft.family_suitability:
        cfg = ontology["family_profile"]
        if payload.get("seating_capacity") is not None and soft.preferred_seating and payload["seating_capacity"] >= soft.preferred_seating:
            profile += cfg["preferred_seating_weight"]
        if payload.get("body_style_group") in cfg["preferred_body_styles"]:
            profile += 0.6
        if _same(payload.get("body_style_group"), "Coupe"):
            profile -= cfg["coupe_penalty"]
        if any(any(term in f for term in cfg["safety_feature_terms"]) for f in features):
            profile += cfg["safety_weight"]
        if any(any(term in f for term in cfg["rear_access_feature_terms"]) for f in features):
            profile += cfg["rear_access_weight"]
        if payload.get("cargo_capacity_l") is not None and payload["cargo_capacity_l"] >= 400:
            profile += cfg["cargo_weight"]
        if profile > 0:
            reasons.append("Matches your family requirement")
    if soft.winter_suitability:
        if payload.get("drivetrain") in ontology["winter_profile"]["preferred_drivetrains"]:
            profile += 1.0
            reasons.append("AWD or 4WD helps with winter driving")
        if any(any(term in f for term in ontology["winter_profile"]["feature_terms"]) for f in features):
            profile += 0.5
    if soft.fuel_efficiency and payload.get("fuel_consumption_l_per_100km") is not None:
        profile += max(0, min(1, (12 - payload["fuel_consumption_l_per_100km"]) / 6))
        reasons.append("Fuel consumption data supports commuting comparison")
    if soft.towing and payload.get("towing_capacity_kg") is not None:
        profile += min(1.0, payload["towing_capacity_kg"] / 2500)
        reasons.append("Towing capacity is documented")
    if soft.cargo_space and payload.get("cargo_capacity_l") is not None:
        profile += min(1.0, payload["cargo_capacity_l"] / 1000)
    profile = max(-1.0, min(1.0, profile / 3))
    price_value = 1 / (1 + float(payload["price"]) / 40000) if payload.get("price") is not None else 0
    mileage = 1 / (1 + float(payload["mileage_km"]) / 100000) if payload.get("mileage_km") is not None else 0
    freshness = 0.0
    if payload.get("source_updated_at"):
        try:
            stamp = datetime.fromisoformat(str(payload["source_updated_at"])).replace(tzinfo=UTC)
            freshness = max(0.0, 1 - (datetime.now(UTC) - stamp).days / 365)
        except ValueError:
            pass
    score = (weights["semantic"] * max(0, min(1, semantic_score)) + weights["keyword"] * keyword +
             weights["exact_entity"] * exact + weights["features"] * feature_score +
             weights["intent_profile"] * profile + weights["price_value"] * price_value +
             weights["mileage"] * mileage + weights["freshness"] * freshness)
    # Scores absent from ag_* are not fabricated or used as evidence.
    if soft.reliability and payload.get("reliability_score") is not None:
        score += weights["reliability"] * float(payload["reliability_score"]) / 100
        if payload["reliability_score"] >= 80:
            reasons.append("High reliability score")
    if soft.winter_suitability and payload.get("winter_score") is not None:
        score += weights["winter"] * float(payload["winter_score"]) / 100
    if soft.future_value and payload.get("future_value_score") is not None:
        score += weights["future_value"] * float(payload["future_value_score"]) / 100
    return SearchHit(vehicle_id=int(payload["vehicle_id"]), score=round(score, 6), reasons=reasons[:5])
