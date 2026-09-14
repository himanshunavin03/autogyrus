# Import Report

## Import Run
- Source: `codex-log/sample-data/toyota.json`
- Provider: Toyota
- Status: Success
- Run timestamp: $now

## Imported Data
- Vehicles imported: 21
- Dealers imported: 1
- Vehicle maps: 21
- Inventory rows: 21
- Pricing rows: 21
- Price history rows: 21
- Location rows: 21
- Specification rows: 21
- Engine rows: 21
- Transmission rows: 21
- Fuel economy rows: 21
- Metadata rows: 21
- Alias rows: 21
- Image rows: 411
- Feature master rows: 784
- Feature value rows: 5947
- Import batches: 2
- Import jobs: 2
- Import logs: 21

## WordPress Compatibility
- Toyota inventory was mirrored into the existing vehicle post workflow.
- Existing vehicle posts continue to resolve normally.
- Toyota posts imported through the compatibility layer preserve post meta and taxonomies required by the current theme and search flow.

## Duplicate Protection
- Unique Toyota VINs imported: 21
- Duplicate VIN groups found: 0
- Duplicate dealer groups found: 0

## Notes
- Source JSON contains no direct image URLs; Toyota photo service IDs were preserved and mapped to synthetic `toyota-photo://` references.
- Some engine fields were null in the source feed; those nulls were preserved rather than fabricated.
- One Toyota-related vehicle post already existed in the database before this import, so the total Toyota post count is 22 while Toyota-imported posts are 21.
