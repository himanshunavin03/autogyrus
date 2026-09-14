<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Import_Service
{
    private static $importing_wordpress = false;

    public static function register()
    {
        add_action('admin_post_autogyrus_toyota_import', array(__CLASS__, 'handle_toyota_import'));
        add_action('admin_post_autogyrus_toyota_smoke_test', array(__CLASS__, 'handle_smoke_test'));
        add_action('autogyrus_vehicle_imported', array(__CLASS__, 'sync_wordpress_vehicle_from_import'), 10, 3);
    }

    public static function handle_toyota_import()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        check_admin_referer('autogyrus_toyota_import');

        $payload = self::read_request_payload();
        if (empty($payload)) {
            wp_safe_redirect(add_query_arg(array('autogyrus_import_error' => 'missing_payload'), admin_url('admin.php?page=autogyrus-toyota')));
            exit;
        }

        $result = self::import_provider_payload('toyota', $payload, array('source_system' => 'toyota', 'import_mode' => 'manual'));
        wp_safe_redirect(add_query_arg(array(
            'autogyrus_import_status' => $result['status'],
            'autogyrus_batch' => $result['batch_code'],
        ), admin_url('admin.php?page=autogyrus-toyota')));
        exit;
    }

    public static function handle_smoke_test()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        check_admin_referer('autogyrus_toyota_smoke_test');

        $provider = AutoGyrus_Provider_Registry::get_provider('toyota');
        $payload = method_exists($provider, 'sample_payload') ? $provider->sample_payload() : array();
        $result = self::import_provider_payload('toyota', $payload, array('dry_run' => true, 'source_system' => 'toyota', 'import_mode' => 'smoke'));

        wp_safe_redirect(add_query_arg(array(
            'autogyrus_smoke_test' => $result['status'],
            'autogyrus_smoke_message' => rawurlencode($result['message']),
        ), admin_url('admin.php?page=autogyrus-toyota')));
        exit;
    }

    public static function read_request_payload()
    {
        if (! empty($_FILES['autogyrus_toyota_file']['tmp_name']) && is_uploaded_file($_FILES['autogyrus_toyota_file']['tmp_name'])) {
            $contents = file_get_contents($_FILES['autogyrus_toyota_file']['tmp_name']);
            $json = json_decode((string) $contents, true);
            if (is_array($json)) {
                return $json;
            }
        }

        $raw_payload = wp_unslash($_POST['autogyrus_toyota_payload'] ?? '');
        $json = json_decode((string) $raw_payload, true);

        return is_array($json) ? $json : array();
    }

    public static function import_provider_payload($provider_code, array $payload, array $options = array())
    {
        $provider = AutoGyrus_Provider_Registry::get_provider($provider_code);
        if (! $provider) {
            return array(
                'status' => 'error',
                'message' => 'Unknown provider.',
                'batch_code' => '',
            );
        }

        $normalized = $provider->normalize_payload($payload);
        if (! empty($options['dry_run'])) {
            return array(
                'status' => 'success',
                'message' => 'Dry run completed successfully.',
                'batch_code' => '',
                'job_code' => '',
                'imported' => count($normalized['records']),
                'updated' => 0,
                'ignored' => 0,
                'warnings' => 0,
                'errors' => 0,
                'normalized' => $normalized,
            );
        }

        if (empty($normalized['records'])) {
            return array(
                'status' => 'warning',
                'message' => 'No vehicle records found in payload.',
                'batch_code' => '',
                'normalized' => $normalized,
            );
        }

        $batch_code = self::generate_code('BATCH');
        $job_code = self::generate_code('JOB');
        $provider_id = self::resolve_provider_master($provider);
        $dealer_context = self::resolve_provider_dealer($provider_id, $normalized['dealer'], $options);
        $batch_id = self::create_batch($batch_code, $provider_id, $dealer_context['dealer_id'], $dealer_context['dealer_map_id'], $normalized, $options);
        $job_id = self::create_job($job_code, $batch_id, $provider_id, $dealer_context['dealer_id'], $dealer_context['dealer_map_id'], $normalized, $options);

        $result = array(
            'status' => 'success',
            'message' => 'Import completed.',
            'batch_code' => $batch_code,
            'job_code' => $job_code,
            'batch_id' => $batch_id,
            'job_id' => $job_id,
            'imported' => 0,
            'updated' => 0,
            'ignored' => 0,
            'warnings' => 0,
            'errors' => 0,
            'normalized' => $normalized,
        );

        foreach ($normalized['records'] as $record) {
            if (! empty($options['dry_run'])) {
                $result['imported']++;
                continue;
            }

            $row = self::process_record($provider, $provider_id, $dealer_context, $batch_id, $job_id, $record, $options);
            foreach (array('imported', 'updated', 'ignored', 'warnings', 'errors') as $metric) {
                if (! empty($row[$metric])) {
                    $result[$metric] += (int) $row[$metric];
                }
            }
        }

        if (! empty($options['dry_run'])) {
            $result['message'] = 'Dry run completed successfully.';
            return $result;
        }

        self::finalize_batch($batch_id, $result);
        self::finalize_job($job_id, $result);

        return $result;
    }

    public static function process_record(AutoGyrus_Provider_Interface $provider, $provider_id, array $dealer_context, $batch_id, $job_id, array $record, array $options = array())
    {
        global $wpdb;

        $started_at = microtime(true);
        $counts = array('imported' => 0, 'updated' => 0, 'ignored' => 0, 'warnings' => 0, 'errors' => 0);

        $validation = self::validate_record($record);
        if (false === $validation['valid']) {
            self::insert_log($batch_id, $job_id, $provider_id, $dealer_context['dealer_id'], $dealer_context['dealer_map_id'], $record, 'error', 'validation_failed', $validation['message'], $record['external_id'] ?? '', null);
            self::increment_batch_counts($batch_id, array('rows_processed' => 1, 'rows_errors' => 1));
            return array_merge($counts, array('errors' => 1));
        }

        $wpdb->query('START TRANSACTION');

        try {
            $context = self::resolve_lookups($provider_id, $dealer_context, $record, $options);
            $vehicle_master = self::resolve_vehicle_master($provider_id, $dealer_context, $record, $context, $options);
            $vehicle_map = self::resolve_vehicle_map($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $record, $context, $options);

            self::upsert_inventory($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_price($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_location($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_metadata($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_alias($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_specification($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_engine($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_transmission($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_fuel_economy($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_features($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);
            self::upsert_images($provider_id, $dealer_context, $vehicle_master['vehicle_id'], $vehicle_map['vehicle_map_id'], $record, $context, $options);

            self::insert_log($batch_id, $job_id, $provider_id, $dealer_context['dealer_id'], $dealer_context['dealer_map_id'], $record, 'info', 'record_imported', 'Vehicle imported successfully.', $record['external_id'] ?? '', $vehicle_master['vehicle_id']);
            self::increment_batch_counts($batch_id, array('rows_processed' => 1, 'rows_inserted' => 1));
            $wpdb->query('COMMIT');

            do_action('autogyrus_vehicle_imported', $vehicle_master['vehicle_id'], $record, array(
                'provider_id' => $provider_id,
                'dealer_id' => $dealer_context['dealer_id'],
                'dealer_map_id' => $dealer_context['dealer_map_id'],
                'vehicle_map_id' => $vehicle_map['vehicle_map_id'],
                'source_system' => $provider->get_code(),
            ));

            $counts['imported'] = 1;
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');
            self::insert_log($batch_id, $job_id, $provider_id, $dealer_context['dealer_id'], $dealer_context['dealer_map_id'], $record, 'error', 'record_failed', $exception->getMessage(), $record['external_id'] ?? '', null);
            self::increment_batch_counts($batch_id, array('rows_processed' => 1, 'rows_errors' => 1));
            $counts['errors'] = 1;
        }

        $counts['duration_seconds'] = (int) max(0, round(microtime(true) - $started_at));

        return $counts;
    }

    public static function validate_record(array $record)
    {
        if (empty($record['vin']) && empty($record['external_id'])) {
            return array('valid' => false, 'message' => 'VIN or external ID is required.');
        }

        if (empty($record['make']) && empty($record['model'])) {
            return array('valid' => false, 'message' => 'Make or model is required.');
        }

        return array('valid' => true, 'message' => '');
    }

    public static function resolve_provider_master(AutoGyrus_Provider_Interface $provider)
    {
        global $wpdb;

        $table = 'ag_provider_master';
        $existing_id = self::find_id($table, array('provider_code' => $provider->get_code()));
        $existing = $existing_id ? self::find_row($table, array('id' => $existing_id)) : null;
        $provider_category = 'wordpress' === $provider->get_code() ? 'internal' : 'oem';
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'provider_code' => $provider->get_code(),
            'provider_name' => $provider->get_name(),
            'provider_category' => $provider_category,
            'provider_subcategory' => 'inventory',
            'api_base_url' => null,
            'documentation_url' => null,
            'auth_type' => 'wordpress' === $provider->get_code() ? 'local' : 'none',
            'import_mode' => 'wordpress' === $provider->get_code() ? 'manual' : 'api',
            'provider_priority' => 'wordpress' === $provider->get_code() ? 1 : 10,
            'version_major' => 1,
            'version_minor' => 0,
            'provider_metadata' => wp_json_encode(array('registered_by' => 'auto_import')),
            'metadata' => wp_json_encode(array('name' => $provider->get_name())),
            'future_reserved' => wp_json_encode(array()),
            'source_system' => $provider->get_code(),
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'provider_registration',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($existing_id) {
            if ($existing) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
            }
            $wpdb->update($table, $data, array('id' => $existing_id));
            return (int) $existing_id;
        }

        $wpdb->insert($table, $data);
        return (int) $wpdb->insert_id;
    }

    public static function resolve_provider_dealer($provider_id, array $dealer, array $options = array())
    {
        if (empty($dealer)) {
            $dealer = array(
                'dealer_external_id' => 'default-toyota-dealer',
                'dealer_name' => 'Toyota Dealer',
                'dealer_slug' => 'toyota-dealer',
                'canonical_dealer_hash' => hash('sha256', 'toyota|dealer'),
            );
        }

        $dealer_id = self::find_id('ag_dealer_master', array('canonical_dealer_hash' => $dealer['canonical_dealer_hash']));
        $dealer_map_id = self::find_id('ag_dealer_map', array(
            'provider_id' => $provider_id,
            'source_system' => $options['source_system'] ?? 'toyota',
            'external_id' => $dealer['dealer_external_id'],
        ));

        $country_id = self::resolve_country($dealer['country'] ?? 'CA', $dealer['country'] ?? 'Canada');
        $province_id = self::resolve_province($country_id, $dealer['province'] ?? 'AB', $dealer['province'] ?? 'Alberta');
        $city_id = self::resolve_city($country_id, $province_id, $dealer['city'] ?? 'Calgary');
        $market_id = self::resolve_market($dealer['country'] ?? 'CA', $country_id, $dealer['country'] ?? 'Canada');

        $dealer_data = array(
            'uuid' => wp_generate_uuid4(),
            'dealer_code' => $dealer['dealer_external_id'] ?: 'toyota-dealer-' . substr($dealer['canonical_dealer_hash'], 0, 12),
            'dealer_name' => $dealer['dealer_name'],
            'dealer_legal_name' => $dealer['dealer_legal_name'] ?: $dealer['dealer_name'],
            'dealer_slug' => $dealer['dealer_slug'] ?: sanitize_title($dealer['dealer_name']),
            'dealer_group_name' => $dealer['dealer_group_name'] ?? null,
            'dealer_category' => 'franchise',
            'ownership_type' => 'franchise',
            'network_role' => 'dealer',
            'market_id' => $market_id,
            'country_id' => $country_id,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'website_url' => $dealer['website_url'] ?: null,
            'phone_number' => $dealer['phone_number'] ?: null,
            'email_address' => $dealer['email_address'] ?: null,
            'address_line1' => $dealer['address_line1'] ?: null,
            'address_line2' => $dealer['address_line2'] ?: null,
            'postal_code' => $dealer['postal_code'] ?: null,
            'latitude' => $dealer['latitude'],
            'longitude' => $dealer['longitude'],
            'timezone' => $dealer['timezone'] ?: null,
            'canonical_dealer_hash' => $dealer['canonical_dealer_hash'],
            'metadata' => wp_json_encode($dealer['metadata'] ?? array()),
            'future_reserved' => wp_json_encode(array()),
            'source_system' => $options['source_system'] ?? 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'dealer_resolution',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($dealer_id) {
            $existing = self::find_row('ag_dealer_master', array('id' => $dealer_id));
            if ($existing) {
                $dealer_data['uuid'] = $existing->uuid;
                $dealer_data['created_at'] = $existing->created_at;
                $wpdb->update('ag_dealer_master', $dealer_data, array('id' => $dealer_id));
            }
        } else {
            $wpdb->insert('ag_dealer_master', $dealer_data);
            $dealer_id = (int) $wpdb->insert_id;
        }

        if (! $dealer_map_id) {
            $wpdb->insert('ag_dealer_map', array(
                'uuid' => wp_generate_uuid4(),
                'dealer_id' => $dealer_id,
                'provider_id' => $provider_id,
                'source_system' => $options['source_system'] ?? 'toyota',
                'external_id' => $dealer['dealer_external_id'] ?: 'toyota-dealer',
                'external_url' => $dealer['website_url'] ?: null,
                'external_reference_type' => 'dealer',
                'provider_dealer_code' => $dealer['dealer_external_id'] ?: null,
                'market_id' => $market_id,
                'source_rank' => 1,
                'source_priority' => 10,
                'is_primary' => 1,
                'sync_status' => 'active',
                'provider_metadata' => wp_json_encode($dealer['metadata'] ?? array()),
                'metadata' => wp_json_encode(array('dealer_name' => $dealer['dealer_name'])),
                'future_reserved' => wp_json_encode(array()),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'dealer_resolution',
                'last_synced_at' => current_time('mysql'),
                'last_provider_update' => current_time('mysql'),
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'first_seen_at' => current_time('mysql'),
                'last_seen_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ));
            $dealer_map_id = (int) $wpdb->insert_id;
        } else {
            $existing_map = self::find_row('ag_dealer_map', array('id' => $dealer_map_id));
            $dealer_map_update = array(
                'dealer_id' => $dealer_id,
                'updated_at' => current_time('mysql'),
                'last_seen_at' => current_time('mysql'),
                'last_synced_at' => current_time('mysql'),
            );
            if ($existing_map) {
                $dealer_map_update['created_at'] = $existing_map->created_at;
            }
            $wpdb->update('ag_dealer_map', $dealer_map_update, array('id' => $dealer_map_id));
        }

        return array(
            'dealer_id' => $dealer_id,
            'dealer_map_id' => $dealer_map_id,
            'country_id' => $country_id,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'market_id' => $market_id,
        );
    }

    public static function resolve_lookups($provider_id, array $dealer_context, array $record, array $options = array())
    {
        $country_id = self::resolve_country($record['country'] ?? 'CA', 'Canada');
        $province_id = self::resolve_province($country_id, $record['province'] ?? 'AB', $record['province'] ?? 'Alberta');
        $city_id = self::resolve_city($country_id, $province_id, $record['city'] ?? 'Calgary');
        $currency_id = self::resolve_currency($record['price']['currency'] ?? 'CAD');
        $market_id = self::resolve_market($record['country'] ?? 'CA', $country_id, $record['country'] ?? 'Canada', $currency_id);

        $manufacturer_id = self::resolve_manufacturer($record['make'] ?? 'Toyota');
        $model_id = self::resolve_model($manufacturer_id, $record['model'] ?? 'Vehicle');
        $platform_id = self::resolve_platform($manufacturer_id, $record['platform'] ?? 'Toyota Platform', $market_id);
        $generation_id = self::resolve_generation($manufacturer_id, $model_id, $platform_id, $record['generation'] ?? ($record['year'] ?? 'generation'), $record['generation'] ?? 'Toyota Generation', $market_id, $country_id, $country_id, null);
        $series_id = self::resolve_series($manufacturer_id, $model_id, $platform_id, $generation_id, $record['series'] ?? $record['model'] ?? 'Series', $record['series'] ?? $record['model'] ?? 'Series', $market_id);
        $trim_id = self::resolve_trim($model_id, $record['trim'] ?? 'Base');
        $vehicle_class_id = self::resolve_vehicle_class($record['body_style'] ?? 'suv', $record['body_style'] ?: 'SUV');
        $vehicle_condition_id = self::resolve_vehicle_condition($record['condition'] ?? 'used', $record['condition'] ?: 'Used');
        $listing_status_id = self::resolve_listing_status($record['listing_status'] ?? 'available', $record['listing_status'] ?: 'Available');
        $body_style_id = self::resolve_body_style($record['body_style'] ?: 'SUV');
        $engine_type_id = self::resolve_engine_type($record['engine']['name'] ?: 'Engine');
        $transmission_type_id = self::resolve_transmission_type($record['transmission'] ?: 'Automatic');
        $drive_type_id = self::resolve_drive_type($record['drivetrain'] ?: 'AWD');
        $fuel_type_id = self::resolve_fuel_type($record['fuel_type'] ?: 'Gasoline');
        $exterior_color_id = self::resolve_color($record['exterior_color'] ?: 'Exterior', 'exterior');
        $interior_color_id = self::resolve_color($record['interior_color'] ?: 'Interior', 'interior');
        $plant_id = self::resolve_manufacturing_plant($manufacturer_id, 'toyota-plant', 'Toyota Plant', $country_id, $province_id, $city_id);

        return array(
            'country_id' => $country_id,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'currency_id' => $currency_id,
            'market_id' => $market_id,
            'manufacturer_id' => $manufacturer_id,
            'model_id' => $model_id,
            'platform_id' => $platform_id,
            'generation_id' => $generation_id,
            'series_id' => $series_id,
            'trim_id' => $trim_id,
            'vehicle_class_id' => $vehicle_class_id,
            'vehicle_condition_id' => $vehicle_condition_id,
            'listing_status_id' => $listing_status_id,
            'body_style_id' => $body_style_id,
            'engine_type_id' => $engine_type_id,
            'transmission_type_id' => $transmission_type_id,
            'drive_type_id' => $drive_type_id,
            'fuel_type_id' => $fuel_type_id,
            'exterior_color_id' => $exterior_color_id,
            'interior_color_id' => $interior_color_id,
            'manufacturing_plant_id' => $plant_id,
        );
    }

    public static function resolve_vehicle_master($provider_id, array $dealer_context, array $record, array $context, array $options = array())
    {
        global $wpdb;

        $vin = ! empty($record['vin']) ? strtoupper($record['vin']) : null;
        $canonical_hash_source = array(
            'vin' => $vin,
            'year' => $record['year'] ?? null,
            'make' => $record['make'] ?? '',
            'model' => $record['model'] ?? '',
            'trim' => $record['trim'] ?? '',
            'body_style' => $record['body_style'] ?? '',
            'dealer' => $dealer_context['dealer_id'] ?? null,
        );
        $canonical_identity_hash = hash('sha256', wp_json_encode($canonical_hash_source));
        $duplicate_group_hash = hash('sha256', strtolower(trim(($record['year'] ?? '') . '|' . ($record['make'] ?? '') . '|' . ($record['model'] ?? '') . '|' . ($record['trim'] ?? '') . '|' . ($record['body_style'] ?? ''))));

        $existing_id = null;
        if ($vin) {
            $existing_id = self::find_id('ag_vehicle_master', array('vin' => $vin));
        }
        if (! $existing_id) {
            $existing_id = self::find_id('ag_vehicle_master', array('canonical_identity_hash' => $canonical_identity_hash));
        }

        $data = array(
            'uuid' => wp_generate_uuid4(),
            'canonical_identity_hash' => $canonical_identity_hash,
            'duplicate_group_hash' => $duplicate_group_hash,
            'identity_state' => $vin ? 'verified' : 'provisional',
            'lifecycle_state' => 'active',
            'manufacturer_id' => $context['manufacturer_id'],
            'model_id' => $context['model_id'],
            'generation_id' => $context['generation_id'],
            'series_id' => $context['series_id'],
            'platform_id' => $context['platform_id'],
            'trim_id' => $context['trim_id'],
            'vehicle_class_id' => $context['vehicle_class_id'],
            'vehicle_condition_id' => $context['vehicle_condition_id'],
            'listing_status_id' => $context['listing_status_id'],
            'body_style_id' => $context['body_style_id'],
            'engine_type_id' => $context['engine_type_id'],
            'transmission_type_id' => $context['transmission_type_id'],
            'drive_type_id' => $context['drive_type_id'],
            'fuel_type_id' => $context['fuel_type_id'],
            'market_id' => $context['market_id'],
            'currency_id' => $context['currency_id'],
            'manufacturing_plant_id' => $context['manufacturing_plant_id'],
            'country_id' => $context['country_id'],
            'province_id' => $context['province_id'],
            'city_id' => $context['city_id'],
            'import_country_id' => $context['country_id'],
            'exterior_color_id' => $context['exterior_color_id'],
            'interior_color_id' => $context['interior_color_id'],
            'vin' => $vin,
            'vin_normalized' => $vin,
            'vin_wmi' => $vin ? substr($vin, 0, 3) : null,
            'vin_vds' => $vin ? substr($vin, 3, 6) : null,
            'vin_vis' => $vin ? substr($vin, -8) : null,
            'vin_checksum_valid' => $vin ? 1 : 0,
            'model_year' => $record['year'] ?? null,
            'production_year_start' => $record['year'] ?? null,
            'production_year_end' => $record['year'] ?? null,
            'global_model_code' => $record['generation'] ?? null,
            'regional_model_code' => $record['series'] ?? null,
            'body_code' => $record['body_style'] ?? null,
            'internal_manufacturer_code' => $record['make'] ?? null,
            'metadata' => wp_json_encode($record['metadata'] ?? array()),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'vehicle_import',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => $vin ? 1 : 0,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($existing_id) {
            $existing = self::find_row('ag_vehicle_master', array('id' => $existing_id));
            if ($existing) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
                $wpdb->update('ag_vehicle_master', $data, array('id' => $existing_id));
                return array('vehicle_id' => (int) $existing_id, 'status' => 'updated');
            }
        }

        $wpdb->insert('ag_vehicle_master', $data);
        return array('vehicle_id' => (int) $wpdb->insert_id, 'status' => 'inserted');
    }

    public static function resolve_vehicle_map($provider_id, array $dealer_context, $vehicle_id, array $record, array $context, array $options = array())
    {
        global $wpdb;

        $source_system = $options['source_system'] ?? 'toyota';
        $external_id = $record['external_id'] ?: ($record['external_vin'] ?: $record['vin']);
        $existing_id = self::find_id('ag_vehicle_map', array(
            'provider_id' => $provider_id,
            'source_system' => $source_system,
            'external_id' => $external_id,
        ));

        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'source_system' => $source_system,
            'source_entity_type' => $record['source_entity_type'] ?? 'vehicle',
            'external_id' => $external_id,
            'external_sub_id' => $record['external_sub_id'] ?: null,
            'external_vin' => $record['external_vin'] ?: null,
            'external_url' => null,
            'external_reference_type' => 'vehicle',
            'provider_record_hash' => $record['source_record_hash'],
            'payload_hash' => $record['source_record_hash'],
            'duplicate_group_hash' => hash('sha256', strtolower(($record['vin'] ?? '') . '|' . ($record['make'] ?? '') . '|' . ($record['model'] ?? ''))),
            'sync_status' => 'active',
            'mapping_status' => 'active',
            'source_rank' => $record['source_rank'] ?? 1,
            'source_priority' => $record['source_priority'] ?? 100,
            'is_primary' => 1,
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'provider_metadata' => wp_json_encode($record['metadata'] ?? array()),
            'metadata' => wp_json_encode(array('vehicle_title' => trim(($record['year'] ?? '') . ' ' . ($record['make'] ?? '') . ' ' . ($record['model'] ?? '') . ' ' . ($record['trim'] ?? '')))),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'vehicle_mapping',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'first_seen_at' => current_time('mysql'),
            'last_seen_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($existing_id) {
            $existing = self::find_row('ag_vehicle_map', array('id' => $existing_id));
            if ($existing) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
            }
            $wpdb->update('ag_vehicle_map', $data, array('id' => $existing_id));
            return array('vehicle_map_id' => (int) $existing_id, 'status' => 'updated');
        }

        $wpdb->insert('ag_vehicle_map', $data);
        return array('vehicle_map_id' => (int) $wpdb->insert_id, 'status' => 'inserted');
    }

    public static function upsert_inventory($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'stock_number' => $record['stock_number'] ?: null,
            'condition_id' => $context['vehicle_condition_id'],
            'availability_status' => $record['listing_status'] ?: 'available',
            'days_on_lot' => null,
            'arrival_date' => null,
            'vin_status' => ! empty($record['vin']) ? 'verified' : 'missing',
            'listing_status_id' => $context['listing_status_id'],
            'lot_status' => $record['listing_status'] ?: 'available',
            'odometer_km' => $record['mileage'] ?? null,
            'is_featured' => 0,
            'is_certified_pre_owned' => 0,
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota', 'raw' => $record['raw'])),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'inventory_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_inventory', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_price($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $price = $record['price'];
        if (empty($price) || (empty($price['current_price']) && empty($price['msrp']) && empty($price['sale_price']))) {
            return array();
        }

        $existing_price = self::find_row('ag_vehicle_price', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'market_id' => $context['market_id'],
            'province_id' => $context['province_id'],
            'currency_id' => $context['currency_id'],
            'price_type' => $price['price_type'] ?: 'current',
            'source_system' => 'toyota',
            'is_current' => 1,
            'is_active' => 1,
            'is_deleted' => 0,
        ));
        $should_insert_history = true;
        if ($existing_price && ! empty($existing_price->source_record_hash) && $existing_price->source_record_hash === ($record['source_record_hash'] ?? '')) {
            $should_insert_history = false;
        }

        $price_id = self::upsert_current_row('ag_vehicle_price', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'market_id' => $context['market_id'],
            'province_id' => $context['province_id'],
            'currency_id' => $context['currency_id'],
            'price_type' => $price['price_type'] ?: 'current',
            'source_system' => 'toyota',
        ), array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'market_id' => $context['market_id'],
            'province_id' => $context['province_id'],
            'currency_id' => $context['currency_id'],
            'price_type' => $price['price_type'] ?: 'current',
            'current_price' => $price['current_price'],
            'msrp' => $price['msrp'],
            'sale_price' => $price['sale_price'],
            'market_price' => $price['market_price'],
            'dealer_price' => $price['dealer_price'],
            'effective_date' => $price['effective_date'],
            'expiry_date' => null,
            'price_status' => 'active',
            'source_record_hash' => $record['source_record_hash'],
            'price_hash' => hash('sha256', wp_json_encode($price)),
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'price_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ), $record['source_record_hash']);

        if ($price_id && $should_insert_history) {
            self::insert_price_history($price_id, $provider_id, $vehicle_id, $dealer_context['dealer_id'], $price, $record);
        }

        return array('price_id' => $price_id);
    }

    public static function insert_price_history($price_id, $provider_id, $vehicle_id, $dealer_id, array $price, array $record)
    {
        global $wpdb;

        $history = array(
            'uuid' => wp_generate_uuid4(),
            'price_id' => $price_id,
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_id,
            'provider_id' => $provider_id,
            'old_price' => null,
            'new_price' => $price['current_price'],
            'old_currency_id' => null,
            'new_currency_id' => null,
            'change_reason' => 'price_sync',
            'price_status' => 'active',
            'effective_date' => $price['effective_date'],
            'source_record_hash' => $record['source_record_hash'],
            'metadata' => wp_json_encode(array('price' => $price)),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $wpdb->insert('ag_vehicle_price_history', $history);
    }

    public static function upsert_location($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'market_id' => $context['market_id'],
            'country_id' => $context['country_id'],
            'province_id' => $context['province_id'],
            'city_id' => $context['city_id'],
            'postal_code' => $record['postal_code'] ?: null,
            'latitude' => $record['latitude'],
            'longitude' => $record['longitude'],
            'geohash' => null,
            'location_type' => 'dealer',
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'location_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_location', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'location_type' => 'dealer',
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_metadata($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $metadata = $record['metadata'];
        $metadata['normalized_record'] = array(
            'vin' => $record['vin'],
            'make' => $record['make'],
            'model' => $record['model'],
            'trim' => $record['trim'],
            'year' => $record['year'],
        );

        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'metadata_namespace' => 'toyota_inventory',
            'metadata_schema_version' => '1.0',
            'metadata' => wp_json_encode($metadata),
            'metadata_hash' => hash('sha256', wp_json_encode($metadata)),
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'metadata_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_metadata', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'metadata_namespace' => 'toyota_inventory',
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_alias($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $alias_name = trim(($record['year'] ?? '') . ' ' . ($record['make'] ?? '') . ' ' . ($record['model'] ?? '') . ' ' . ($record['trim'] ?? ''));
        if ('' === trim($alias_name)) {
            return array();
        }

        $alias_normalized = sanitize_title($alias_name);
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'alias_type' => 'marketing',
            'alias_name' => $alias_name,
            'alias_name_normalized' => $alias_normalized,
            'locale_code' => 'en-CA',
            'market_id' => $context['market_id'],
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'is_primary' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'alias_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_alias', array(
            'vehicle_context_id' => $vehicle_id,
            'dealer_context_id' => $dealer_context['dealer_id'],
            'provider_id' => $provider_id,
            'alias_type' => 'marketing',
            'alias_name_normalized' => $alias_normalized,
            'locale_code' => 'en-CA',
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_specification($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $specifications = $record['specifications'];
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'generation_id' => $context['generation_id'],
            'platform_id' => $context['platform_id'],
            'series_id' => $context['series_id'],
            'manufacturing_plant_id' => $context['manufacturing_plant_id'],
            'production_country_id' => $context['country_id'],
            'market_id' => $context['market_id'],
            'vehicle_class_id' => $context['vehicle_class_id'],
            'wheelbase_mm' => $specifications['wheelbase_mm'],
            'length_mm' => $specifications['length_mm'],
            'width_mm' => $specifications['width_mm'],
            'height_mm' => $specifications['height_mm'],
            'ground_clearance_mm' => $specifications['ground_clearance_mm'],
            'turning_radius_m' => $specifications['turning_radius_m'],
            'cargo_capacity_l' => $specifications['cargo_capacity_l'],
            'passenger_volume_l' => $specifications['passenger_volume_l'],
            'curb_weight_kg' => $specifications['curb_weight_kg'],
            'gvwr_kg' => $specifications['gvwr_kg'],
            'payload_kg' => $specifications['payload_kg'],
            'towing_capacity_kg' => $specifications['towing_capacity_kg'],
            'safety_rating_overall' => $specifications['safety_rating_overall'],
            'safety_rating_source' => $specifications['safety_rating_source'],
            'epa_rating_overall' => $specifications['epa_rating_overall'],
            'epa_city_mpg' => $specifications['epa_city_mpg'],
            'epa_highway_mpg' => $specifications['epa_highway_mpg'],
            'epa_combined_mpg' => $specifications['epa_combined_mpg'],
            'warranty_basic_months' => $specifications['warranty_basic_months'],
            'warranty_basic_km' => $specifications['warranty_basic_km'],
            'warranty_powertrain_months' => $specifications['warranty_powertrain_months'],
            'warranty_powertrain_km' => $specifications['warranty_powertrain_km'],
            'warranty_corrosion_months' => null,
            'warranty_battery_months' => $specifications['warranty_battery_months'],
            'warranty_battery_km' => $specifications['warranty_battery_km'],
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'specification_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_specification', array(
            'vehicle_context_id' => $vehicle_id,
            'provider_id' => $provider_id,
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_engine($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $engine = $record['engine'];
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'engine_code' => $engine['code'] ?: null,
            'engine_name' => $engine['name'] ?: null,
            'fuel_type_id' => $context['fuel_type_id'],
            'displacement_cc' => $engine['displacement_cc'],
            'horsepower_hp' => $engine['horsepower_hp'],
            'torque_nm' => $engine['torque_nm'],
            'compression_ratio' => $engine['compression_ratio'],
            'is_turbocharged' => $engine['turbo'],
            'is_supercharged' => $engine['supercharged'],
            'fuel_system' => $engine['fuel_system'] ?: null,
            'cylinder_count' => $engine['cylinder_count'],
            'cylinder_layout' => $engine['cylinder_layout'] ?: null,
            'emission_standard' => $engine['emission_standard'] ?: null,
            'has_start_stop' => $engine['start_stop'],
            'has_hybrid_assist' => $engine['hybrid_assist'],
            'battery_capacity_kwh' => $engine['battery_capacity_kwh'],
            'charging_type' => $engine['charging_type'] ?: null,
            'motor_power_kw' => $engine['motor_power_kw'],
            'motor_torque_nm' => $engine['motor_torque_nm'],
            'cooling_type' => $engine['cooling_type'] ?: null,
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'engine_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_engine', array(
            'vehicle_context_id' => $vehicle_id,
            'provider_id' => $provider_id,
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_transmission($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'transmission_code' => sanitize_title($record['transmission']),
            'transmission_name' => $record['transmission'],
            'gear_count' => null,
            'transmission_family' => $record['transmission'],
            'is_manual' => stripos($record['transmission'], 'manual') !== false ? 1 : 0,
            'is_automatic' => stripos($record['transmission'], 'automatic') !== false ? 1 : 0,
            'is_cvt' => stripos($record['transmission'], 'cvt') !== false ? 1 : 0,
            'is_dct' => stripos($record['transmission'], 'dct') !== false ? 1 : 0,
            'has_transfer_case' => stripos($record['drivetrain'], '4wd') !== false ? 1 : 0,
            'axle_ratio' => null,
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'transmission_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_transmission', array(
            'vehicle_context_id' => $vehicle_id,
            'provider_id' => $provider_id,
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_fuel_economy($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        $fuel = $record['fuel_economy'];
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_context['dealer_id'],
            'vehicle_map_id' => $vehicle_map_id,
            'dealer_map_id' => $dealer_context['dealer_map_id'],
            'provider_id' => $provider_id,
            'city_l_per_100km' => $fuel['city_l_per_100km'],
            'highway_l_per_100km' => $fuel['highway_l_per_100km'],
            'combined_l_per_100km' => $fuel['combined_l_per_100km'],
            'city_mpg' => null,
            'highway_mpg' => null,
            'combined_mpg' => null,
            'fuel_tank_l' => $fuel['fuel_tank_l'],
            'electric_range_km' => $fuel['electric_range_km'],
            'hybrid_range_km' => $fuel['hybrid_range_km'],
            'battery_range_km' => $fuel['battery_range_km'],
            'mpge' => $fuel['mpge'],
            'consumption_l_per_100km' => $fuel['consumption_l_per_100km'],
            'energy_consumption_kwh_per_100km' => null,
            'source_record_hash' => $record['source_record_hash'],
            'effective_from' => current_time('mysql'),
            'effective_to' => null,
            'is_current' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'fuel_economy_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'vehicle_context_id' => $vehicle_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        return self::upsert_current_row('ag_vehicle_fuel_economy', array(
            'vehicle_context_id' => $vehicle_id,
            'provider_id' => $provider_id,
            'source_system' => 'toyota',
        ), $data, $record['source_record_hash']);
    }

    public static function upsert_features($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        global $wpdb;

        foreach ($record['features'] as $feature) {
            if (empty($feature['code']) || empty($feature['name'])) {
                continue;
            }

            $feature_id = self::resolve_feature($feature['code'], $feature['name'], $feature['category'] ?? 'equipment', $feature['sort_order'] ?? 100);
            $value_data = array(
                'uuid' => wp_generate_uuid4(),
                'vehicle_id' => $vehicle_id,
                'dealer_id' => $dealer_context['dealer_id'],
                'vehicle_map_id' => $vehicle_map_id,
                'dealer_map_id' => $dealer_context['dealer_map_id'],
                'provider_id' => $provider_id,
                'feature_id' => $feature_id,
                'value_type' => $feature['value_type'] ?: 'boolean',
                'value_boolean' => 'boolean' === $feature['value_type'] ? (int) $feature['value'] : null,
                'value_number' => is_numeric($feature['value']) ? (float) $feature['value'] : null,
                'value_text' => is_scalar($feature['value']) ? (string) $feature['value'] : null,
                'value_json' => is_array($feature['value']) ? wp_json_encode($feature['value']) : null,
                'unit_of_measure' => null,
                'evidence_text' => null,
                'sort_order' => $feature['sort_order'] ?? 100,
                'is_primary' => $feature['is_primary'] ?? 0,
                'source_record_hash' => $record['source_record_hash'],
                'effective_from' => current_time('mysql'),
                'effective_to' => null,
                'is_current' => 1,
                'metadata' => wp_json_encode(array('source' => 'toyota')),
                'future_reserved' => wp_json_encode(array()),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'feature_sync',
                'last_synced_at' => current_time('mysql'),
                'last_provider_update' => current_time('mysql'),
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'source_system' => 'toyota',
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'vehicle_context_id' => $vehicle_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            );

            self::upsert_current_row('ag_vehicle_feature_value', array(
                'vehicle_context_id' => $vehicle_id,
                'feature_id' => $feature_id,
                'provider_id' => $provider_id,
                'source_system' => 'toyota',
            ), $value_data, $record['source_record_hash']);
        }

        return array('features' => count($record['features']));
    }

    public static function upsert_images($provider_id, array $dealer_context, $vehicle_id, $vehicle_map_id, array $record, array $context, array $options = array())
    {
        foreach ($record['images'] as $index => $image) {
            $data = array(
                'uuid' => wp_generate_uuid4(),
                'vehicle_id' => $vehicle_id,
                'dealer_id' => $dealer_context['dealer_id'],
                'vehicle_map_id' => $vehicle_map_id,
                'dealer_map_id' => $dealer_context['dealer_map_id'],
                'provider_id' => $provider_id,
                'image_role' => $index === 0 ? 'primary' : ($image['role'] ?: 'gallery'),
                'image_kind' => 'original',
                'original_image_url' => $image['url'],
                'thumbnail_image_url' => $image['thumbnail'] ?: null,
                'gallery_image_url' => $image['gallery'] ?: null,
                'alt_text' => $record['year'] . ' ' . $record['make'] . ' ' . $record['model'],
                'caption' => null,
                'resolution_width' => $image['resolution_width'],
                'resolution_height' => $image['resolution_height'],
                'file_size_bytes' => null,
                'image_hash' => $image['hash'],
                'source_media_id' => $image['source_media_id'] ?: null,
                'sort_order' => $image['sort_order'] ?? $index,
                'is_primary' => $index === 0 ? 1 : 0,
                'is_provider_image' => 1,
                'is_ai_image' => 0,
                'source_record_hash' => $record['source_record_hash'],
                'effective_from' => current_time('mysql'),
                'effective_to' => null,
                'is_current' => 1,
                'metadata' => wp_json_encode(array('source' => 'toyota')),
                'future_reserved' => wp_json_encode(array()),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'image_sync',
                'last_synced_at' => current_time('mysql'),
                'last_provider_update' => current_time('mysql'),
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'source_system' => 'toyota',
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'vehicle_context_id' => $vehicle_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            );

            self::upsert_current_row('ag_vehicle_image', array(
                'vehicle_context_id' => $vehicle_id,
                'provider_id' => $provider_id,
                'image_role' => $data['image_role'],
                'image_hash' => $data['image_hash'],
                'source_system' => 'toyota',
            ), $data, $record['source_record_hash']);
        }

        return array('images' => count($record['images']));
    }

    public static function resolve_simple_lookup($table, array $where, array $data)
    {
        global $wpdb;

        $existing_id = self::find_id($table, $where);
        $data = array_merge($data, array(
            'uuid' => $data['uuid'] ?? wp_generate_uuid4(),
            'source_system' => $data['source_system'] ?? 'toyota',
            'is_active' => $data['is_active'] ?? 1,
            'is_deleted' => $data['is_deleted'] ?? 0,
            'is_verified' => $data['is_verified'] ?? 1,
            'version' => $data['version'] ?? 1,
            'created_at' => $data['created_at'] ?? current_time('mysql'),
            'updated_at' => $data['updated_at'] ?? current_time('mysql'),
        ));

        if ($existing_id) {
            $existing = self::find_row($table, array('id' => $existing_id));
            if ($existing) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
                $wpdb->update($table, $data, array('id' => $existing_id));
                return (int) $existing_id;
            }
        }

        $wpdb->insert($table, $data);
        return (int) $wpdb->insert_id;
    }

    public static function resolve_country($code, $name = null)
    {
        $code = strtoupper(substr(trim((string) $code), 0, 2));
        if ('' === $code) {
            $code = 'CA';
        }

        $iso3_map = array(
            'CA' => 'CAN',
            'US' => 'USA',
            'MX' => 'MEX',
        );

        return self::resolve_simple_lookup('ag_country', array('iso2' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'iso2' => $code,
            'iso3' => $iso3_map[$code] ?? strtoupper(substr($code . 'XXX', 0, 3)),
            'country_name' => $name ?: ('CA' === $code ? 'Canada' : $code),
        ));
    }

    public static function resolve_province($country_id, $code, $name = null)
    {
        $code = strtoupper(trim((string) $code));
        $name = $name ?: $code;
        return self::resolve_simple_lookup('ag_state_province', array('country_id' => $country_id, 'province_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'country_id' => $country_id,
            'province_code' => $code,
            'province_name' => $name,
        ));
    }

    public static function resolve_city($country_id, $province_id, $name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Calgary';
        }

        return self::resolve_simple_lookup('ag_city', array('country_id' => $country_id, 'province_id' => $province_id, 'city_name' => $name), array(
            'uuid' => wp_generate_uuid4(),
            'country_id' => $country_id,
            'province_id' => $province_id,
            'city_name' => $name,
        ));
    }

    public static function resolve_currency($code)
    {
        $code = strtoupper(substr(trim((string) $code), 0, 3));
        if ('' === $code) {
            $code = 'CAD';
        }

        $name = 'CAD' === $code ? 'Canadian Dollar' : $code . ' Currency';
        return self::resolve_simple_lookup('ag_currency', array('currency_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'currency_code' => $code,
            'currency_name' => $name,
            'symbol' => 'CAD' === $code ? '$' : null,
            'minor_unit' => 2,
        ));
    }

    public static function resolve_market($code, $country_id, $name = null, $currency_id = null)
    {
        $code = strtoupper(trim((string) $code));
        if ('' === $code) {
            $code = 'CA';
        }

        return self::resolve_simple_lookup('ag_market', array('market_code' => $code, 'country_id' => $country_id), array(
            'uuid' => wp_generate_uuid4(),
            'market_code' => $code,
            'market_name' => $name ?: ('CA' === $code ? 'Canada' : $code),
            'country_id' => $country_id,
            'currency_id' => $currency_id,
            'language_code' => 'en',
            'region_code' => $code,
            'market_type' => 'retail',
            'is_primary' => 'CA' === $code ? 1 : 0,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_manufacturer($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Toyota';
        }

        return self::resolve_simple_lookup('ag_manufacturer', array('manufacturer_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_name' => $name,
            'manufacturer_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_model($manufacturer_id, $name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Vehicle';
        }

        return self::resolve_simple_lookup('ag_model', array('manufacturer_id' => $manufacturer_id, 'model_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_id' => $manufacturer_id,
            'model_name' => $name,
            'model_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_platform($manufacturer_id, $name, $market_id)
    {
        $code = sanitize_title($name);
        if ('' === $code) {
            $code = 'platform';
        }

        return self::resolve_simple_lookup('ag_platform', array('manufacturer_id' => $manufacturer_id, 'platform_code' => $code, 'market_id' => $market_id), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_id' => $manufacturer_id,
            'platform_code' => $code,
            'platform_name' => $name ?: $code,
            'platform_family_code' => null,
            'platform_type' => null,
            'market_id' => $market_id,
            'production_start_year' => null,
            'production_end_year' => null,
            'is_global' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_manufacturing_plant($manufacturer_id, $code, $name, $country_id, $province_id, $city_id)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_manufacturing_plant', array('manufacturer_id' => $manufacturer_id, 'plant_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_id' => $manufacturer_id,
            'country_id' => $country_id,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'plant_code' => $code,
            'plant_name' => $name ?: $code,
            'plant_type' => 'assembly',
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_generation($manufacturer_id, $model_id, $platform_id, $code, $name, $market_id, $country_id, $import_country_id, $manufacturing_plant_id)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_vehicle_generation', array('model_id' => $model_id, 'generation_code' => $code, 'market_id' => $market_id), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_id' => $manufacturer_id,
            'model_id' => $model_id,
            'platform_id' => $platform_id,
            'generation_code' => $code,
            'generation_name' => $name ?: $code,
            'generation_number' => null,
            'global_model_code' => null,
            'regional_model_code' => null,
            'body_code' => null,
            'internal_manufacturer_code' => null,
            'production_start_year' => null,
            'production_end_year' => null,
            'market_id' => $market_id,
            'country_id' => $country_id,
            'import_country_id' => $import_country_id,
            'manufacturing_plant_id' => $manufacturing_plant_id,
            'is_current_generation' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_series($manufacturer_id, $model_id, $platform_id, $generation_id, $code, $name, $market_id)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_series', array('model_id' => $model_id, 'generation_id' => $generation_id, 'series_code' => $code, 'market_id' => $market_id), array(
            'uuid' => wp_generate_uuid4(),
            'manufacturer_id' => $manufacturer_id,
            'model_id' => $model_id,
            'platform_id' => $platform_id,
            'generation_id' => $generation_id,
            'series_code' => $code,
            'series_name' => $name ?: $code,
            'series_order' => 100,
            'market_id' => $market_id,
            'is_current_series' => 1,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_trim($model_id, $name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Base';
        }

        return self::resolve_simple_lookup('ag_trim', array('model_id' => $model_id, 'trim_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'model_id' => $model_id,
            'trim_name' => $name,
            'trim_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_vehicle_class($code, $name)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_vehicle_class', array('class_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'class_code' => $code,
            'class_name' => $name ?: $code,
            'class_group' => null,
            'parent_class_id' => null,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_vehicle_condition($code, $name)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_vehicle_condition', array('condition_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'condition_code' => $code,
            'condition_name' => $name ?: $code,
            'condition_rank' => 'new' === $code ? 1 : 50,
            'is_new_vehicle' => 'new' === $code ? 1 : 0,
            'is_used_vehicle' => 'new' === $code ? 0 : 1,
            'is_certified_vehicle' => 0,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_listing_status($code, $name)
    {
        $code = sanitize_title($code ?: $name);
        return self::resolve_simple_lookup('ag_listing_status', array('status_code' => $code), array(
            'uuid' => wp_generate_uuid4(),
            'status_code' => $code,
            'status_name' => $name ?: $code,
            'status_rank' => 'available' === $code ? 1 : 50,
            'is_public' => 1,
            'is_terminal' => in_array($code, array('sold', 'removed', 'inactive'), true) ? 1 : 0,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_body_style($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'SUV';
        }

        return self::resolve_simple_lookup('ag_body_style', array('body_style_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'body_style_name' => $name,
            'body_style_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_engine_type($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Engine';
        }

        return self::resolve_simple_lookup('ag_engine_type', array('engine_type_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'engine_type_name' => $name,
            'engine_type_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_transmission_type($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Automatic';
        }

        return self::resolve_simple_lookup('ag_transmission_type', array('transmission_type_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'transmission_type_name' => $name,
            'transmission_type_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_drive_type($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'AWD';
        }

        return self::resolve_simple_lookup('ag_drive_type', array('drive_type_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'drive_type_name' => $name,
            'drive_type_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_fuel_type($name)
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Gasoline';
        }

        return self::resolve_simple_lookup('ag_fuel_type', array('fuel_type_slug' => sanitize_title($name)), array(
            'uuid' => wp_generate_uuid4(),
            'fuel_type_name' => $name,
            'fuel_type_slug' => sanitize_title($name),
        ));
    }

    public static function resolve_color($name, $scope = 'both')
    {
        $name = trim((string) $name);
        if ('' === $name) {
            $name = 'Unknown';
        }

        return self::resolve_simple_lookup('ag_color', array('color_code' => sanitize_title($name), 'color_scope' => $scope), array(
            'uuid' => wp_generate_uuid4(),
            'color_code' => sanitize_title($name),
            'color_name' => $name,
            'color_family' => null,
            'color_scope' => $scope,
            'hex_value' => null,
            'manufacturer_color_code' => null,
            'metadata' => wp_json_encode(array('source' => 'toyota')),
        ));
    }

    public static function resolve_feature($code, $name, $category = 'equipment', $sort_order = 100)
    {
        global $wpdb;

        $code = sanitize_title($code ?: $name);
        $existing_id = self::find_id('ag_vehicle_feature', array('feature_code' => $code));
        $data = array(
            'uuid' => wp_generate_uuid4(),
            'feature_code' => $code,
            'feature_name' => $name ?: $code,
            'feature_category' => $category,
            'feature_group' => $category,
            'feature_scope' => 'vehicle',
            'description' => null,
            'is_standard' => 0,
            'is_optional' => 1,
            'source_record_hash' => null,
            'metadata' => wp_json_encode(array('sort_order' => $sort_order)),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'feature_sync',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        if ($existing_id) {
            $existing = self::find_row('ag_vehicle_feature', array('id' => $existing_id));
            if ($existing) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
                $wpdb->update('ag_vehicle_feature', $data, array('id' => $existing_id));
                return (int) $existing_id;
            }
        }

        $wpdb->insert('ag_vehicle_feature', $data);
        return (int) $wpdb->insert_id;
    }

    public static function upsert_current_row($table, array $where, array $data, $source_record_hash = null)
    {
        global $wpdb;

        $existing = self::find_row($table, array_merge($where, array(
            'is_current' => 1,
            'is_active' => 1,
            'is_deleted' => 0,
        )));

        if ($existing) {
            if (! empty($source_record_hash) && ! empty($existing->source_record_hash) && $existing->source_record_hash === $source_record_hash) {
                $data['uuid'] = $existing->uuid;
                $data['created_at'] = $existing->created_at;
                $wpdb->update($table, $data, array('id' => $existing->id));
                return (int) $existing->id;
            }

            $wpdb->update($table, array(
                'is_current' => 0,
                'effective_to' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ), array('id' => $existing->id));
        }

        if (! empty($source_record_hash) && empty($data['source_record_hash'])) {
            $data['source_record_hash'] = $source_record_hash;
        }

        if ($existing) {
            $data['created_at'] = $existing->created_at;
        }
        $data['uuid'] = $data['uuid'] ?? wp_generate_uuid4();
        $wpdb->insert($table, $data);

        return (int) $wpdb->insert_id;
    }

    public static function insert_log($batch_id, $job_id, $provider_id, $dealer_id, $dealer_map_id, array $record, $level, $code, $message, $duplicate_hash = '', $vehicle_id = null)
    {
        global $wpdb;

        $wpdb->insert('ag_import_log', array(
            'uuid' => wp_generate_uuid4(),
            'batch_id' => $batch_id,
            'job_id' => $job_id,
            'vehicle_id' => $vehicle_id,
            'dealer_id' => $dealer_id,
            'vehicle_map_id' => null,
            'dealer_map_id' => $dealer_map_id,
            'provider_id' => $provider_id,
            'source_row_number' => null,
            'log_level' => $level,
            'log_code' => $code,
            'log_message' => $message,
            'event_type' => $code,
            'duplicate_hash' => $duplicate_hash ?: null,
            'skip_reason' => null,
            'raw_payload' => wp_json_encode($record),
            'normalized_payload' => wp_json_encode($record),
            'log_status' => 'open',
            'event_at' => current_time('mysql'),
            'metadata' => wp_json_encode(array('source' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => $code,
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    public static function create_batch($batch_code, $provider_id, $dealer_id, $dealer_map_id, array $normalized, array $options = array())
    {
        global $wpdb;

        $wpdb->insert('ag_import_batch', array(
            'uuid' => wp_generate_uuid4(),
            'batch_code' => $batch_code,
            'batch_type' => 'toyota_inventory',
            'provider_id' => $provider_id,
            'dealer_id' => $dealer_id,
            'dealer_map_id' => $dealer_map_id,
            'market_id' => $normalized['dealer']['metadata']['market_id'] ?? null,
            'import_source' => $options['source_system'] ?? 'toyota',
            'source_file_name' => $options['source_file_name'] ?? null,
            'source_file_uri' => $options['source_file_uri'] ?? null,
            'source_file_hash' => hash('sha256', wp_json_encode($normalized['raw_payload'])),
            'rows_total' => count($normalized['records']),
            'rows_processed' => 0,
            'rows_inserted' => 0,
            'rows_updated' => 0,
            'rows_ignored' => 0,
            'rows_duplicates' => 0,
            'rows_errors' => 0,
            'rows_warnings' => 0,
            'batch_status' => 'running',
            'started_at' => current_time('mysql'),
            'finished_at' => null,
            'duration_seconds' => null,
            'metadata' => wp_json_encode(array('provider' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => get_current_user_id() ?: null,
            'updated_by' => get_current_user_id() ?: null,
            'change_reason' => 'batch_created',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return (int) $wpdb->insert_id;
    }

    public static function create_job($job_code, $batch_id, $provider_id, $dealer_id, $dealer_map_id, array $normalized, array $options = array())
    {
        global $wpdb;

        $wpdb->insert('ag_import_job', array(
            'uuid' => wp_generate_uuid4(),
            'batch_id' => $batch_id,
            'job_code' => $job_code,
            'job_type' => 'toyota_inventory',
            'queue_name' => 'default',
            'job_status' => 'queued',
            'provider_id' => $provider_id,
            'dealer_id' => $dealer_id,
            'dealer_map_id' => $dealer_map_id,
            'market_id' => $normalized['dealer']['metadata']['market_id'] ?? null,
            'attempt_count' => 0,
            'max_attempts' => 3,
            'rows_total' => count($normalized['records']),
            'rows_success' => 0,
            'rows_failed' => 0,
            'rows_duplicates' => 0,
            'rows_skipped' => 0,
            'started_at' => current_time('mysql'),
            'finished_at' => null,
            'duration_seconds' => null,
            'next_run_at' => null,
            'error_summary' => null,
            'metadata' => wp_json_encode(array('provider' => 'toyota')),
            'future_reserved' => wp_json_encode(array()),
            'created_by' => get_current_user_id() ?: null,
            'updated_by' => get_current_user_id() ?: null,
            'change_reason' => 'job_created',
            'last_synced_at' => current_time('mysql'),
            'last_provider_update' => current_time('mysql'),
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return (int) $wpdb->insert_id;
    }

    public static function finalize_batch($batch_id, array $result)
    {
        global $wpdb;

        $wpdb->update('ag_import_batch', array(
            'rows_processed' => $result['imported'] + $result['errors'] + $result['ignored'],
            'rows_inserted' => $result['imported'],
            'rows_updated' => $result['updated'],
            'rows_ignored' => $result['ignored'],
            'rows_errors' => $result['errors'],
            'rows_warnings' => $result['warnings'],
            'batch_status' => $result['errors'] > 0 ? 'completed_with_errors' : 'completed',
            'finished_at' => current_time('mysql'),
            'duration_seconds' => isset($result['duration_seconds']) ? (int) $result['duration_seconds'] : null,
            'updated_at' => current_time('mysql'),
        ), array('id' => $batch_id));
    }

    public static function finalize_job($job_id, array $result)
    {
        global $wpdb;

        $wpdb->update('ag_import_job', array(
            'job_status' => $result['errors'] > 0 ? 'completed_with_errors' : 'completed',
            'rows_success' => $result['imported'],
            'rows_failed' => $result['errors'],
            'rows_skipped' => $result['ignored'],
            'finished_at' => current_time('mysql'),
            'duration_seconds' => isset($result['duration_seconds']) ? (int) $result['duration_seconds'] : null,
            'updated_at' => current_time('mysql'),
        ), array('id' => $job_id));
    }

    public static function increment_batch_counts($batch_id, array $increments)
    {
        global $wpdb;

        $batch = self::find_row('ag_import_batch', array('id' => $batch_id));
        if (! $batch) {
            return;
        }

        $wpdb->update('ag_import_batch', array(
            'rows_processed' => (int) $batch->rows_processed + (int) ($increments['rows_processed'] ?? 0),
            'rows_inserted' => (int) $batch->rows_inserted + (int) ($increments['rows_inserted'] ?? 0),
            'rows_updated' => (int) $batch->rows_updated + (int) ($increments['rows_updated'] ?? 0),
            'rows_ignored' => (int) $batch->rows_ignored + (int) ($increments['rows_ignored'] ?? 0),
            'rows_duplicates' => (int) $batch->rows_duplicates + (int) ($increments['rows_duplicates'] ?? 0),
            'rows_errors' => (int) $batch->rows_errors + (int) ($increments['rows_errors'] ?? 0),
            'rows_warnings' => (int) $batch->rows_warnings + (int) ($increments['rows_warnings'] ?? 0),
            'updated_at' => current_time('mysql'),
        ), array('id' => $batch_id));
    }

    public static function find_id($table, array $where)
    {
        global $wpdb;

        $row = self::find_row($table, $where);
        return $row ? (int) $row->id : 0;
    }

    public static function find_row($table, array $where)
    {
        global $wpdb;

        $clauses = array();
        $values = array();
        foreach ($where as $column => $value) {
            $clauses[] = $column . ' = %s';
            $values[] = (string) $value;
        }

        if (empty($clauses)) {
            return null;
        }

        $sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $clauses) . ' LIMIT 1';
        $prepared = $wpdb->prepare($sql, $values);
        return $wpdb->get_row($prepared);
    }

    public static function sync_wordpress_vehicle($post_id, $post = null)
    {
        if (self::$importing_wordpress) {
            return 0;
        }

        self::$importing_wordpress = true;
        try {
            $post = $post ?: get_post($post_id);
            if (! $post || 'vehicle' !== $post->post_type) {
                return 0;
            }

            $provider = new class implements AutoGyrus_Provider_Interface {
                public function get_code()
                {
                    return 'wordpress';
                }

                public function get_name()
                {
                    return 'WordPress Vehicle CRUD';
                }

                public function supports($feature)
                {
                    return true;
                }

                public function normalize_payload(array $payload)
                {
                    return $payload;
                }
            };
            $provider_id = self::resolve_provider_master($provider);
            $record = array(
                'external_id' => 'wp-' . $post_id,
                'external_sub_id' => null,
                'external_vin' => strtoupper((string) get_post_meta($post_id, 'vin', true)),
                'stock_number' => (string) get_post_meta($post_id, 'stock_number', true),
                'source_entity_type' => 'wordpress_vehicle',
                'source_rank' => 1,
                'source_priority' => 1,
                'vin' => strtoupper((string) get_post_meta($post_id, 'vin', true)),
                'year' => (int) get_post_meta($post_id, 'year', true),
                'make' => (string) get_post_meta($post_id, 'make', true),
                'model' => (string) get_post_meta($post_id, 'model', true),
                'trim' => (string) get_post_meta($post_id, 'trim', true),
                'generation' => (string) get_post_meta($post_id, 'generation', true),
                'series' => (string) get_post_meta($post_id, 'series', true),
                'platform' => (string) get_post_meta($post_id, 'platform', true),
                'body_style' => (string) get_post_meta($post_id, 'body_type', true),
                'condition' => 'used',
                'listing_status' => 'published',
                'drivetrain' => (string) get_post_meta($post_id, 'drivetrain', true),
                'transmission' => (string) get_post_meta($post_id, 'transmission', true),
                'fuel_type' => (string) get_post_meta($post_id, 'fuel_type', true),
                'engine' => array(
                    'code' => sanitize_title((string) get_post_meta($post_id, 'engine', true)),
                    'name' => (string) get_post_meta($post_id, 'engine', true),
                ),
                'fuel_economy' => array(),
                'price' => array(
                    'current_price' => (float) get_post_meta($post_id, 'price', true),
                    'currency' => 'CAD',
                    'price_type' => 'current',
                ),
                'mileage' => (float) get_post_meta($post_id, 'mileage', true),
                'exterior_color' => (string) get_post_meta($post_id, 'exterior_color', true),
                'interior_color' => (string) get_post_meta($post_id, 'interior_color', true),
                'country' => 'CA',
                'province' => (string) get_post_meta($post_id, 'province', true),
                'city' => (string) get_post_meta($post_id, 'city', true),
                'postal_code' => (string) get_post_meta($post_id, 'postal_code', true),
                'latitude' => null,
                'longitude' => null,
                'images' => array(),
                'features' => array(),
                'specifications' => array(
                    'ground_clearance_mm' => (float) get_post_meta($post_id, 'ground_clearance_mm', true),
                ),
                'description' => (string) $post->post_content,
                'metadata' => array(
                    'source' => 'wordpress',
                    'post_id' => $post_id,
                ),
                'raw' => array(),
                'source_record_hash' => hash('sha256', wp_json_encode(array(
                    'post_id' => $post_id,
                    'vin' => get_post_meta($post_id, 'vin', true),
                    'make' => get_post_meta($post_id, 'make', true),
                    'model' => get_post_meta($post_id, 'model', true),
                    'trim' => get_post_meta($post_id, 'trim', true),
                ))),
            );

            $dealer_context = array(
                'dealer_id' => null,
                'dealer_map_id' => null,
            );
            $context = self::resolve_lookups($provider_id, $dealer_context, $record, array('source_system' => 'wordpress'));
            $vehicle = self::resolve_vehicle_master($provider_id, $dealer_context, $record, $context, array('source_system' => 'wordpress'));
            self::resolve_vehicle_map($provider_id, $dealer_context, $vehicle['vehicle_id'], $record, $context, array('source_system' => 'wordpress'));
            self::upsert_metadata($provider_id, $dealer_context, $vehicle['vehicle_id'], null, $record, $context, array('source_system' => 'wordpress'));

            return (int) $vehicle['vehicle_id'];
        } finally {
            self::$importing_wordpress = false;
        }
    }

    public static function sync_wordpress_vehicle_from_import($vehicle_id, array $record, array $context)
    {
        $post_id = self::find_wordpress_vehicle_post($record);
        if (! $post_id) {
            return;
        }

        self::$importing_wordpress = true;
        try {
            $update = array('ID' => $post_id);
            $title = trim(($record['year'] ?? '') . ' ' . ($record['make'] ?? '') . ' ' . ($record['model'] ?? '') . ' ' . ($record['trim'] ?? ''));
            if ('' !== trim($title) && $title !== get_the_title($post_id)) {
                $update['post_title'] = $title;
            }

            if (count($update) > 1) {
                wp_update_post($update);
            }

            $meta_pairs = array(
                'vin' => $record['vin'] ?? '',
                'make' => $record['make'] ?? '',
                'model' => $record['model'] ?? '',
                'year' => $record['year'] ?? '',
                'trim' => $record['trim'] ?? '',
                'mileage' => $record['mileage'] ?? '',
                'price' => $record['price']['current_price'] ?? '',
                'transmission' => $record['transmission'] ?? '',
                'fuel_type' => $record['fuel_type'] ?? '',
                'engine' => $record['engine']['name'] ?? '',
                'drivetrain' => $record['drivetrain'] ?? '',
                'body_type' => $record['body_style'] ?? '',
                'exterior_color' => $record['exterior_color'] ?? '',
                'interior_color' => $record['interior_color'] ?? '',
                'city' => $record['city'] ?? '',
                'postal_code' => $record['postal_code'] ?? '',
                'province' => $record['province'] ?? '',
                'ground_clearance_mm' => $record['specifications']['ground_clearance_mm'] ?? '',
                'autogyrus_vehicle_id' => $vehicle_id,
                'autogyrus_vehicle_map_id' => $context['vehicle_map_id'] ?? '',
            );

            foreach ($meta_pairs as $key => $value) {
                if ('' !== (string) $value && null !== $value) {
                    update_post_meta($post_id, $key, $value);
                }
            }

            $primary_image = '';
            if (! empty($record['images']) && is_array($record['images'])) {
                foreach ($record['images'] as $image) {
                    if (! empty($image['url'])) {
                        $primary_image = (string) $image['url'];
                        break;
                    }
                }
            }

            if ('' !== $primary_image) {
                $attachment_id = self::sideload_vehicle_image($post_id, $primary_image, $title ?: get_the_title($post_id));
                if ($attachment_id) {
                    set_post_thumbnail($post_id, $attachment_id);

                    if (function_exists('update_field')) {
                        update_field('vehicle_primary_image', $attachment_id, $post_id);
                    } else {
                        update_post_meta($post_id, 'vehicle_primary_image', $attachment_id);
                    }
                }
            }

            do_action('autogyrus_vehicle_post_synced', $post_id, $vehicle_id, $record, $context);
        } finally {
            self::$importing_wordpress = false;
        }
    }

    protected static function sideload_vehicle_image($post_id, $image_url, $title = '')
    {
        static $cache = array();

        $image_url = esc_url_raw((string) $image_url);
        if ('' === $image_url) {
            return 0;
        }

        if (isset($cache[$image_url])) {
            return (int) $cache[$image_url];
        }

        $existing = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_key' => '_autogyrus_source_image_url',
            'meta_value' => $image_url,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ));

        if (! empty($existing)) {
            $attachment_id = (int) $existing[0];
            $cache[$image_url] = $attachment_id;
            return $attachment_id;
        }

        if (! function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return 0;
        }

        $file_array = array(
            'name' => sanitize_file_name(wp_basename(parse_url($image_url, PHP_URL_PATH) ?: 'vehicle-image.jpg')),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return 0;
        }

        update_post_meta($attachment_id, '_autogyrus_source_image_url', $image_url);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
        $cache[$image_url] = (int) $attachment_id;

        return (int) $attachment_id;
    }

    public static function find_wordpress_vehicle_post(array $record)
    {
        $vin = strtoupper((string) ($record['vin'] ?? ''));
        if ('' !== $vin) {
            $existing = get_posts(array(
                'post_type' => 'vehicle',
                'post_status' => 'any',
                'meta_key' => 'vin',
                'meta_value' => $vin,
                'fields' => 'ids',
                'posts_per_page' => 1,
            ));

            if (! empty($existing)) {
                return (int) $existing[0];
            }
        }

        $title = trim(($record['year'] ?? '') . ' ' . ($record['make'] ?? '') . ' ' . ($record['model'] ?? '') . ' ' . ($record['trim'] ?? ''));
        if ('' === $title) {
            return 0;
        }

        $existing = get_posts(array(
            'post_type' => 'vehicle',
            'post_status' => 'any',
            's' => $title,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ));

        return ! empty($existing) ? (int) $existing[0] : 0;
    }

    public static function generate_code($prefix)
    {
        return strtoupper($prefix) . '-' . gmdate('YmdHis') . '-' . wp_generate_password(6, false, false);
    }
}
