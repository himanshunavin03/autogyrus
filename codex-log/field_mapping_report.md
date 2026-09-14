# Field Mapping Report

## Source Overview
- Source file: `codex-log/sample-data/toyota.json`
- Source shape: `results[0].hits[]`
- Records imported: 21
- Dealer source: 1 logical dealer (`Toyota on the Trail`)

## Canonical Mapping Summary
### Identity
- `vin` -> `ag_vehicle_master.vin`, `ag_vehicle_map.external_id`, `wp_postmeta.vin`
- `item_key` -> `ag_vehicle_metadata`, compatibility meta
- `stock_number` -> `ag_inventory.stock_number`, `wp_postmeta.stock_number`
- `dealer_id` -> `ag_dealer_map.external_id`

### Vehicle Core
- `year`, `make_name`, `model_name`, `trim` -> `ag_vehicle_master`, `wp_posts.post_title`, `wp_postmeta`
- `manufacturer_code`, `style_id`, `make_model_trim` -> `ag_vehicle_metadata`
- `body_type_name`, `body_type_category` -> `ag_body_style`, `ag_vehicle_class`, `wp_terms.body_type`
- `fuel_type_name`, `fuel_type_category` -> `ag_fuel_type`, `ag_engine_type`, `wp_terms.fuel_type`
- `transmission_name`, `transmission_type`, `transmission_desc` -> `ag_transmission_type`, `ag_vehicle_transmission`, `wp_terms.transmission`
- `drive_type_name`, `drive_type_desc` -> `ag_drive_type`, `wp_terms.drivetrain`

### Pricing
- `sort_price`, `list_price`, `special_price`, `regular_price`, `msrp`, `cbb_selected_price` -> `ag_vehicle_price`
- Initial price snapshot stored in `ag_vehicle_price_history`

### Location
- Dealer city/province/postal/address/geolocation -> `ag_dealer_master`, `ag_dealer_map`, `ag_vehicle_location`
- `dealer_city_name`, `dealer_province_short`, `dealer_postal_code`, `_geoloc` -> WordPress meta and AutoGyrus location tables

### Images
- `photo_service_ids` -> `ag_vehicle_image` as synthetic `toyota-photo://<id>` references and WordPress compatibility metadata
- No direct image URLs were present in the Toyota JSON

### Features
- `equipment[]` -> `ag_vehicle_feature` + `ag_vehicle_feature_value`
- Each equipment string was normalized into a feature code and boolean vehicle feature value

### Metadata Preservation
All fields without a dedicated destination table were preserved in `ag_vehicle_metadata`.
Examples include:
- pricing detail objects
- model grouping hints
- page/website overlay data
- dealer-specific source attributes
- decode and publication flags
- source analytics / ranking fields

## Transformation Rules
- Trim whitespace on strings
- Normalize identifiers to canonical slugs when used as lookup keys
- Preserve null source values as null
- Convert timestamps to MySQL datetimes
- Store provider-only fields in JSON metadata instead of discarding them
- Keep WordPress compatibility fields in post meta to preserve existing site behavior

## Exceptions / Notes
- Source engine fields are often null; no synthetic engine data was generated.
- Toyota photo service IDs were retained even though the source feed did not provide direct image URLs.
