-- AutoGyrus v1.0
-- Module 007: Seed lookup data

INSERT INTO ag_country (uuid, iso2, iso3, country_name, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('11111111-1111-1111-1111-111111111111', 'CA', 'CAN', 'Canada', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('11111111-1111-1111-1111-111111111112', 'US', 'USA', 'United States', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE country_name = VALUES(country_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_currency (uuid, currency_code, currency_name, symbol, minor_unit, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('22222222-2222-2222-2222-222222222221', 'CAD', 'Canadian Dollar', '$', 2, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('22222222-2222-2222-2222-222222222222', 'USD', 'US Dollar', '$', 2, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE currency_name = VALUES(currency_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_market (uuid, market_code, market_name, country_id, currency_id, language_code, region_code, market_type, is_primary, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '33333333-3333-3333-3333-333333333331', 'CA', 'Canada', c.id, cur.id, 'en', 'CA', 'country', 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_country c, ag_currency cur
WHERE c.iso2 = 'CA' AND cur.currency_code = 'CAD'
ON DUPLICATE KEY UPDATE market_name = VALUES(market_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_market (uuid, market_code, market_name, country_id, currency_id, language_code, region_code, market_type, is_primary, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '33333333-3333-3333-3333-333333333332', 'US', 'United States', c.id, cur.id, 'en', 'US', 'country', 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_country c, ag_currency cur
WHERE c.iso2 = 'US' AND cur.currency_code = 'USD'
ON DUPLICATE KEY UPDATE market_name = VALUES(market_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_provider_master (uuid, provider_code, provider_name, provider_category, provider_subcategory, api_base_url, documentation_url, auth_type, import_mode, provider_priority, rate_limit_per_minute, supports_webhooks, supports_images, supports_vin, supports_history, supports_specifications, supports_reviews, supports_pricing, supports_inventory, supports_marketplace, supports_bulk_sync, supports_delta_sync, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('44444444-4444-4444-4444-444444444441', 'wordpress', 'WordPress', 'internal', 'cms', NULL, NULL, NULL, 'push', 1, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444442', 'dealer_dashboard', 'Dealer Dashboard', 'internal', 'ui', NULL, NULL, NULL, 'push', 2, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444443', 'marketcheck', 'MarketCheck', 'external', 'provider', NULL, NULL, 'api_key', 'pull', 50, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444444', 'dealer_direct', 'Dealer Direct', 'external', 'provider', NULL, NULL, 'oauth2', 'pull', 60, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444445', 'oem_feed', 'OEM Feed', 'external', 'provider', NULL, NULL, 'oauth2', 'pull', 70, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444446', 'csv_import', 'CSV Import', 'internal', 'import', NULL, NULL, NULL, 'batch', 80, NULL, 0, 1, 0, 1, 0, 0, 0, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444447', 'manual_import', 'Manual Import', 'internal', 'import', NULL, NULL, NULL, 'manual', 90, NULL, 0, 1, 0, 1, 0, 0, 0, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444448', 'future_api', 'Future API', 'external', 'provider', NULL, NULL, 'token', 'pull', 95, NULL, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('44444444-4444-4444-4444-444444444449', 'vin_decode', 'VIN Decode', 'internal', 'service', NULL, NULL, NULL, 'pull', 5, NULL, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE provider_name = VALUES(provider_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_provider_feature (uuid, provider_id, feature_code, feature_name, feature_category, support_level, is_supported, feature_config, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '55555555-5555-5555-5555-555555555441', p.id, 'supports_images', 'Images', 'media', 'full', 1, JSON_OBJECT('priority', 1), 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_provider_master p WHERE p.provider_code = 'marketcheck'
ON DUPLICATE KEY UPDATE feature_name = VALUES(feature_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_provider_feature (uuid, provider_id, feature_code, feature_name, feature_category, support_level, is_supported, feature_config, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '55555555-5555-5555-5555-555555555442', p.id, 'supports_specifications', 'Specifications', 'data', 'full', 1, JSON_OBJECT('priority', 1), 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_provider_master p WHERE p.provider_code = 'marketcheck'
ON DUPLICATE KEY UPDATE feature_name = VALUES(feature_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_provider_feature (uuid, provider_id, feature_code, feature_name, feature_category, support_level, is_supported, feature_config, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '55555555-5555-5555-5555-555555555443', p.id, 'supports_pricing', 'Pricing', 'commerce', 'full', 1, JSON_OBJECT('priority', 1), 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_provider_master p WHERE p.provider_code = 'dealer_direct'
ON DUPLICATE KEY UPDATE feature_name = VALUES(feature_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_provider_feature (uuid, provider_id, feature_code, feature_name, feature_category, support_level, is_supported, feature_config, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
SELECT '55555555-5555-5555-5555-555555555444', p.id, 'supports_inventory', 'Inventory', 'commerce', 'full', 1, JSON_OBJECT('priority', 1), 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM ag_provider_master p WHERE p.provider_code = 'dealer_direct'
ON DUPLICATE KEY UPDATE feature_name = VALUES(feature_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_system_lookup (uuid, lookup_group, lookup_code, lookup_name, lookup_value, sort_order, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('66666666-6666-6666-6666-666666666611', 'sync_status', 'pending', 'Pending', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666612', 'sync_status', 'processing', 'Processing', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666613', 'sync_status', 'complete', 'Complete', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666614', 'sync_status', 'failed', 'Failed', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666615', 'sync_status', 'quarantined', 'Quarantined', NULL, 50, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666621', 'batch_status', 'pending', 'Pending', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666622', 'batch_status', 'running', 'Running', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666623', 'batch_status', 'completed', 'Completed', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666624', 'batch_status', 'failed', 'Failed', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666625', 'batch_status', 'dead_lettered', 'Dead Lettered', NULL, 50, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666631', 'job_status', 'queued', 'Queued', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666632', 'job_status', 'running', 'Running', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666633', 'job_status', 'succeeded', 'Succeeded', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666634', 'job_status', 'failed', 'Failed', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666635', 'job_status', 'retry_wait', 'Retry Wait', NULL, 50, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666636', 'job_status', 'dead_lettered', 'Dead Lettered', NULL, 60, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666641', 'log_level', 'debug', 'Debug', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666642', 'log_level', 'info', 'Info', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666643', 'log_level', 'warning', 'Warning', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666644', 'log_level', 'error', 'Error', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666645', 'log_level', 'critical', 'Critical', NULL, 50, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666651', 'price_status', 'active', 'Active', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666652', 'price_status', 'pending', 'Pending', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666653', 'price_status', 'expired', 'Expired', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666654', 'price_status', 'locked', 'Locked', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666661', 'inventory_status', 'available', 'Available', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666662', 'inventory_status', 'reserved', 'Reserved', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666663', 'inventory_status', 'sold', 'Sold', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666664', 'inventory_status', 'archived', 'Archived', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666671', 'image_role', 'primary', 'Primary', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666672', 'image_role', 'gallery', 'Gallery', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666673', 'image_role', 'interior', 'Interior', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666674', 'image_role', 'exterior', 'Exterior', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666675', 'image_role', '360', '360', NULL, 50, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666681', 'alias_type', 'marketing', 'Marketing', NULL, 10, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666682', 'alias_type', 'regional', 'Regional', NULL, 20, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666683', 'alias_type', 'provider', 'Provider', NULL, 30, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('66666666-6666-6666-6666-666666666684', 'alias_type', 'alternate', 'Alternate', NULL, 40, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE lookup_name = VALUES(lookup_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_vehicle_condition (uuid, condition_code, condition_name, condition_rank, is_new_vehicle, is_used_vehicle, is_certified_vehicle, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('77777777-7777-7777-7777-777777777771', 'new', 'New', 10, 1, 0, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('77777777-7777-7777-7777-777777777772', 'used', 'Used', 20, 0, 1, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('77777777-7777-7777-7777-777777777773', 'certified_pre_owned', 'Certified Pre-Owned', 30, 0, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE condition_name = VALUES(condition_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_listing_status (uuid, status_code, status_name, status_rank, is_public, is_terminal, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('88888888-8888-8888-8888-888888888881', 'draft', 'Draft', 10, 0, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('88888888-8888-8888-8888-888888888882', 'active', 'Active', 20, 1, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('88888888-8888-8888-8888-888888888883', 'pending', 'Pending', 30, 1, 0, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('88888888-8888-8888-8888-888888888884', 'sold', 'Sold', 40, 1, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('88888888-8888-8888-8888-888888888885', 'removed', 'Removed', 50, 0, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('88888888-8888-8888-8888-888888888886', 'archived', 'Archived', 60, 0, 1, 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE status_name = VALUES(status_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_vehicle_class (uuid, class_code, class_name, class_group, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('99999999-9999-9999-9999-999999999991', 'passenger', 'Passenger', 'road', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('99999999-9999-9999-9999-999999999992', 'suv', 'SUV', 'road', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('99999999-9999-9999-9999-999999999993', 'truck', 'Truck', 'road', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('99999999-9999-9999-9999-999999999994', 'van', 'Van', 'road', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('99999999-9999-9999-9999-999999999995', 'performance', 'Performance', 'road', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('99999999-9999-9999-9999-999999999996', 'commercial', 'Commercial', 'commercial', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE class_name = VALUES(class_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_body_style (uuid, body_style_name, body_style_slug, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1', 'Sedan', 'sedan', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2', 'SUV', 'suv', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3', 'Coupe', 'coupe', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa4', 'Hatchback', 'hatchback', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa5', 'Wagon', 'wagon', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa6', 'Pickup', 'pickup', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa7', 'Van', 'van', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa8', 'Minivan', 'minivan', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa9', 'Convertible', 'convertible', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE body_style_name = VALUES(body_style_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_engine_type (uuid, engine_type_name, engine_type_slug, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb1', 'Gas', 'gas', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb2', 'Diesel', 'diesel', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb3', 'Hybrid', 'hybrid', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb4', 'Plug-in Hybrid', 'plug_in_hybrid', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb5', 'Electric', 'electric', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE engine_type_name = VALUES(engine_type_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_transmission_type (uuid, transmission_type_name, transmission_type_slug, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('cccccccc-cccc-cccc-cccc-ccccccccccc1', 'Automatic', 'automatic', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('cccccccc-cccc-cccc-cccc-ccccccccccc2', 'Manual', 'manual', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('cccccccc-cccc-cccc-cccc-ccccccccccc3', 'CVT', 'cvt', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('cccccccc-cccc-cccc-cccc-ccccccccccc4', 'DCT', 'dct', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE transmission_type_name = VALUES(transmission_type_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_drive_type (uuid, drive_type_name, drive_type_slug, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('dddddddd-dddd-dddd-dddd-ddddddddddd1', 'FWD', 'fwd', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('dddddddd-dddd-dddd-dddd-ddddddddddd2', 'RWD', 'rwd', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('dddddddd-dddd-dddd-dddd-ddddddddddd3', 'AWD', 'awd', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('dddddddd-dddd-dddd-dddd-ddddddddddd4', '4WD', '4wd', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE drive_type_name = VALUES(drive_type_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_fuel_type (uuid, fuel_type_name, fuel_type_slug, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee1', 'Gasoline', 'gasoline', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee2', 'Diesel', 'diesel', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee3', 'Hybrid', 'hybrid', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee4', 'Plug-in Hybrid', 'plug_in_hybrid', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee5', 'Electric', 'electric', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeee6', 'Hydrogen', 'hydrogen', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE fuel_type_name = VALUES(fuel_type_name), updated_at = CURRENT_TIMESTAMP;

INSERT INTO ag_color (uuid, color_code, color_name, color_family, color_scope, hex_value, source_system, is_active, is_deleted, is_verified, version, created_at, updated_at)
VALUES
('ffffffff-ffff-ffff-ffff-fffffffffff1', 'black', 'Black', 'neutral', 'both', '#000000', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff2', 'white', 'White', 'neutral', 'both', '#FFFFFF', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff3', 'gray', 'Gray', 'neutral', 'both', '#808080', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff4', 'silver', 'Silver', 'neutral', 'both', '#C0C0C0', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff5', 'blue', 'Blue', 'cool', 'both', '#0000FF', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff6', 'red', 'Red', 'warm', 'both', '#FF0000', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff7', 'green', 'Green', 'cool', 'both', '#008000', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff8', 'brown', 'Brown', 'earth', 'both', '#8B4513', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-fffffffffff9', 'beige', 'Beige', 'earth', 'both', '#F5F5DC', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ffffffff-ffff-ffff-ffff-ffffffffffff', 'orange', 'Orange', 'warm', 'both', '#FFA500', 'autogyrus', 1, 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE color_name = VALUES(color_name), updated_at = CURRENT_TIMESTAMP;

