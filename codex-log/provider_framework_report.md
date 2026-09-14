# Provider Framework Report

## Summary
The Toyota provider integration uses a reusable provider framework built around the existing AutoGyrus plugin architecture.

## Framework Components
- `ProviderInterface`
- `AbstractProvider`
- `ImportPipeline`
- `ValidationPipeline`
- `CanonicalMapper`
- `PersistenceService`
- `DuplicateDetectionService`
- `ConflictResolutionService`
- `HistoryService`
- `AuditService`
- `ToyotaProvider`

## Files Implemented or Updated
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-provider-interface.php`
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-provider-base.php`
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-toyota-provider.php`
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-import-service.php`
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-provider-admin.php`
- `app/public/wp-content/plugins/autogyrus/includes/class-autogyrus-compatibility.php`
- `codex-log/generate_toyota_phase8_sql.php`
- `codex-log/toyota_phase8_import.sql`

## Compatibility Notes
- Existing WordPress vehicle pages remain intact.
- Existing dealer dashboard behavior remains intact.
- Existing URLs, AJAX, and search continue to function.
- ToyotaProvider is isolated behind the provider interface and can be swapped for MarketCheck, DealerDirect, OEM, or CSV adapters without changing the import engine.

## Reuse Pattern
1. Create a provider adapter.
2. Normalize payload into the canonical vehicle model.
3. Pass records into the shared import pipeline.
4. Persist into `ag_*` tables.
5. Sync WordPress post/meta/taxonomy only through the compatibility layer.
