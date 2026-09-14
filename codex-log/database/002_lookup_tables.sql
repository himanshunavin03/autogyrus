-- AutoGyrus v1.0
-- Module 002: Lookup / master entity tables

CREATE TABLE IF NOT EXISTS ag_model (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    model_name VARCHAR(150) NOT NULL,
    model_slug VARCHAR(180) NOT NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_model_uuid (uuid),
    UNIQUE KEY uq_ag_model_manufacturer_slug (manufacturer_id, model_slug),
    CONSTRAINT fk_ag_model_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_platform (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    platform_code VARCHAR(100) NOT NULL,
    platform_name VARCHAR(255) NOT NULL,
    platform_family_code VARCHAR(100) NULL,
    platform_type VARCHAR(100) NULL,
    market_id BIGINT UNSIGNED NULL,
    production_start_year SMALLINT UNSIGNED NULL,
    production_end_year SMALLINT UNSIGNED NULL,
    is_global TINYINT(1) NOT NULL DEFAULT 0,
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
    UNIQUE KEY uq_ag_platform_uuid (uuid),
    UNIQUE KEY uq_ag_platform_code (manufacturer_id, platform_code, market_id),
    CONSTRAINT fk_ag_platform_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_platform_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_manufacturing_plant (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NULL,
    province_id BIGINT UNSIGNED NULL,
    city_id BIGINT UNSIGNED NULL,
    plant_code VARCHAR(100) NOT NULL,
    plant_name VARCHAR(255) NOT NULL,
    plant_type VARCHAR(100) NULL,
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
    UNIQUE KEY uq_ag_manufacturing_plant_uuid (uuid),
    UNIQUE KEY uq_ag_manufacturing_plant_code (manufacturer_id, plant_code),
    CONSTRAINT fk_ag_manufacturing_plant_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_manufacturing_plant_country
        FOREIGN KEY (country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_manufacturing_plant_province
        FOREIGN KEY (province_id) REFERENCES ag_state_province(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_manufacturing_plant_city
        FOREIGN KEY (city_id) REFERENCES ag_city(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_generation (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    model_id BIGINT UNSIGNED NULL,
    platform_id BIGINT UNSIGNED NULL,
    generation_code VARCHAR(100) NOT NULL,
    generation_name VARCHAR(255) NOT NULL,
    generation_number VARCHAR(50) NULL,
    global_model_code VARCHAR(100) NULL,
    regional_model_code VARCHAR(100) NULL,
    body_code VARCHAR(100) NULL,
    internal_manufacturer_code VARCHAR(100) NULL,
    production_start_year SMALLINT UNSIGNED NULL,
    production_end_year SMALLINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NULL,
    import_country_id BIGINT UNSIGNED NULL,
    manufacturing_plant_id BIGINT UNSIGNED NULL,
    is_current_generation TINYINT(1) NOT NULL DEFAULT 1,
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
    UNIQUE KEY uq_ag_vehicle_generation_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_generation_code (model_id, generation_code, market_id),
    CONSTRAINT fk_ag_vehicle_generation_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_model
        FOREIGN KEY (model_id) REFERENCES ag_model(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_platform
        FOREIGN KEY (platform_id) REFERENCES ag_platform(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_country
        FOREIGN KEY (country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_import_country
        FOREIGN KEY (import_country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_generation_manufacturing_plant
        FOREIGN KEY (manufacturing_plant_id) REFERENCES ag_manufacturing_plant(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_series (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    model_id BIGINT UNSIGNED NULL,
    platform_id BIGINT UNSIGNED NULL,
    generation_id BIGINT UNSIGNED NULL,
    series_code VARCHAR(100) NOT NULL,
    series_name VARCHAR(255) NOT NULL,
    series_order INT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    is_current_series TINYINT(1) NOT NULL DEFAULT 1,
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
    UNIQUE KEY uq_ag_series_uuid (uuid),
    UNIQUE KEY uq_ag_series_code (model_id, generation_id, series_code, market_id),
    CONSTRAINT fk_ag_series_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_series_model
        FOREIGN KEY (model_id) REFERENCES ag_model(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_series_platform
        FOREIGN KEY (platform_id) REFERENCES ag_platform(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_series_generation
        FOREIGN KEY (generation_id) REFERENCES ag_vehicle_generation(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_series_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_trim (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    model_id BIGINT UNSIGNED NULL,
    trim_name VARCHAR(150) NOT NULL,
    trim_slug VARCHAR(180) NOT NULL,
    source_system VARCHAR(100) NOT NULL DEFAULT 'autogyrus',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_trim_uuid (uuid),
    UNIQUE KEY uq_ag_trim_model_slug (model_id, trim_slug),
    CONSTRAINT fk_ag_trim_model
        FOREIGN KEY (model_id) REFERENCES ag_model(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_class (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    class_code VARCHAR(100) NOT NULL,
    class_name VARCHAR(150) NOT NULL,
    class_group VARCHAR(100) NULL,
    parent_class_id BIGINT UNSIGNED NULL,
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
    UNIQUE KEY uq_ag_vehicle_class_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_class_code (class_code),
    CONSTRAINT fk_ag_vehicle_class_parent
        FOREIGN KEY (parent_class_id) REFERENCES ag_vehicle_class(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_condition (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    condition_code VARCHAR(100) NOT NULL,
    condition_name VARCHAR(150) NOT NULL,
    condition_rank INT UNSIGNED NOT NULL DEFAULT 100,
    is_new_vehicle TINYINT(1) NOT NULL DEFAULT 0,
    is_used_vehicle TINYINT(1) NOT NULL DEFAULT 1,
    is_certified_vehicle TINYINT(1) NOT NULL DEFAULT 0,
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
    UNIQUE KEY uq_ag_vehicle_condition_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_condition_code (condition_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_listing_status (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    status_code VARCHAR(100) NOT NULL,
    status_name VARCHAR(150) NOT NULL,
    status_rank INT UNSIGNED NOT NULL DEFAULT 100,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    is_terminal TINYINT(1) NOT NULL DEFAULT 0,
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
    UNIQUE KEY uq_ag_listing_status_uuid (uuid),
    UNIQUE KEY uq_ag_listing_status_code (status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_color (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    color_code VARCHAR(100) NOT NULL,
    color_name VARCHAR(150) NOT NULL,
    color_family VARCHAR(100) NULL,
    color_scope VARCHAR(20) NOT NULL DEFAULT 'both',
    hex_value VARCHAR(20) NULL,
    manufacturer_color_code VARCHAR(100) NULL,
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
    UNIQUE KEY uq_ag_color_uuid (uuid),
    UNIQUE KEY uq_ag_color_code_scope (color_code, color_scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_dealer_master (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    dealer_code VARCHAR(100) NOT NULL,
    dealer_name VARCHAR(255) NOT NULL,
    dealer_legal_name VARCHAR(255) NULL,
    dealer_slug VARCHAR(255) NOT NULL,
    dealer_group_name VARCHAR(255) NULL,
    dealer_category VARCHAR(100) NULL,
    ownership_type VARCHAR(100) NULL,
    network_role VARCHAR(100) NULL,
    market_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NULL,
    province_id BIGINT UNSIGNED NULL,
    city_id BIGINT UNSIGNED NULL,
    website_url VARCHAR(500) NULL,
    phone_number VARCHAR(50) NULL,
    email_address VARCHAR(255) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    postal_code VARCHAR(30) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    timezone VARCHAR(100) NULL,
    canonical_dealer_hash CHAR(64) NOT NULL,
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
    UNIQUE KEY uq_ag_dealer_master_uuid (uuid),
    UNIQUE KEY uq_ag_dealer_master_code (dealer_code),
    UNIQUE KEY uq_ag_dealer_master_slug (dealer_slug),
    UNIQUE KEY uq_ag_dealer_master_hash (canonical_dealer_hash),
    CONSTRAINT fk_ag_dealer_master_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_dealer_master_country
        FOREIGN KEY (country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_dealer_master_province
        FOREIGN KEY (province_id) REFERENCES ag_state_province(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_dealer_master_city
        FOREIGN KEY (city_id) REFERENCES ag_city(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_provider_feature (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    provider_id BIGINT UNSIGNED NULL,
    feature_code VARCHAR(100) NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    feature_category VARCHAR(100) NOT NULL,
    support_level VARCHAR(50) NOT NULL DEFAULT 'partial',
    is_supported TINYINT(1) NOT NULL DEFAULT 0,
    feature_config JSON NULL,
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
    UNIQUE KEY uq_ag_provider_feature_uuid (uuid),
    UNIQUE KEY uq_ag_provider_feature_provider_code (provider_id, feature_code),
    CONSTRAINT fk_ag_provider_feature_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_dealer_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    dealer_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    source_system VARCHAR(100) NOT NULL,
    external_id VARCHAR(191) NOT NULL,
    external_url VARCHAR(500) NULL,
    external_reference_type VARCHAR(100) NULL,
    provider_dealer_code VARCHAR(100) NULL,
    market_id BIGINT UNSIGNED NULL,
    source_rank INT UNSIGNED NOT NULL DEFAULT 1,
    source_priority INT UNSIGNED NOT NULL DEFAULT 100,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    provider_metadata JSON NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    first_seen_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_dealer_map_uuid (uuid),
    UNIQUE KEY uq_ag_dealer_map_source_external (provider_id, source_system, external_id),
    CONSTRAINT fk_ag_dealer_map_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_dealer_map_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_dealer_map_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_master (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    canonical_identity_hash CHAR(64) NOT NULL,
    duplicate_group_hash CHAR(64) NULL,
    identity_state VARCHAR(50) NOT NULL DEFAULT 'provisional',
    lifecycle_state VARCHAR(50) NOT NULL DEFAULT 'active',
    manufacturer_id BIGINT UNSIGNED NULL,
    model_id BIGINT UNSIGNED NULL,
    generation_id BIGINT UNSIGNED NULL,
    series_id BIGINT UNSIGNED NULL,
    platform_id BIGINT UNSIGNED NULL,
    trim_id BIGINT UNSIGNED NULL,
    vehicle_class_id BIGINT UNSIGNED NULL,
    vehicle_condition_id BIGINT UNSIGNED NULL,
    listing_status_id BIGINT UNSIGNED NULL,
    body_style_id BIGINT UNSIGNED NULL,
    engine_type_id BIGINT UNSIGNED NULL,
    transmission_type_id BIGINT UNSIGNED NULL,
    drive_type_id BIGINT UNSIGNED NULL,
    fuel_type_id BIGINT UNSIGNED NULL,
    market_id BIGINT UNSIGNED NULL,
    currency_id BIGINT UNSIGNED NULL,
    manufacturing_plant_id BIGINT UNSIGNED NULL,
    country_id BIGINT UNSIGNED NULL,
    province_id BIGINT UNSIGNED NULL,
    city_id BIGINT UNSIGNED NULL,
    import_country_id BIGINT UNSIGNED NULL,
    exterior_color_id BIGINT UNSIGNED NULL,
    interior_color_id BIGINT UNSIGNED NULL,
    vin VARCHAR(50) NULL,
    vin_normalized VARCHAR(50) NULL,
    vin_wmi VARCHAR(3) NULL,
    vin_vds VARCHAR(6) NULL,
    vin_vis VARCHAR(8) NULL,
    vin_checksum_valid TINYINT(1) NOT NULL DEFAULT 0,
    model_year SMALLINT UNSIGNED NULL,
    production_year_start SMALLINT UNSIGNED NULL,
    production_year_end SMALLINT UNSIGNED NULL,
    global_model_code VARCHAR(100) NULL,
    regional_model_code VARCHAR(100) NULL,
    body_code VARCHAR(100) NULL,
    internal_manufacturer_code VARCHAR(100) NULL,
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
    UNIQUE KEY uq_ag_vehicle_master_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_master_hash (canonical_identity_hash),
    UNIQUE KEY uq_ag_vehicle_master_vin (vin),
    CONSTRAINT fk_ag_vehicle_master_manufacturer
        FOREIGN KEY (manufacturer_id) REFERENCES ag_manufacturer(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_model
        FOREIGN KEY (model_id) REFERENCES ag_model(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_generation
        FOREIGN KEY (generation_id) REFERENCES ag_vehicle_generation(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_series
        FOREIGN KEY (series_id) REFERENCES ag_series(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_platform
        FOREIGN KEY (platform_id) REFERENCES ag_platform(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_trim
        FOREIGN KEY (trim_id) REFERENCES ag_trim(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_vehicle_class
        FOREIGN KEY (vehicle_class_id) REFERENCES ag_vehicle_class(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_vehicle_condition
        FOREIGN KEY (vehicle_condition_id) REFERENCES ag_vehicle_condition(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_listing_status
        FOREIGN KEY (listing_status_id) REFERENCES ag_listing_status(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_body_style
        FOREIGN KEY (body_style_id) REFERENCES ag_body_style(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_engine_type
        FOREIGN KEY (engine_type_id) REFERENCES ag_engine_type(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_transmission_type
        FOREIGN KEY (transmission_type_id) REFERENCES ag_transmission_type(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_drive_type
        FOREIGN KEY (drive_type_id) REFERENCES ag_drive_type(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_fuel_type
        FOREIGN KEY (fuel_type_id) REFERENCES ag_fuel_type(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_market
        FOREIGN KEY (market_id) REFERENCES ag_market(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_currency
        FOREIGN KEY (currency_id) REFERENCES ag_currency(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_manufacturing_plant
        FOREIGN KEY (manufacturing_plant_id) REFERENCES ag_manufacturing_plant(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_country
        FOREIGN KEY (country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_province
        FOREIGN KEY (province_id) REFERENCES ag_state_province(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_city
        FOREIGN KEY (city_id) REFERENCES ag_city(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_import_country
        FOREIGN KEY (import_country_id) REFERENCES ag_country(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_exterior_color
        FOREIGN KEY (exterior_color_id) REFERENCES ag_color(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_master_interior_color
        FOREIGN KEY (interior_color_id) REFERENCES ag_color(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ag_vehicle_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    dealer_id BIGINT UNSIGNED NULL,
    provider_id BIGINT UNSIGNED NULL,
    source_system VARCHAR(100) NOT NULL,
    source_entity_type VARCHAR(100) NULL,
    external_id VARCHAR(191) NOT NULL,
    external_sub_id VARCHAR(191) NULL,
    external_vin VARCHAR(50) NULL,
    external_url VARCHAR(500) NULL,
    external_reference_type VARCHAR(100) NULL,
    provider_record_hash CHAR(64) NULL,
    payload_hash CHAR(64) NULL,
    duplicate_group_hash CHAR(64) NULL,
    sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    mapping_status VARCHAR(50) NOT NULL DEFAULT 'active',
    source_rank INT UNSIGNED NOT NULL DEFAULT 1,
    source_priority INT UNSIGNED NOT NULL DEFAULT 100,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    provider_metadata JSON NULL,
    metadata JSON NULL,
    future_reserved JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    change_reason VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    last_provider_update DATETIME NULL,
    data_quality_score DECIMAL(5,2) NULL,
    confidence_score DECIMAL(5,2) NULL,
    first_seen_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ag_vehicle_map_uuid (uuid),
    UNIQUE KEY uq_ag_vehicle_map_source_external (provider_id, source_system, external_id),
    CONSTRAINT fk_ag_vehicle_map_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES ag_vehicle_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_map_dealer
        FOREIGN KEY (dealer_id) REFERENCES ag_dealer_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ag_vehicle_map_provider
        FOREIGN KEY (provider_id) REFERENCES ag_provider_master(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
