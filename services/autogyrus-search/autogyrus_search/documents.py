"""Stable, concise index documents and filterable Qdrant payloads."""
import hashlib
import json
from dataclasses import dataclass
from datetime import date, datetime
from typing import Any

from .parser import load_ontology

DOCUMENT_VERSION = "1"


@dataclass(frozen=True)
class SearchDocument:
    vehicle_id: int
    text: str
    payload: dict[str, Any]
    document_hash: str


def _number(value: Any) -> float | None:
    return float(value) if value is not None and value != "" else None


def _date(value: Any) -> str | None:
    if isinstance(value, (date, datetime)):
        return value.isoformat()
    return str(value) if value else None


def build_document(row: dict[str, Any]) -> SearchDocument:
    feature_set = {str(f).strip() for f in row.get("features", []) if str(f).strip()}
    for name in list(feature_set):
        lower = name.casefold()
        if "heated" in lower and "seat" in lower:
            feature_set.add("heated seats")
        if "blind spot" in lower:
            feature_set.add("blind spot monitoring")
        if "child seat" in lower or "isofix" in lower or "latch anchor" in lower:
            feature_set.add("child seat anchors")
        if "backup camera" in lower or "rear view camera" in lower:
            feature_set.add("backup camera")
    features = sorted(feature_set, key=str.lower)
    body_style_group = load_ontology()["body_style_groups"].get(str(row.get("body_style") or "").casefold())
    family_score = None
    if row.get("seating_capacity") is not None:
        cfg = load_ontology()["family_profile"]
        lower_features = [name.casefold() for name in features]
        points = min(35, int(row["seating_capacity"]) * 7)
        points += 15 if body_style_group in cfg["preferred_body_styles"] else 0
        points += 15 if any(any(term in name for term in cfg["safety_feature_terms"])
                            for name in lower_features) else 0
        points += 10 if any(any(term in name for term in cfg["rear_access_feature_terms"])
                            for name in lower_features) else 0
        points += 10 if row.get("cargo_capacity_l") is not None and float(row["cargo_capacity_l"]) >= 400 else 0
        points -= 20 if body_style_group == "Coupe" else 0
        family_score = max(0, min(100, points))
    title = " ".join(str(row.get(key) or "").strip() for key in ("year", "make", "model", "trim")).strip()
    details = [title]
    for key, label in (("body_style", "body style"), ("fuel_type", "fuel"),
                       ("drivetrain", "drivetrain"), ("transmission", "transmission")):
        if row.get(key):
            details.append(f"{label}: {row[key]}")
    if row.get("seating_capacity") is not None:
        details.append(f"seats {row['seating_capacity']} people")
    if row.get("cargo_capacity_l") is not None:
        details.append(f"cargo {row['cargo_capacity_l']} litres")
    if row.get("towing_capacity_kg") is not None:
        details.append(f"towing {row['towing_capacity_kg']} kg")
    if features:
        details.append("features: " + ", ".join(features[:60]))
    if row.get("city") or row.get("province"):
        details.append("location: " + ", ".join(x for x in (row.get("city"), row.get("province")) if x))
    text = ". ".join(details)
    timestamps = [str(_date(row.get(key))) for key in ("vehicle_updated_at", "inventory_updated_at",
                   "price_updated_at", "specs_updated_at", "location_updated_at") if row.get(key)]
    payload: dict[str, Any] = {
        "vehicle_id": int(row["vehicle_id"]), "document_version": DOCUMENT_VERSION,
        "source_updated_at": max(timestamps) if timestamps else None,
        "searchable_text": text,
        "make": row.get("make"), "model": row.get("model"), "trim": row.get("trim"),
        "year": int(row["year"]) if row.get("year") is not None else None,
        "price": _number(row.get("price")), "mileage_km": _number(row.get("mileage_km")),
        "body_style": row.get("body_style"), "body_style_group": body_style_group,
        "fuel_type": row.get("fuel_type"),
        "drivetrain": row.get("drivetrain"), "transmission": row.get("transmission"),
        "seating_capacity": row.get("seating_capacity"), "province": row.get("province"),
        "family_score": family_score,
        "city": row.get("city"), "postal_code": row.get("postal_code"),
        "dealer_id": int(row["dealer_id"]) if row.get("dealer_id") is not None else None,
        "availability": row.get("availability"), "features": features,
        "cargo_capacity_l": _number(row.get("cargo_capacity_l")),
        "towing_capacity_kg": _number(row.get("towing_capacity_kg")),
        "fuel_consumption_l_per_100km": _number(row.get("fuel_consumption_l_per_100km")),
        "safety_rating": row.get("safety_rating"),
        "latitude": _number(row.get("latitude")), "longitude": _number(row.get("longitude")),
        # No canonical ag_* scores currently exist. Derived profile scoring happens at query time.
        "reliability_score": None, "winter_score": None, "future_value_score": None,
    }
    if payload["latitude"] is not None and payload["longitude"] is not None:
        payload["location"] = {"lat": payload["latitude"], "lon": payload["longitude"]}
    hash_input = {"text": text, "payload": {k: v for k, v in payload.items() if k != "source_updated_at"}}
    encoded = json.dumps(hash_input, sort_keys=True, separators=(",", ":"), default=str).encode()
    digest = hashlib.sha256(encoded).hexdigest()
    payload["document_hash"] = digest
    return SearchDocument(int(row["vehicle_id"]), text, payload, digest)
