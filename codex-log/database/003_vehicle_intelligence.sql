-- AutoGyrus v1.0
-- Module 003: Vehicle intelligence tables

CREATE TABLE IF NOT EXISTS ag_vehicle_feature (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    feature_code VARCHAR(100) NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    feature_category VARCHAR(100) NOT NULL,
    feature_group VARCHAR(100) NULL,
    feature_scope VARCHAR(50) NULL,
    description TEXT NULL,
    is_standard TINYINT(1) NOT NULL DEFAULT 0,
    is_optional TINYINT(1) NOT NULL DEFAULT 1,
    source_record_hash CHAR(64) NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_feature_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_feature_code (feature_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_feature_value (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    feature_id BIGINT UNSIGNED NULL,
    value_type VARCHAR(50) NOT NULL DEFAULT 'boolean',
    value_boolean TINYINT(1) NULL,
    value_number DECIMAL(18,4) NULL,
    value_text VARCHAR(500) NULL,
    value_json JSON NULL,
    unit_of_measure VARCHAR(50) NULL,
    evidence_text VARCHAR(500) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 100,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_feature_value_uuid (uuid),
    KEY idx_ag_vehicle_feature_value_vehicle (vehicle_id),
    KEY idx_ag_vehicle_feature_value_vehicle_map (vehicle_map_id),
    KEY idx_ag_vehicle_feature_value_feature (feature_id),
    UNIQUE KEY uq_ag_vehicle_feature_value_current (vehicle_context_id, feature_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_feature_value_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_feature_value_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_feature_value_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_feature_value_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_feature_value_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_feature_value_feature
        FOREIGN KEY (feature_id) REFERENCES ag_vehicle_feature(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_specification (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    generation_id BIGINT UNSIGNED NULL,
    platform_id BIGINT UNSIGNED NULL,
    series_id BIGINT UNSIGNED NULL,
    manufacturing_plant_id BIGINT UNSIGNED NULL,
    production_country_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    vehicle_class_id BIGINT UNSIGNED NULL,
    wheelbase_mm DECIMAL(12,2) NULL,
    length_mm DECIMAL(12,2) NULL,
    width_mm DECIMAL(12,2) NULL,
    height_mm DECIMAL(12,2) NULL,
    ground_clearance_mm DECIMAL(12,2) NULL,
    turning_radius_m DECIMAL(12,2) NULL,
    cargo_capacity_l DECIMAL(12,2) NULL,
    passenger_volume_l DECIMAL(12,2) NULL,
    curb_weight_kg DECIMAL(12,2) NULL,
    gvwr_kg DECIMAL(12,2) NULL,
    payload_kg DECIMAL(12,2) NULL,
    towing_capacity_kg DECIMAL(12,2) NULL,
    safety_rating_overall VARCHAR(100) NULL,
    safety_rating_source VARCHAR(100) NULL,
    epa_rating_overall VARCHAR(100) NULL,
    epa_city_mpg DECIMAL(10,2) NULL,
    epa_highway_mpg DECIMAL(10,2) NULL,
    epa_combined_mpg DECIMAL(10,2) NULL,
    warranty_basic_months INT UNSIGNED NULL,
    warranty_basic_km INT UNSIGNED NULL,
    warranty_powertrain_months INT UNSIGNED NULL,
    warranty_powertrain_km INT UNSIGNED NULL,
    warranty_corrosion_months INT UNSIGNED NULL,
    warranty_battery_months INT UNSIGNED NULL,
    warranty_battery_km INT UNSIGNED NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_specification_uuid (uuid),
    KEY idx_ag_vehicle_specification_vehicle (vehicle_id),
    KEY idx_ag_vehicle_specification_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_specification_current (vehicle_context_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_specification_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_generation
        FOREIGN KEY (generation_id) REFERENCES ag_vehicle_generation(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_platform
        FOREIGN KEY (platform_id) REFERENCES ag_platform(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_series
        FOREIGN KEY (series_id) REFERENCES ag_series(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_plant
        FOREIGN KEY (manufacturing_plant_id) REFERENCES ag_manufacturing_plant(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_country
        FOREIGN KEY (production_country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_specification_class
        FOREIGN KEY (vehicle_class_id) REFERENCES ag_vehicle_class(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_engine (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    engine_code VARCHAR(100) NULL,
    engine_name VARCHAR(255) NULL,
    fuel_type_id BIGINT UNSIGNED NULL,
    displacement_cc DECIMAL(12,2) NULL,
    horsepower_hp DECIMAL(12,2) NULL,
    torque_nm DECIMAL(12,2) NULL,
    compression_ratio DECIMAL(10,2) NULL,
    is_turbocharged TINYINT(1) NOT NULL DEFAULT 0,
    is_supercharged TINYINT(1) NOT NULL DEFAULT 0,
    fuel_system VARCHAR(100) NULL,
    cylinder_count INT UNSIGNED NULL,
    cylinder_layout VARCHAR(100) NULL,
    emission_standard VARCHAR(100) NULL,
    has_start_stop TINYINT(1) NOT NULL DEFAULT 0,
    has_hybrid_assist TINYINT(1) NOT NULL DEFAULT 0,
    battery_capacity_kwh DECIMAL(12,2) NULL,
    charging_type VARCHAR(100) NULL,
    motor_power_kw DECIMAL(12,2) NULL,
    motor_torque_nm DECIMAL(12,2) NULL,
    cooling_type VARCHAR(100) NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_engine_uuid (uuid),
    KEY idx_ag_vehicle_engine_vehicle (vehicle_id),
    KEY idx_ag_vehicle_engine_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_engine_current (vehicle_context_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_engine_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_engine_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_engine_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_engine_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_engine_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_engine_fuel_type
        FOREIGN KEY (fuel_type_id) REFERENCES ag_fuel_type(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_transmission (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    transmission_code VARCHAR(100) NULL,
    transmission_name VARCHAR(255) NULL,
    gear_count INT UNSIGNED NULL,
    transmission_family VARCHAR(100) NULL,
    is_manual TINYINT(1) NOT NULL DEFAULT 0,
    is_automatic TINYINT(1) NOT NULL DEFAULT 0,
    is_cvt TINYINT(1) NOT NULL DEFAULT 0,
    is_dct TINYINT(1) NOT NULL DEFAULT 0,
    has_transfer_case TINYINT(1) NOT NULL DEFAULT 0,
    axle_ratio VARCHAR(50) NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_transmission_uuid (uuid),
    KEY idx_ag_vehicle_transmission_vehicle (vehicle_id),
    KEY idx_ag_vehicle_transmission_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_transmission_current (vehicle_context_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_transmission_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_transmission_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_transmission_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_transmission_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_transmission_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_fuel_economy (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    city_l_per_100km DECIMAL(12,2) NULL,
    highway_l_per_100km DECIMAL(12,2) NULL,
    combined_l_per_100km DECIMAL(12,2) NULL,
    city_mpg DECIMAL(12,2) NULL,
    highway_mpg DECIMAL(12,2) NULL,
    combined_mpg DECIMAL(12,2) NULL,
    fuel_tank_l DECIMAL(12,2) NULL,
    electric_range_km DECIMAL(12,2) NULL,
    hybrid_range_km DECIMAL(12,2) NULL,
    battery_range_km DECIMAL(12,2) NULL,
    mpge DECIMAL(12,2) NULL,
    consumption_l_per_100km DECIMAL(12,2) NULL,
    energy_consumption_kwh_per_100km DECIMAL(12,2) NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_fuel_economy_uuid (uuid),
    KEY idx_ag_vehicle_fuel_economy_vehicle (vehicle_id),
    KEY idx_ag_vehicle_fuel_economy_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_fuel_economy_current (vehicle_context_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_fuel_economy_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_fuel_economy_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_fuel_economy_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_fuel_economy_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_fuel_economy_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_image (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    image_role VARCHAR(50) NOT NULL DEFAULT 'gallery',
    image_kind VARCHAR(50) NOT NULL DEFAULT 'original',
    original_image_url VARCHAR(500) NOT NULL,
    thumbnail_image_url VARCHAR(500) NULL,
    gallery_image_url VARCHAR(500) NULL,
    alt_text VARCHAR(255) NULL,
    caption VARCHAR(500) NULL,
    resolution_width INT UNSIGNED NULL,
    resolution_height INT UNSIGNED NULL,
    file_size_bytes BIGINT UNSIGNED NULL,
    image_hash CHAR(64) NULL,
    source_media_id VARCHAR(191) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 100,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_provider_image TINYINT(1) NOT NULL DEFAULT 0,
    is_ai_image TINYINT(1) NOT NULL DEFAULT 0,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_image_uuid (uuid),
    KEY idx_ag_vehicle_image_vehicle (vehicle_id),
    KEY idx_ag_vehicle_image_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_image_current (vehicle_context_id, provider_id, image_role, image_hash, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_image_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_image_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_image_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_image_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_image_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_price (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    province_id BIGINT UNSIGNED NULL,
    currency_id BIGINT UNSIGNED NULL,
    price_type VARCHAR(50) NOT NULL DEFAULT 'current',
    current_price DECIMAL(14,2) NULL,
    msrp DECIMAL(14,2) NULL,
    sale_price DECIMAL(14,2) NULL,
    market_price DECIMAL(14,2) NULL,
    dealer_price DECIMAL(14,2) NULL,
    effective_date DATE NULL,
    expiry_date DATE NULL,
    price_status VARCHAR(50) NOT NULL DEFAULT 'active',
    source_record_hash CHAR(64) NULL,
    price_hash CHAR(64) NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    dealer_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_price_uuid (uuid),
    KEY idx_ag_vehicle_price_vehicle (vehicle_id),
    KEY idx_ag_vehicle_price_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_price_current (vehicle_context_id, dealer_context_id, provider_id, market_id, province_id, currency_id, price_type, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_price_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_province
        FOREIGN KEY (province_id) REFERENCES ag_state_province(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_currency
        FOREIGN KEY (currency_id) REFERENCES ag_currency(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_price_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    price_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    old_price DECIMAL(14,2) NULL,
    new_price DECIMAL(14,2) NULL,
    old_currency_id BIGINT UNSIGNED NULL,
    new_currency_id BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    price_status VARCHAR(50) NULL,
    effective_date DATE NULL,
    source_record_hash CHAR(64) NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_price_history_uuid (uuid),
    CONSTRAINT fk_ag_vehicle_price_history_price
        FOREIGN KEY (price_id) REFERENCES ag_vehicle_price(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_history_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_history_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_history_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_history_old_currency
        FOREIGN KEY (old_currency_id) REFERENCES ag_currency(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_price_history_new_currency
        FOREIGN KEY (new_currency_id) REFERENCES ag_currency(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_inventory (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    stock_number VARCHAR(100) NULL,
    condition_id BIGINT UNSIGNED NULL,
    availability_status VARCHAR(50) NOT NULL DEFAULT 'available',
    days_on_lot INT UNSIGNED NULL,
    arrival_date DATE NULL,
    vin_status VARCHAR(50) NULL,
    listing_status_id BIGINT UNSIGNED NULL,
    lot_status VARCHAR(50) NULL,
    odometer_km DECIMAL(12,2) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_certified_pre_owned TINYINT(1) NOT NULL DEFAULT 0,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    dealer_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_inventory_uuid (uuid),
    KEY idx_ag_inventory_vehicle (vehicle_id),
    KEY idx_ag_inventory_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_inventory_current (vehicle_context_id, dealer_context_id, provider_id, source_system, current_record_guard),
    CONSTRAINT fk_ag_inventory_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_condition
        FOREIGN KEY (condition_id) REFERENCES ag_vehicle_condition(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_listing_status
        FOREIGN KEY (listing_status_id) REFERENCES ag_listing_status(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_inventory_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    inventory_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    changed_field VARCHAR(100) NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    reason_code VARCHAR(100) NULL,
    source_record_hash CHAR(64) NULL,
    event_at DATETIME NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_inventory_history_uuid (uuid),
    CONSTRAINT fk_ag_inventory_history_inventory
        FOREIGN KEY (inventory_id) REFERENCES ag_inventory(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_history_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_history_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_inventory_history_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_location (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NULL,
    province_id BIGINT UNSIGNED NULL,
    city_id BIGINT UNSIGNED NULL,
    postal_code VARCHAR(30) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    geohash VARCHAR(20) NULL,
    location_type VARCHAR(50) NOT NULL DEFAULT 'dealer',
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    dealer_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_location_uuid (uuid),
    KEY idx_ag_vehicle_location_vehicle (vehicle_id),
    KEY idx_ag_vehicle_location_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_location_current (vehicle_context_id, dealer_context_id, provider_id, location_type, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_location_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_country
        FOREIGN KEY (country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_province
        FOREIGN KEY (province_id) REFERENCES ag_state_province(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_location_city
        FOREIGN KEY (city_id) REFERENCES ag_city(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_metadata (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    metadata_namespace VARCHAR(100) NOT NULL,
    metadata_schema_version VARCHAR(50) NULL,
    metadata JSON NOT NULL,
    metadata_hash CHAR(64) NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    dealer_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_metadata_uuid (uuid),
    KEY idx_ag_vehicle_metadata_vehicle (vehicle_id),
    KEY idx_ag_vehicle_metadata_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_metadata_current (vehicle_context_id, dealer_context_id, provider_id, metadata_namespace, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_metadata_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_metadata_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_metadata_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_metadata_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_metadata_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_alias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    alias_type VARCHAR(50) NOT NULL,
    alias_name VARCHAR(255) NOT NULL,
    alias_name_normalized VARCHAR(255) NOT NULL,
    locale_code VARCHAR(20) NULL,
    market_id BIGINT UNSIGNED NULL,
    source_record_hash CHAR(64) NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    vehicle_context_id BIGINT UNSIGNED NULL,
    dealer_context_id BIGINT UNSIGNED NULL,
    current_record_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_current = 1 AND is_active = 1 AND is_deleted = 0 THEN 1 ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_alias_uuid (uuid),
    KEY idx_ag_vehicle_alias_vehicle (vehicle_id),
    KEY idx_ag_vehicle_alias_vehicle_map (vehicle_map_id),
    UNIQUE KEY uq_ag_vehicle_alias_current (vehicle_context_id, dealer_context_id, provider_id, alias_type, alias_name_normalized, locale_code, source_system, current_record_guard),
    CONSTRAINT fk_ag_vehicle_alias_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_alias_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_alias_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_alias_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_alias_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_alias_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_import_batch (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    batch_code VARCHAR(100) NOT NULL,
    batch_type VARCHAR(50) NOT NULL,
    provider_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    import_source VARCHAR(100) NULL,
    source_file_name VARCHAR(255) NULL,
    source_file_uri VARCHAR(500) NULL,
    source_file_hash CHAR(64) NULL,
    rows_total INT UNSIGNED NOT NULL DEFAULT 0,
    rows_processed INT UNSIGNED NOT NULL DEFAULT 0,
    rows_inserted INT UNSIGNED NOT NULL DEFAULT 0,
    rows_updated INT UNSIGNED NOT NULL DEFAULT 0,
    rows_ignored INT UNSIGNED NOT NULL DEFAULT 0,
    rows_duplicates INT UNSIGNED NOT NULL DEFAULT 0,
    rows_errors INT UNSIGNED NOT NULL DEFAULT 0,
    rows_warnings INT UNSIGNED NOT NULL DEFAULT 0,
    batch_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    duration_seconds INT UNSIGNED NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_import_batch_uuid (uuid),
    UNIQUE KEY uq_ag_import_batch_code (batch_code),
    CONSTRAINT fk_ag_import_batch_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_batch_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_batch_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_batch_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_import_job (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    job_code VARCHAR(100) NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    queue_name VARCHAR(100) NULL,
    job_status VARCHAR(50) NOT NULL DEFAULT 'queued',
    provider_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
    rows_total INT UNSIGNED NOT NULL DEFAULT 0,
    rows_success INT UNSIGNED NOT NULL DEFAULT 0,
    rows_failed INT UNSIGNED NOT NULL DEFAULT 0,
    rows_duplicates INT UNSIGNED NOT NULL DEFAULT 0,
    rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    duration_seconds INT UNSIGNED NULL,
    next_run_at DATETIME NULL,
    error_summary TEXT NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_import_job_uuid (uuid),
    UNIQUE KEY uq_ag_import_job_code (job_code),
    CONSTRAINT fk_ag_import_job_batch
        FOREIGN KEY (batch_id) REFERENCES ag_import_batch(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_job_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_job_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_job_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_job_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_import_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    vehicle_map_id BIGINT UNSIGNED NULL,
    dealer_map_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    source_row_number INT UNSIGNED NULL,
    log_level VARCHAR(20) NOT NULL DEFAULT 'info',
    log_code VARCHAR(100) NULL,
    log_message TEXT NOT NULL,
    event_type VARCHAR(100) NULL,
    duplicate_hash CHAR(64) NULL,
    skip_reason VARCHAR(255) NULL,
    raw_payload JSON NULL,
    normalized_payload JSON NULL,
    log_status VARCHAR(50) NOT NULL DEFAULT 'open',
    event_at DATETIME NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_import_log_uuid (uuid),
    CONSTRAINT fk_ag_import_log_batch
        FOREIGN KEY (batch_id) REFERENCES ag_import_batch(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_job
        FOREIGN KEY (job_id) REFERENCES ag_import_job(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_vehicle_map
        FOREIGN KEY (vehicle_map_id) REFERENCES ag_vehicle_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_dealer_map
        FOREIGN KEY (dealer_map_id) REFERENCES ag_dealer_map(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_import_log_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
