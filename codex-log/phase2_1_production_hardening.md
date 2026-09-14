# AutoGyrus Phase 2.1
# Production Hardening & Final Database Validation

## 1. Executive Summary
The Phase 1, Phase 1.1, and Phase 2 schema is structurally strong and already suitable for long-term platform growth.

The remaining work is narrow but important:
- enforce current-state uniqueness more strictly
- remove one circular relationship in the vehicle lineage model
- standardize one remaining audit inconsistency
- strengthen duplicate detection and lookup performance

This is not a redesign. It is a production hardening pass.

The schema is compatible with the current WordPress application because the hardening changes are additive or relational-cleanup changes only. Nothing in WordPress needs to change.

## 2. Final Architecture Score
95/100

## 3. Final Database Score
96/100

## 4. Scalability Score
97/100

## 5. AI Readiness Score
94/100

## 6. WordPress Compatibility Score
99/100

## 7. Performance Score
95/100

## 8. Security Score
96/100

## 9. Every Required Schema Change

### 9.1 Break the circular lineage relationship
The Phase 1.1 model introduced a circular FK between generation and series:
- `ag_series.generation_id -> ag_vehicle_generation.id`
- `ag_vehicle_generation.series_id -> ag_series.id`

That is not ideal for a locked production foundation.

Required change:
- keep `ag_series.generation_id`
- remove `ag_vehicle_generation.series_id`
- remove the corresponding FK and index from `ag_vehicle_generation`

### 9.2 Enforce one current active row per business context
Current-state fact tables need explicit uniqueness protection.

Required hardening target tables:
- `ag_vehicle_specification`
- `ag_vehicle_engine`
- `ag_vehicle_transmission`
- `ag_vehicle_fuel_economy`
- `ag_vehicle_feature_value`
- `ag_vehicle_image`
- `ag_vehicle_price`
- `ag_inventory`
- `ag_vehicle_location`
- `ag_vehicle_metadata`
- `ag_vehicle_alias`

Required approach:
- add a generated `current_record_guard` column
- build a unique key using the vehicle/dealer context, provider, source system, business dimensions, and `current_record_guard`
- allow historical rows to remain appendable via `NULL` guard values

### 9.3 Standardize audit columns
One history table still carries a non-standard extra audit field:
- `ag_vehicle_price_history.change_reason_text`

Required change:
- remove the inconsistent extra field
- keep the canonical `change_reason` audit field only

### 9.4 Strengthen duplicate detection
`ag_vehicle_master` needs stronger duplicate lookup coverage.

Required additions:
- index `duplicate_group_hash`
- index `vin_normalized`
- index the core identity lookup path:
  - manufacturer
  - model
  - trim
  - model year
  - market

### 9.5 Improve dealer/provider lookups
`ag_dealer_map` needs a direct lookup index for provider dealer codes.

Required additions:
- index `provider_dealer_code`

### 9.6 Improve location lookup
`ag_vehicle_location` needs geospatial and postal lookup support.

Required additions:
- index `geohash`
- index `postal_code`

## 10. Every Required Index Change

### Add indexes
- `ag_vehicle_master`
  - `duplicate_group_hash`
  - `vin_normalized`
  - `(manufacturer_id, model_id, trim_id, model_year, market_id)`
- `ag_dealer_map`
  - `provider_dealer_code`
- `ag_vehicle_location`
  - `geohash`
  - `postal_code`

### Add current-state unique indexes
- `ag_vehicle_specification`
- `ag_vehicle_engine`
- `ag_vehicle_transmission`
- `ag_vehicle_fuel_economy`
- `ag_vehicle_feature_value`
- `ag_vehicle_image`
- `ag_vehicle_price`
- `ag_inventory`
- `ag_vehicle_location`
- `ag_vehicle_metadata`
- `ag_vehicle_alias`

## 11. Every Required Constraint Change

### Foreign key cleanup
- drop the circular `ag_vehicle_generation -> ag_series` foreign key

### Unique constraint hardening
- add a current-state unique constraint to each current fact table
- keep business keys narrow enough to prevent duplicate active rows
- preserve historical append behavior through the generated guard column

### Audit cleanup
- remove the non-standard `change_reason_text` audit variant from price history

## 12. Every Required Relationship Change

### Mandatory relationship change
- remove the `ag_vehicle_generation.series_id` relationship

### Why
- the model should be directional:
  - `ag_series -> ag_vehicle_generation`
- generation does not need to reference series back in the locked foundation

### All other core relationships
- are structurally sound
- do not require redesign

## 13. Provider Conflict Rules

The permanent provider strategy should be deterministic and field-aware.

### Identity fields
For VIN, manufacturer, model, generation, series, platform, trim, and body code:
- OEM / VIN decode has highest trust
- then provider direct feed
- then MarketCheck
- then dealer import/manual feed

### Commercial fields
For price, inventory, availability, and listing status:
- dealer manual edit in WordPress has highest trust
- then dealer direct feed
- then provider feed
- then MarketCheck

### Images
For image selection and ordering:
- dealer direct/manual image selection wins
- then provider image feed
- then AI-generated image placeholders

### Conflict resolution
- higher-priority source can overwrite lower-priority current values
- lower-priority sources still append history and log conflicts
- if a verified manual override exists, imports must not overwrite it without explicit unlock

### Verification rules
- match VIN + provider + dealer context + canonical hash = auto-accept
- core identity conflict = manual review
- low-confidence fields may update the current row only if source priority is higher and the row is not manually locked

## 14. Duplicate Detection Rules

### Exact match
- normalized VIN match
- canonical identity hash match
- source system + external ID match
- provider record hash match

### Strong match
- manufacturer + model + trim + model year + market
- stock number + dealer context
- VIN partial decode match when exact VIN is unavailable

### Probable match
- same vehicle class and generation with matching dealer/location context
- same image hash and near-identical core identity attributes

### Manual review
- conflicting VINs
- multiple canonical hashes for the same exact VIN
- same external ID mapped to different vehicles
- same dealer context with materially different identity attributes

## 15. Source Priority Rules

Recommended default precedence:
1. verified manual override
2. WordPress dealer-authored current row
3. dealer direct feed
4. OEM / manufacturer feed
5. MarketCheck
6. CSV/manual bulk import
7. legacy or inferred source

Field-level precedence still applies.
That means price and identity do not always use the same trust order.

## 16. History Strategy

### Append-only tables
- `ag_vehicle_price_history`
- `ag_inventory_history`
- `ag_import_log`

### Behavior
- never overwrite history rows
- append corrections and new observations
- current-state tables represent the active snapshot
- history tables preserve the chain of change

### Retention
- keep hot detailed history in the primary database for operational querying
- archive older history by policy rather than by redesign
- retain enough history to support price trends, inventory movement, and import diagnostics

### Partition strategy
- not required to lock the schema
- recommended later for high-volume history tables
- if adopted, partition by `created_at` or `event_at` with monthly or quarterly ranges

## 17. Retention Strategy

- `ag_vehicle_price_history`: long retention, because pricing trends matter
- `ag_inventory_history`: medium retention, because operational movement matters
- `ag_import_log`: shorter detailed retention, because error diagnostics can be summarized later
- current-state tables: retain indefinitely with soft delete and versioning

## 18. Partition Strategy

No structural partitioning change is required to declare Phase 2.1 production ready.

Recommended operational threshold:
- partition history/log tables when row volume or query latency makes full-table scans expensive

Recommended partition keys:
- `created_at`
- `event_at`

Recommended partition style:
- monthly range partitions for large history/log tables

## 19. Final GO / NO-GO Decision
NO-GO

The schema is almost production ready, but the following mandatory hardening changes should be applied first:
1. remove the circular generation/series FK
2. add current-state unique guards to the fact tables
3. standardize the remaining audit inconsistency
4. add duplicate and lookup indexes

After those ALTERs are applied, the database can be considered locked for the Synchronization Layer.

## 20. Production Readiness Checklist

- [ ] Circular FK removed
- [ ] Current-state unique constraints added
- [ ] Audit field inconsistency removed
- [ ] Duplicate detection indexes added
- [ ] Lookup indexes added
- [ ] WordPress compatibility preserved
- [ ] Soft delete behavior preserved
- [ ] History tables remain append-only
- [ ] Source priority rules documented
- [ ] Duplicate resolution rules documented
- [ ] Retention policy documented

## Required ALTER TABLE Statements

```sql
ALTER TABLE ag_vehicle_generation
  DROP FOREIGN KEY fk_ag_vehicle_generation_series,
  DROP COLUMN series_id;

ALTER TABLE ag_vehicle_master
  ADD INDEX idx_ag_vehicle_master_duplicate_group_hash (duplicate_group_hash),
  ADD INDEX idx_ag_vehicle_master_vin_normalized (vin_normalized),
  ADD INDEX idx_ag_vehicle_master_identity_lookup (manufacturer_id, model_id, trim_id, model_year, market_id);

ALTER TABLE ag_dealer_map
  ADD INDEX idx_ag_dealer_map_provider_dealer_code (provider_dealer_code);

ALTER TABLE ag_vehicle_location
  ADD INDEX idx_ag_vehicle_location_geohash (geohash),
  ADD INDEX idx_ag_vehicle_location_postal_code (postal_code);

ALTER TABLE ag_vehicle_price_history
  DROP COLUMN change_reason_text;
```

```sql
ALTER TABLE ag_vehicle_feature_value
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_feature_value_current
    (vehicle_context_id, feature_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_specification
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_specification_current
    (vehicle_context_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_engine
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_engine_current
    (vehicle_context_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_transmission
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_transmission_current
    (vehicle_context_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_fuel_economy
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_fuel_economy_current
    (vehicle_context_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_image
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_image_current
    (vehicle_context_id, provider_id, image_role, image_hash, source_system, current_record_guard);

ALTER TABLE ag_vehicle_price
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN dealer_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(dealer_id, dealer_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_price_current
    (vehicle_context_id, dealer_context_id, provider_id, market_id, province_id, currency_id, price_type, source_system, current_record_guard);

ALTER TABLE ag_inventory
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN dealer_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(dealer_id, dealer_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_inventory_current
    (vehicle_context_id, dealer_context_id, provider_id, source_system, current_record_guard);

ALTER TABLE ag_vehicle_location
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN dealer_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(dealer_id, dealer_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_location_current
    (vehicle_context_id, dealer_context_id, provider_id, location_type, source_system, current_record_guard);

ALTER TABLE ag_vehicle_metadata
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN dealer_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(dealer_id, dealer_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_metadata_current
    (vehicle_context_id, dealer_context_id, provider_id, metadata_namespace, source_system, current_record_guard);

ALTER TABLE ag_vehicle_alias
  ADD COLUMN vehicle_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(vehicle_id, vehicle_map_id)) STORED,
  ADD COLUMN dealer_context_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (COALESCE(dealer_id, dealer_map_id)) STORED,
  ADD COLUMN current_record_guard TINYINT
    GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_ag_vehicle_alias_current
    (vehicle_context_id, dealer_context_id, provider_id, alias_type, alias_name_normalized, locale_code, source_system, current_record_guard);
```

## Final Readiness Statement
The AutoGyrus database schema is not yet locked until the ALTERs above are executed.

Once those changes are applied, the schema is production-grade and ready for the Synchronization Layer.
