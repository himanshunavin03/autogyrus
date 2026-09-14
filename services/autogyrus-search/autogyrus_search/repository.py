"""Read-only projections from actual AutoGyrus ag_* relationships."""
import re
from collections.abc import Iterator
from typing import Any

from sqlalchemy import Engine, create_engine, text

from .parser import Lexicon

BASE_SQL = """
WITH current_inventory AS (
  SELECT i.*, ROW_NUMBER() OVER (PARTITION BY i.vehicle_id ORDER BY i.last_synced_at DESC, i.id DESC) AS rn
  FROM ag_inventory i
  WHERE i.vehicle_id = :vehicle_id AND i.is_current = 1 AND i.is_active = 1 AND i.is_deleted = 0
    AND LOWER(TRIM(i.availability_status)) IN ('in stock','available','published','active')
), current_price AS (
  SELECT p.*, ROW_NUMBER() OVER (PARTITION BY p.vehicle_id ORDER BY p.last_synced_at DESC, p.id DESC) AS rn
  FROM ag_vehicle_price p
  WHERE p.vehicle_id = :vehicle_id AND p.is_current = 1 AND p.is_active = 1 AND p.is_deleted = 0
), current_specs AS (
  SELECT s.*, ROW_NUMBER() OVER (PARTITION BY s.vehicle_id ORDER BY s.last_synced_at DESC, s.id DESC) AS rn
  FROM ag_vehicle_specification s
  WHERE s.vehicle_id = :vehicle_id AND s.is_current = 1 AND s.is_active = 1 AND s.is_deleted = 0
), current_economy AS (
  SELECT e.*, ROW_NUMBER() OVER (PARTITION BY e.vehicle_id ORDER BY e.last_synced_at DESC, e.id DESC) AS rn
  FROM ag_vehicle_fuel_economy e
  WHERE e.vehicle_id = :vehicle_id AND e.is_current = 1 AND e.is_active = 1 AND e.is_deleted = 0
), current_location AS (
  SELECT l.*, ROW_NUMBER() OVER (PARTITION BY l.vehicle_id ORDER BY l.last_synced_at DESC, l.id DESC) AS rn
  FROM ag_vehicle_location l
  WHERE l.vehicle_id = :vehicle_id AND l.is_current = 1 AND l.is_active = 1 AND l.is_deleted = 0
)
SELECT v.id AS vehicle_id, v.model_year AS year, v.updated_at AS vehicle_updated_at,
       ma.manufacturer_name AS make, mo.model_name AS model, tr.trim_name AS trim,
       bs.body_style_name AS body_style, ft.fuel_type_name AS fuel_type,
       dt.drive_type_name AS drivetrain, tt.transmission_type_name AS transmission,
       i.availability_status AS availability, i.odometer_km AS mileage_km,
       i.dealer_id AS dealer_id, i.last_synced_at AS inventory_updated_at,
       p.current_price AS price, p.last_synced_at AS price_updated_at,
       s.cargo_capacity_l AS cargo_capacity_l, s.towing_capacity_kg AS towing_capacity_kg,
       s.safety_rating_overall AS safety_rating, s.last_synced_at AS specs_updated_at,
       e.combined_l_per_100km AS fuel_consumption_l_per_100km,
       loc.postal_code AS postal_code, loc.latitude AS latitude, loc.longitude AS longitude,
       c.city_name AS city, sp.province_code AS province,
       d.dealer_name AS dealer_name, loc.last_synced_at AS location_updated_at
FROM ag_vehicle_master v
JOIN current_inventory i ON i.vehicle_id = v.id AND i.rn = 1
LEFT JOIN current_price p ON p.vehicle_id = v.id AND p.rn = 1
LEFT JOIN current_specs s ON s.vehicle_id = v.id AND s.rn = 1
LEFT JOIN current_economy e ON e.vehicle_id = v.id AND e.rn = 1
LEFT JOIN current_location loc ON loc.vehicle_id = v.id AND loc.rn = 1
LEFT JOIN ag_manufacturer ma ON ma.id = v.manufacturer_id
LEFT JOIN ag_model mo ON mo.id = v.model_id
LEFT JOIN ag_trim tr ON tr.id = v.trim_id
LEFT JOIN ag_body_style bs ON bs.id = v.body_style_id
LEFT JOIN ag_fuel_type ft ON ft.id = v.fuel_type_id
LEFT JOIN ag_drive_type dt ON dt.id = v.drive_type_id
LEFT JOIN ag_transmission_type tt ON tt.id = v.transmission_type_id
LEFT JOIN ag_dealer_master d ON d.id = i.dealer_id
LEFT JOIN ag_city c ON c.id = loc.city_id
LEFT JOIN ag_state_province sp ON sp.id = loc.province_id
WHERE v.id = :vehicle_id AND v.is_active = 1 AND v.is_deleted = 0
  AND v.lifecycle_state = 'active'
LIMIT 1
"""

FEATURE_SQL = """
SELECT f.feature_name, f.feature_code, f.feature_category,
       fv.value_type, fv.value_boolean, fv.value_number, fv.value_text
FROM ag_vehicle_feature_value fv
JOIN ag_vehicle_feature f ON f.id = fv.feature_id
WHERE fv.vehicle_id = :vehicle_id AND fv.is_current = 1 AND fv.is_active = 1
  AND fv.is_deleted = 0 AND f.is_active = 1 AND f.is_deleted = 0
ORDER BY f.feature_name
"""


class VehicleRepository:
    def __init__(self, engine: Engine):
        self.engine = engine

    @classmethod
    def from_dsn(cls, dsn: str) -> "VehicleRepository":
        return cls(create_engine(dsn, pool_pre_ping=True, pool_recycle=1800))

    def ping(self) -> None:
        with self.engine.connect() as connection:
            connection.execute(text("SELECT 1"))

    def close(self) -> None:
        self.engine.dispose()

    def lexicon(self) -> Lexicon:
        queries = {
            "makes": "SELECT manufacturer_name FROM ag_manufacturer WHERE is_active=1 AND is_deleted=0",
            "models": "SELECT model_name FROM ag_model WHERE is_active=1 AND is_deleted=0",
            "trims": "SELECT trim_name FROM ag_trim WHERE is_active=1 AND is_deleted=0",
            "body_styles": "SELECT body_style_name FROM ag_body_style WHERE is_active=1 AND is_deleted=0",
            "fuel_types": "SELECT fuel_type_name FROM ag_fuel_type WHERE is_active=1 AND is_deleted=0",
            "drive_types": "SELECT drive_type_name FROM ag_drive_type WHERE is_active=1 AND is_deleted=0",
            "transmission_types": "SELECT transmission_type_name FROM ag_transmission_type WHERE is_active=1 AND is_deleted=0",
            "features": "SELECT feature_name FROM ag_vehicle_feature WHERE is_active=1 AND is_deleted=0",
            "cities": "SELECT city_name FROM ag_city WHERE is_active=1 AND is_deleted=0",
            "provinces": "SELECT province_name FROM ag_state_province WHERE is_active=1 AND is_deleted=0",
        }
        values: dict[str, tuple[str, ...]] = {}
        with self.engine.connect() as connection:
            for name, sql in queries.items():
                values[name] = tuple(sorted({str(row[0]) for row in connection.execute(text(sql)) if row[0]}))
        return Lexicon(**values)

    def iter_vehicle_ids(self, batch_size: int = 500) -> Iterator[list[int]]:
        cursor = 0
        sql = text("SELECT id FROM ag_vehicle_master WHERE id > :cursor ORDER BY id LIMIT :limit")
        while True:
            with self.engine.connect() as connection:
                ids = [int(row[0]) for row in connection.execute(sql, {"cursor": cursor, "limit": batch_size})]
            if not ids:
                return
            yield ids
            cursor = ids[-1]

    def get_vehicle(self, vehicle_id: int) -> dict[str, Any] | None:
        if vehicle_id <= 0:
            return None
        with self.engine.connect() as connection:
            row = connection.execute(text(BASE_SQL), {"vehicle_id": vehicle_id}).mappings().first()
            if row is None:
                return None
            result = dict(row)
            feature_rows = connection.execute(text(FEATURE_SQL), {"vehicle_id": vehicle_id}).mappings()
            features: list[str] = []
            seating: int | None = None
            for feature in feature_rows:
                name = str(feature["feature_name"] or "").strip()
                if not name:
                    continue
                if feature["value_type"] == "boolean" and not feature["value_boolean"]:
                    continue
                if name not in features:
                    features.append(name)
                # Toyota feed expresses seating as a feature name, not a canonical spec column.
                match = re.search(r"\b(\d{1,2})\s+(?:passenger\s+)?seating\s+capacity\b", name, re.IGNORECASE)
                if match:
                    seating = max(seating or 0, int(match.group(1)))
            result["features"] = features
            result["seating_capacity"] = seating
            return result
