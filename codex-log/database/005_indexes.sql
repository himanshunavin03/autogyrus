-- AutoGyrus v1.0
-- Module 005: Secondary indexes

CREATE INDEX idx_ag_provider_master_state ON ag_provider_master (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_provider_master_category ON ag_provider_master (provider_category, provider_priority);

CREATE INDEX idx_ag_country_state ON ag_country (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_state_province_state ON ag_state_province (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_city_state ON ag_city (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_manufacturer_state ON ag_manufacturer (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_body_style_state ON ag_body_style (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_engine_type_state ON ag_engine_type (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_transmission_type_state ON ag_transmission_type (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_drive_type_state ON ag_drive_type (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_fuel_type_state ON ag_fuel_type (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_currency_state ON ag_currency (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_market_country ON ag_market (country_id, currency_id);
CREATE INDEX idx_ag_market_state ON ag_market (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_system_lookup_parent ON ag_system_lookup (parent_lookup_id);
CREATE INDEX idx_ag_system_lookup_sort ON ag_system_lookup (lookup_group, sort_order);
CREATE INDEX idx_ag_system_lookup_state ON ag_system_lookup (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_model_state ON ag_model (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_platform_market ON ag_platform (market_id);
CREATE INDEX idx_ag_platform_state ON ag_platform (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_generation_manufacturer ON ag_vehicle_generation (manufacturer_id);
CREATE INDEX idx_ag_vehicle_generation_platform ON ag_vehicle_generation (platform_id);
CREATE INDEX idx_ag_vehicle_generation_market ON ag_vehicle_generation (market_id);
CREATE INDEX idx_ag_vehicle_generation_country ON ag_vehicle_generation (country_id);
CREATE INDEX idx_ag_vehicle_generation_import_country ON ag_vehicle_generation (import_country_id);
CREATE INDEX idx_ag_vehicle_generation_plant ON ag_vehicle_generation (manufacturing_plant_id);
CREATE INDEX idx_ag_vehicle_generation_state ON ag_vehicle_generation (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_series_manufacturer ON ag_series (manufacturer_id);
CREATE INDEX idx_ag_series_platform ON ag_series (platform_id);
CREATE INDEX idx_ag_series_generation ON ag_series (generation_id);
CREATE INDEX idx_ag_series_market ON ag_series (market_id);
CREATE INDEX idx_ag_series_state ON ag_series (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_trim_state ON ag_trim (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_class_parent ON ag_vehicle_class (parent_class_id);
CREATE INDEX idx_ag_vehicle_class_state ON ag_vehicle_class (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_condition_rank ON ag_vehicle_condition (condition_rank);
CREATE INDEX idx_ag_vehicle_condition_state ON ag_vehicle_condition (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_listing_status_rank ON ag_listing_status (status_rank);
CREATE INDEX idx_ag_listing_status_state ON ag_listing_status (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_color_state ON ag_color (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_manufacturing_plant_country ON ag_manufacturing_plant (country_id);
CREATE INDEX idx_ag_manufacturing_plant_province ON ag_manufacturing_plant (province_id);
CREATE INDEX idx_ag_manufacturing_plant_city ON ag_manufacturing_plant (city_id);
CREATE INDEX idx_ag_manufacturing_plant_state ON ag_manufacturing_plant (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_dealer_master_market ON ag_dealer_master (market_id);
CREATE INDEX idx_ag_dealer_master_country ON ag_dealer_master (country_id);
CREATE INDEX idx_ag_dealer_master_province ON ag_dealer_master (province_id);
CREATE INDEX idx_ag_dealer_master_city ON ag_dealer_master (city_id);
CREATE INDEX idx_ag_dealer_master_state ON ag_dealer_master (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_provider_feature_category ON ag_provider_feature (feature_category);
CREATE INDEX idx_ag_provider_feature_state ON ag_provider_feature (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_dealer_map_dealer ON ag_dealer_map (dealer_id);
CREATE INDEX idx_ag_dealer_map_market ON ag_dealer_map (market_id);
CREATE INDEX idx_ag_dealer_map_status ON ag_dealer_map (sync_status, source_priority, source_rank, is_primary);
CREATE INDEX idx_ag_dealer_map_state ON ag_dealer_map (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_dealer_map_provider_code ON ag_dealer_map (provider_dealer_code);

CREATE INDEX idx_ag_vehicle_master_model ON ag_vehicle_master (model_id);
CREATE INDEX idx_ag_vehicle_master_generation ON ag_vehicle_master (generation_id);
CREATE INDEX idx_ag_vehicle_master_series ON ag_vehicle_master (series_id);
CREATE INDEX idx_ag_vehicle_master_platform ON ag_vehicle_master (platform_id);
CREATE INDEX idx_ag_vehicle_master_trim ON ag_vehicle_master (trim_id);
CREATE INDEX idx_ag_vehicle_master_class ON ag_vehicle_master (vehicle_class_id);
CREATE INDEX idx_ag_vehicle_master_condition ON ag_vehicle_master (vehicle_condition_id);
CREATE INDEX idx_ag_vehicle_master_listing_status ON ag_vehicle_master (listing_status_id);
CREATE INDEX idx_ag_vehicle_master_body_style ON ag_vehicle_master (body_style_id);
CREATE INDEX idx_ag_vehicle_master_engine_type ON ag_vehicle_master (engine_type_id);
CREATE INDEX idx_ag_vehicle_master_transmission_type ON ag_vehicle_master (transmission_type_id);
CREATE INDEX idx_ag_vehicle_master_drive_type ON ag_vehicle_master (drive_type_id);
CREATE INDEX idx_ag_vehicle_master_fuel_type ON ag_vehicle_master (fuel_type_id);
CREATE INDEX idx_ag_vehicle_master_market ON ag_vehicle_master (market_id);
CREATE INDEX idx_ag_vehicle_master_currency ON ag_vehicle_master (currency_id);
CREATE INDEX idx_ag_vehicle_master_plant ON ag_vehicle_master (manufacturing_plant_id);
CREATE INDEX idx_ag_vehicle_master_country ON ag_vehicle_master (country_id);
CREATE INDEX idx_ag_vehicle_master_province ON ag_vehicle_master (province_id);
CREATE INDEX idx_ag_vehicle_master_city ON ag_vehicle_master (city_id);
CREATE INDEX idx_ag_vehicle_master_import_country ON ag_vehicle_master (import_country_id);
CREATE INDEX idx_ag_vehicle_master_exterior_color ON ag_vehicle_master (exterior_color_id);
CREATE INDEX idx_ag_vehicle_master_interior_color ON ag_vehicle_master (interior_color_id);
CREATE INDEX idx_ag_vehicle_master_identity ON ag_vehicle_master (identity_state, lifecycle_state);
CREATE INDEX idx_ag_vehicle_master_duplicate_group ON ag_vehicle_master (duplicate_group_hash);
CREATE INDEX idx_ag_vehicle_master_vin_normalized ON ag_vehicle_master (vin_normalized);
CREATE INDEX idx_ag_vehicle_master_identity_lookup ON ag_vehicle_master (manufacturer_id, model_id, trim_id, model_year, market_id);
CREATE INDEX idx_ag_vehicle_master_state ON ag_vehicle_master (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_map_vehicle ON ag_vehicle_map (vehicle_id);
CREATE INDEX idx_ag_vehicle_map_dealer ON ag_vehicle_map (dealer_id);
CREATE INDEX idx_ag_vehicle_map_external_vin ON ag_vehicle_map (external_vin);
CREATE INDEX idx_ag_vehicle_map_status ON ag_vehicle_map (sync_status, mapping_status, source_priority, source_rank, is_primary);
CREATE INDEX idx_ag_vehicle_map_hashes ON ag_vehicle_map (provider_record_hash, payload_hash, duplicate_group_hash);
CREATE INDEX idx_ag_vehicle_map_state ON ag_vehicle_map (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_feature_state ON ag_vehicle_feature (source_system, is_active, is_deleted, is_verified, version);
CREATE INDEX idx_ag_vehicle_feature_category ON ag_vehicle_feature (feature_category, feature_group);

CREATE INDEX idx_ag_vehicle_feature_value_dealer ON ag_vehicle_feature_value (dealer_id);
CREATE INDEX idx_ag_vehicle_feature_value_dealer_map ON ag_vehicle_feature_value (dealer_map_id);
CREATE INDEX idx_ag_vehicle_feature_value_provider ON ag_vehicle_feature_value (provider_id);

CREATE INDEX idx_ag_vehicle_specification_dealer ON ag_vehicle_specification (dealer_id);
CREATE INDEX idx_ag_vehicle_specification_dealer_map ON ag_vehicle_specification (dealer_map_id);
CREATE INDEX idx_ag_vehicle_specification_provider ON ag_vehicle_specification (provider_id);
CREATE INDEX idx_ag_vehicle_specification_generation ON ag_vehicle_specification (generation_id);
CREATE INDEX idx_ag_vehicle_specification_platform ON ag_vehicle_specification (platform_id);
CREATE INDEX idx_ag_vehicle_specification_series ON ag_vehicle_specification (series_id);
CREATE INDEX idx_ag_vehicle_specification_plant ON ag_vehicle_specification (manufacturing_plant_id);
CREATE INDEX idx_ag_vehicle_specification_country ON ag_vehicle_specification (production_country_id);
CREATE INDEX idx_ag_vehicle_specification_market ON ag_vehicle_specification (market_id);
CREATE INDEX idx_ag_vehicle_specification_class ON ag_vehicle_specification (vehicle_class_id);

CREATE INDEX idx_ag_vehicle_engine_dealer ON ag_vehicle_engine (dealer_id);
CREATE INDEX idx_ag_vehicle_engine_dealer_map ON ag_vehicle_engine (dealer_map_id);
CREATE INDEX idx_ag_vehicle_engine_provider ON ag_vehicle_engine (provider_id);
CREATE INDEX idx_ag_vehicle_engine_fuel_type ON ag_vehicle_engine (fuel_type_id);

CREATE INDEX idx_ag_vehicle_transmission_dealer ON ag_vehicle_transmission (dealer_id);
CREATE INDEX idx_ag_vehicle_transmission_dealer_map ON ag_vehicle_transmission (dealer_map_id);
CREATE INDEX idx_ag_vehicle_transmission_provider ON ag_vehicle_transmission (provider_id);

CREATE INDEX idx_ag_vehicle_fuel_economy_dealer ON ag_vehicle_fuel_economy (dealer_id);
CREATE INDEX idx_ag_vehicle_fuel_economy_dealer_map ON ag_vehicle_fuel_economy (dealer_map_id);
CREATE INDEX idx_ag_vehicle_fuel_economy_provider ON ag_vehicle_fuel_economy (provider_id);

CREATE INDEX idx_ag_vehicle_image_dealer ON ag_vehicle_image (dealer_id);
CREATE INDEX idx_ag_vehicle_image_dealer_map ON ag_vehicle_image (dealer_map_id);
CREATE INDEX idx_ag_vehicle_image_provider ON ag_vehicle_image (provider_id);
CREATE INDEX idx_ag_vehicle_image_hash ON ag_vehicle_image (image_hash);

CREATE INDEX idx_ag_vehicle_price_dealer ON ag_vehicle_price (dealer_id);
CREATE INDEX idx_ag_vehicle_price_dealer_map ON ag_vehicle_price (dealer_map_id);
CREATE INDEX idx_ag_vehicle_price_provider ON ag_vehicle_price (provider_id);
CREATE INDEX idx_ag_vehicle_price_market ON ag_vehicle_price (market_id);
CREATE INDEX idx_ag_vehicle_price_province ON ag_vehicle_price (province_id);
CREATE INDEX idx_ag_vehicle_price_currency ON ag_vehicle_price (currency_id);
CREATE INDEX idx_ag_vehicle_price_effective_date ON ag_vehicle_price (effective_date);
CREATE INDEX idx_ag_vehicle_price_status ON ag_vehicle_price (price_status, price_type, source_system);

CREATE INDEX idx_ag_vehicle_price_history_price ON ag_vehicle_price_history (price_id);
CREATE INDEX idx_ag_vehicle_price_history_vehicle ON ag_vehicle_price_history (vehicle_id);
CREATE INDEX idx_ag_vehicle_price_history_dealer ON ag_vehicle_price_history (dealer_id);
CREATE INDEX idx_ag_vehicle_price_history_provider ON ag_vehicle_price_history (provider_id);
CREATE INDEX idx_ag_vehicle_price_history_effective_date ON ag_vehicle_price_history (effective_date);
CREATE INDEX idx_ag_vehicle_price_history_state ON ag_vehicle_price_history (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_inventory_dealer ON ag_inventory (dealer_id);
CREATE INDEX idx_ag_inventory_dealer_map ON ag_inventory (dealer_map_id);
CREATE INDEX idx_ag_inventory_provider ON ag_inventory (provider_id);
CREATE INDEX idx_ag_inventory_condition ON ag_inventory (condition_id);
CREATE INDEX idx_ag_inventory_listing_status ON ag_inventory (listing_status_id);
CREATE INDEX idx_ag_inventory_stock_number ON ag_inventory (stock_number);
CREATE INDEX idx_ag_inventory_status ON ag_inventory (availability_status, vin_status, source_system);

CREATE INDEX idx_ag_inventory_history_inventory ON ag_inventory_history (inventory_id);
CREATE INDEX idx_ag_inventory_history_vehicle ON ag_inventory_history (vehicle_id);
CREATE INDEX idx_ag_inventory_history_dealer ON ag_inventory_history (dealer_id);
CREATE INDEX idx_ag_inventory_history_provider ON ag_inventory_history (provider_id);
CREATE INDEX idx_ag_inventory_history_event_at ON ag_inventory_history (event_at);
CREATE INDEX idx_ag_inventory_history_state ON ag_inventory_history (source_system, is_active, is_deleted, is_verified, version);

CREATE INDEX idx_ag_vehicle_location_dealer ON ag_vehicle_location (dealer_id);
CREATE INDEX idx_ag_vehicle_location_dealer_map ON ag_vehicle_location (dealer_map_id);
CREATE INDEX idx_ag_vehicle_location_provider ON ag_vehicle_location (provider_id);
CREATE INDEX idx_ag_vehicle_location_market ON ag_vehicle_location (market_id);
CREATE INDEX idx_ag_vehicle_location_country ON ag_vehicle_location (country_id);
CREATE INDEX idx_ag_vehicle_location_province ON ag_vehicle_location (province_id);
CREATE INDEX idx_ag_vehicle_location_city ON ag_vehicle_location (city_id);
CREATE INDEX idx_ag_vehicle_location_geohash ON ag_vehicle_location (geohash);
CREATE INDEX idx_ag_vehicle_location_postal_code ON ag_vehicle_location (postal_code);

CREATE INDEX idx_ag_vehicle_metadata_dealer ON ag_vehicle_metadata (dealer_id);
CREATE INDEX idx_ag_vehicle_metadata_dealer_map ON ag_vehicle_metadata (dealer_map_id);
CREATE INDEX idx_ag_vehicle_metadata_provider ON ag_vehicle_metadata (provider_id);
CREATE INDEX idx_ag_vehicle_metadata_namespace ON ag_vehicle_metadata (metadata_namespace);
CREATE INDEX idx_ag_vehicle_metadata_hash ON ag_vehicle_metadata (metadata_hash);

CREATE INDEX idx_ag_vehicle_alias_dealer ON ag_vehicle_alias (dealer_id);
CREATE INDEX idx_ag_vehicle_alias_dealer_map ON ag_vehicle_alias (dealer_map_id);
CREATE INDEX idx_ag_vehicle_alias_provider ON ag_vehicle_alias (provider_id);
CREATE INDEX idx_ag_vehicle_alias_market ON ag_vehicle_alias (market_id);

CREATE INDEX idx_ag_import_batch_provider ON ag_import_batch (provider_id);
CREATE INDEX idx_ag_import_batch_dealer ON ag_import_batch (dealer_id);
CREATE INDEX idx_ag_import_batch_dealer_map ON ag_import_batch (dealer_map_id);
CREATE INDEX idx_ag_import_batch_market ON ag_import_batch (market_id);
CREATE INDEX idx_ag_import_batch_status ON ag_import_batch (batch_status, source_system);

CREATE INDEX idx_ag_import_job_batch ON ag_import_job (batch_id);
CREATE INDEX idx_ag_import_job_provider ON ag_import_job (provider_id);
CREATE INDEX idx_ag_import_job_dealer ON ag_import_job (dealer_id);
CREATE INDEX idx_ag_import_job_dealer_map ON ag_import_job (dealer_map_id);
CREATE INDEX idx_ag_import_job_market ON ag_import_job (market_id);
CREATE INDEX idx_ag_import_job_status ON ag_import_job (job_status, queue_name, source_system);

CREATE INDEX idx_ag_import_log_batch ON ag_import_log (batch_id);
CREATE INDEX idx_ag_import_log_job ON ag_import_log (job_id);
CREATE INDEX idx_ag_import_log_vehicle ON ag_import_log (vehicle_id);
CREATE INDEX idx_ag_import_log_dealer ON ag_import_log (dealer_id);
CREATE INDEX idx_ag_import_log_vehicle_map ON ag_import_log (vehicle_map_id);
CREATE INDEX idx_ag_import_log_dealer_map ON ag_import_log (dealer_map_id);
CREATE INDEX idx_ag_import_log_provider ON ag_import_log (provider_id);
CREATE INDEX idx_ag_import_log_level ON ag_import_log (log_level, log_status);
CREATE INDEX idx_ag_import_log_duplicate_hash ON ag_import_log (duplicate_hash);
CREATE INDEX idx_ag_import_log_event_at ON ag_import_log (event_at);
CREATE INDEX idx_ag_import_log_state ON ag_import_log (source_system, is_active, is_deleted, is_verified, version);

