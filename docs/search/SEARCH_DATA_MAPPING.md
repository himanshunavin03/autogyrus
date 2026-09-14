# Search data mapping

Updated: 2026-09-14. Evidence: `codex-log/database/001_foundation.sql`, `002_lookup_tables.sql`, `003_vehicle_intelligence.sql`, read-only `information_schema` inspection of the local MySQL database, and `autogyrus_search/repository.py`. Null means unknown; a missing value is never guessed into a hard-filter match.

| SearchIntent / concept | Canonical source and column | Qdrant payload | Behavior when missing |
| --- | --- | --- | --- |
| Canonical identity | `ag_vehicle_master.id` | `vehicle_id` | Cannot index |
| Provider mapping | `ag_vehicle_map.vehicle_id`, `provider_id`, `external_id` | Not indexed; ID remains canonical | Provider mapping is retained in MySQL |
| Availability | `ag_inventory.availability_status`, `is_current`, `is_active`, `is_deleted` | `availability` | Not searchable |
| Dealer | `ag_inventory.dealer_id` → `ag_dealer_master.id`, `dealer_name` | `dealer_id` | May be null; no dealer ranking |
| Make | `ag_vehicle_master.manufacturer_id` → `ag_manufacturer.manufacturer_name` | `make` | Exact make filter excludes |
| Model | `ag_vehicle_master.model_id` → `ag_model.model_name` | `model` | Exact model filter excludes |
| Trim | `ag_vehicle_master.trim_id` → `ag_trim.trim_name` | `trim` | Exact trim filter excludes |
| Model year | `ag_vehicle_master.model_year` | `year` | Year filters exclude |
| Price minimum/maximum | Current `ag_vehicle_price.current_price` | `price` | Price filters exclude |
| Price history | `ag_vehicle_price_history.old_price`, `new_price` | Not indexed in v1 | Historical price not used in live ranking |
| Mileage | Current `ag_inventory.odometer_km` | `mileage_km` | Mileage filter excludes |
| Body style | `ag_vehicle_master.body_style_id` → `ag_body_style.body_style_name`; lookup labels such as “Sport Utility” and “Short Bed” map through versioned `ontology.json` | `body_style` (raw), `body_style_group` (derived) | Group filter excludes; unknown labels remain ungrouped |
| Fuel type | `ag_vehicle_master.fuel_type_id` → `ag_fuel_type.fuel_type_name` | `fuel_type` | Exact filter excludes |
| Drivetrain | `ag_vehicle_master.drive_type_id` → `ag_drive_type.drive_type_name` | `drivetrain` | Exact filter excludes |
| Transmission | `ag_vehicle_master.transmission_type_id` → `ag_transmission_type.transmission_type_name` | `transmission` | Exact filter excludes |
| Minimum seating | **No dedicated `ag_*` seating column.** Derived from active `ag_vehicle_feature_value.feature_id` → `ag_vehicle_feature.feature_name` pattern such as “5 Passenger Seating Capacity” | `seating_capacity` | Seating filter excludes; no family capacity claim |
| Required/preferred features | Active `ag_vehicle_feature_value.feature_id`, `value_boolean`, `value_text` → `ag_vehicle_feature.feature_name` | `features` | Required feature excludes; preferred feature gives no boost |
| Cargo-space preference | Current `ag_vehicle_specification.cargo_capacity_l` | `cargo_capacity_l` | No cargo boost |
| Towing preference | Current `ag_vehicle_specification.towing_capacity_kg` | `towing_capacity_kg` | No towing boost |
| Fuel-efficiency preference | Current `ag_vehicle_fuel_economy.combined_l_per_100km` | `fuel_consumption_l_per_100km` | No consumption boost |
| Safety signal | Current `ag_vehicle_specification.safety_rating_overall`; active safety feature names | `safety_rating`, `features` | Profile uses only known signals |
| City/province | Current `ag_vehicle_location.city_id` → `ag_city.city_name`; `province_id` → `ag_state_province.province_code` | `city`, `province` | Exact location filter excludes |
| Postal code | Current `ag_vehicle_location.postal_code` | `postal_code` | Postal filter excludes |
| Radius | Current `ag_vehicle_location.latitude`, `longitude`; configured origin city centroid in `ontology.json` | `location`, `latitude`, `longitude` | Radius filter excludes; unknown origin raises validation error |
| Images | Current `ag_vehicle_image.original_image_url`, `thumbnail_image_url`, `source_media_id` | Not indexed in v1 | WordPress resolves display media; some Toyota IDs are synthetic references |
| Reliability preference | **No canonical `ag_*` score column**; WordPress plugin currently computes a heuristic | `reliability_score: null` | No score boost or strong-score explanation |
| Winter suitability | **No canonical `ag_*` score column**; current service uses drivetrain and winter-feature evidence | `winter_score: null` | Evidence-based profile boost only |
| Future-value preference | **No canonical `ag_*` score column**; WordPress plugin currently computes a heuristic | `future_value_score: null` | No score boost or prediction claim |
| Family suitability | Derived from explicit seating, body style, safety/rear-access features, cargo capacity; weights in `ontology.json` | `family_score` when seating known | Null if seating unknown; query-time ranking uses available evidence |
| Freshness | `ag_vehicle_master.updated_at` and current fact-table `last_synced_at` | `source_updated_at` | No freshness boost |

All live MySQL queries use fixed SQL with bind parameters. SearchIntent text never becomes a table name, column name, or SQL fragment. `ag_vehicle_price_history`, `ag_vehicle_image`, and provider mappings remain canonical even where this first index does not project them.
