# Database Usage Report

## Tables Touched
### Core AutoGyrus Tables
- `ag_provider_master` — 1 Toyota provider row
- `ag_dealer_master` — 1 Toyota dealer row
- `ag_dealer_map` — 1 Toyota dealer map row
- `ag_vehicle_master` — 21 rows
- `ag_vehicle_map` — 21 rows
- `ag_inventory` — 21 rows
- `ag_vehicle_price` — 21 rows
- `ag_vehicle_price_history` — 21 rows
- `ag_vehicle_location` — 21 rows
- `ag_vehicle_specification` — 21 rows
- `ag_vehicle_engine` — 21 rows
- `ag_vehicle_transmission` — 21 rows
- `ag_vehicle_fuel_economy` — 21 rows
- `ag_vehicle_metadata` — 21 rows
- `ag_vehicle_alias` — 21 rows
- `ag_vehicle_image` — 411 rows
- `ag_vehicle_feature` — 784 rows
- `ag_vehicle_feature_value` — 5947 rows
- `ag_import_batch` — 2 rows
- `ag_import_job` — 2 rows
- `ag_import_log` — 21 rows

### WordPress Tables
- `wp_posts` — vehicle posts synced for Toyota inventory
- `wp_postmeta` — canonical vehicle meta, inventory meta, and search meta
- `wp_terms` — make/model/body/fuel/transmission/province/city/drivetrain terms
- `wp_term_taxonomy` — matching taxonomies
- `wp_term_relationships` — post-to-term links

## Canonical Mapping
- VIN is the primary vehicle identity key.
- Dealer is resolved to one logical dealer and one dealer map.
- Provider-specific fields are preserved in metadata.
- Search-critical fields are mirrored into WordPress post meta.

## Integrity Observations
- Toyota VIN uniqueness: 21 unique VINs
- Toyota dealer uniqueness: 1 unique dealer
- Duplicate VIN groups: 0
- Duplicate dealer groups: 0

## Compatibility Notes
The import writes only `ag_*` tables plus the compatibility layer targets in WordPress. No existing `wp_*` structure was modified.
