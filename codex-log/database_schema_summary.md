# AutoGyrus Database Schema Summary

## Table List

### Foundation
- `ag_provider_master`
- `ag_country`
- `ag_state_province`
- `ag_city`
- `ag_manufacturer`
- `ag_body_style`
- `ag_engine_type`
- `ag_transmission_type`
- `ag_drive_type`
- `ag_fuel_type`
- `ag_currency`
- `ag_market`
- `ag_system_lookup`

### Lookup and Identity
- `ag_model`
- `ag_platform`
- `ag_vehicle_generation`
- `ag_series`
- `ag_trim`
- `ag_vehicle_class`
- `ag_vehicle_condition`
- `ag_listing_status`
- `ag_color`
- `ag_manufacturing_plant`
- `ag_dealer_master`
- `ag_provider_feature`
- `ag_dealer_map`
- `ag_vehicle_master`
- `ag_vehicle_map`

### Vehicle Intelligence
- `ag_vehicle_feature`
- `ag_vehicle_feature_value`
- `ag_vehicle_specification`
- `ag_vehicle_engine`
- `ag_vehicle_transmission`
- `ag_vehicle_fuel_economy`
- `ag_vehicle_image`
- `ag_vehicle_price`
- `ag_vehicle_price_history`
- `ag_inventory`
- `ag_inventory_history`
- `ag_vehicle_location`
- `ag_vehicle_metadata`
- `ag_vehicle_alias`
- `ag_import_batch`
- `ag_import_job`
- `ag_import_log`

## Relationships
- `ag_state_province` links to `ag_country`.
- `ag_city` links to `ag_state_province` and `ag_country`.
- `ag_market` links to `ag_country` and `ag_currency`.
- `ag_model` links to `ag_manufacturer`.
- `ag_vehicle_generation` links to `ag_manufacturer`, `ag_model`, and `ag_platform`.
- `ag_series` links to `ag_vehicle_generation`.
- `ag_trim` links to `ag_manufacturer`, `ag_model`, `ag_vehicle_generation`, and `ag_series`.
- `ag_manufacturing_plant` links to `ag_country`, `ag_state_province`, and `ag_city`.
- `ag_dealer_master` links to `ag_country`, `ag_state_province`, `ag_city`, and `ag_market`.
- `ag_dealer_map` links to `ag_dealer_master` and `ag_provider_master`.
- `ag_vehicle_master` links to master lookup tables for identity, classification, and geography.
- `ag_vehicle_map` links to `ag_vehicle_master`, `ag_dealer_master`, and `ag_provider_master`.
- Vehicle intelligence tables link back to `ag_vehicle_master`, `ag_dealer_master`, `ag_provider_master`, and relevant lookup tables.
- Import tracking tables link back to `ag_provider_master`, `ag_vehicle_master`, and `ag_dealer_master` where applicable.

## Foreign Keys
- All foreign keys are defined with `ON DELETE SET NULL` where applicable.
- Self-references are limited to lookup hierarchy fields such as `ag_system_lookup.parent_lookup_id`.
- The phase 2.1 circular reference between `ag_vehicle_generation` and `ag_series` has been removed.

## Indexes
- Lookup indexes exist for all primary lookup keys and foreign key columns.
- Composite uniqueness and current-state guards are implemented for active/current vehicle facts.
- Duplicate-detection and provider-mapping indexes exist on VIN, stock number, external IDs, source system, and canonical hashes.
- Audit and status columns are indexed where they participate in operational filtering.

## Constraints
- Primary keys use `BIGINT UNSIGNED AUTO_INCREMENT`.
- UUID columns are unique on every table.
- Current-state tables use generated guard columns to prevent duplicate active rows.
- Soft delete is supported consistently with `is_deleted` and `deleted_at`.

## Estimated Table Count
- Total tables: `45`
- Foundation: `13`
- Lookup and identity: `15`
- Vehicle intelligence: `17`
- Phase 2.1 hardening: `0` new tables

## Compatibility Notes
- No existing WordPress core tables are modified.
- All new objects use the `ag_` prefix.
- The schema is additive and references existing WordPress/plugin concepts through mapping tables instead of replacing them.
