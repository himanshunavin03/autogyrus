-- AutoGyrus Database v1.0
-- Master migration loader
-- Execute this file from the codex-log directory with a MySQL client that supports SOURCE.

SOURCE database/001_foundation.sql;
SOURCE database/002_lookup_tables.sql;
SOURCE database/003_vehicle_intelligence.sql;
SOURCE database/004_constraints.sql;
SOURCE database/005_indexes.sql;
SOURCE database/006_views.sql;
SOURCE database/007_seed_lookup_data.sql;
