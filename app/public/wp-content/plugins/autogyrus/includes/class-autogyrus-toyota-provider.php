<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Toyota_Provider extends AbstractProvider
{
    private const IMAGE_PREFIX = 'https://res.cloudinary.com/goauto-images/image/upload/f_auto,c_fill,w_640,ar_14:9,q_auto/v1/';

    public function get_code()
    {
        return 'toyota';
    }

    public function get_name()
    {
        return 'Toyota Dealer Inventory';
    }

    public function normalize_payload(array $payload)
    {
        $records = $this->extract_records($payload);
        $dealer = $this->normalize_dealer($payload, $records);
        $normalized_records = array();

        foreach ($records as $record) {
            $normalized_records[] = $this->normalize_record($record, $dealer, $payload);
        }

        return array(
            'provider_code' => $this->get_code(),
            'provider_name' => $this->get_name(),
            'dealer' => $dealer,
            'records' => $normalized_records,
            'raw_payload' => $payload,
        );
    }

    private function extract_records(array $payload)
    {
        $results = $this->get_value($payload, 'results', array());
        if (is_array($results) && ! empty($results)) {
            $first_result = reset($results);
            if (is_array($first_result)) {
                $hits = $this->get_value($first_result, array('hits', 'items', 'inventory', 'vehicles'), array());
                $hits = $this->coerce_array($hits);
                $hits = array_values(array_filter($hits, 'is_array'));
                if (! empty($hits)) {
                    return $hits;
                }
            }
        }

        $records = $this->flatten_records($payload);
        return array_values(array_filter($records, 'is_array'));
    }

    public function sample_payload()
    {
        return array(
            'dealer' => array(
                'dealerId' => 'toyota-demo-001',
                'name' => 'Toyota Demo Centre',
                'legalName' => 'Toyota Demo Centre Inc.',
                'website' => 'https://example.com/toyota-demo',
                'phone' => '(403) 555-0100',
                'email' => 'sales@example.com',
                'address' => array(
                    'line1' => '100 Demo Drive',
                    'city' => 'Calgary',
                    'province' => 'AB',
                    'country' => 'CA',
                    'postalCode' => 'T2P 1J9',
                ),
                'location' => array(
                    'latitude' => 51.0447,
                    'longitude' => -114.0719,
                ),
            ),
            'inventory' => array(
                array(
                    'vehicleId' => 'toyota-rav4-2024-demo-001',
                    'vin' => '2T3B1RFV0RW000001',
                    'stockNumber' => 'RAV4001',
                    'year' => 2024,
                    'make' => 'Toyota',
                    'model' => 'RAV4',
                    'trim' => 'XLE',
                    'generation' => '5th Generation',
                    'series' => 'RAV4',
                    'platform' => 'TNGA-K',
                    'bodyStyle' => 'SUV',
                    'condition' => 'Used',
                    'listingStatus' => 'Available',
                    'drivetrain' => 'AWD',
                    'transmission' => 'Automatic',
                    'fuelType' => 'Hybrid',
                    'engine' => array(
                        'code' => 'A25A-FXS',
                        'name' => '2.5L Hybrid',
                        'displacementCc' => 2487,
                        'horsepowerHp' => 219,
                        'torqueNm' => 221,
                    ),
                    'fuelEconomy' => array(
                        'cityLPer100km' => 5.8,
                        'highwayLPer100km' => 6.3,
                        'combinedLPer100km' => 6.0,
                        'fuelTankL' => 55.0,
                    ),
                    'price' => array(
                        'current' => 40995,
                        'msrp' => 43500,
                        'sale' => 39995,
                        'currency' => 'CAD',
                    ),
                    'mileage' => 12850,
                    'exteriorColor' => 'White',
                    'interiorColor' => 'Black',
                    'postalCode' => 'T2P 1J9',
                    'city' => 'Calgary',
                    'province' => 'AB',
                    'country' => 'CA',
                    'images' => array(
                        array(
                            'url' => 'https://example.com/images/toyota-rav4-front.jpg',
                            'thumbnail' => 'https://example.com/images/toyota-rav4-front-thumb.jpg',
                            'role' => 'primary',
                            'hash' => 'demo-image-hash-1',
                        ),
                    ),
                    'features' => array(
                        'Sunroof',
                        'Blind Spot Monitoring',
                        'Apple CarPlay',
                        'Remote Start',
                    ),
                    'specifications' => array(
                        'wheelbaseMm' => 2690,
                        'lengthMm' => 4600,
                        'widthMm' => 1855,
                        'heightMm' => 1685,
                        'groundClearanceMm' => 195,
                        'cargoCapacityL' => 1065,
                    ),
                    'warranty' => array(
                        'basicMonths' => 36,
                        'basicKm' => 60000,
                        'powertrainMonths' => 60,
                        'powertrainKm' => 100000,
                    ),
                    'description' => 'Toyota demo inventory record for validation.',
                ),
            ),
        );
    }

    private function normalize_dealer(array $payload, array $records = array())
    {
        $dealer = $this->get_value($payload, array('dealer', 'dealership', 'dealerInfo', 'dealer_info'), array());
        $dealer = is_array($dealer) ? $dealer : array();

        if (empty($dealer) && ! empty($records)) {
            $first_record = $records[0];
            $dealer = array(
                'dealerId' => $this->normalize_text($this->get_value($first_record, array('dealer_id', 'dealerId', 'dealer_code'), 'toyota-dealer')),
                'name' => $this->normalize_text($this->get_value($first_record, array('dealer_name', 'dealerName', 'dealer'), 'Toyota Dealer')),
                'phone' => $this->normalize_text($this->get_value($first_record, array('dealer_phone', 'phone', 'dealerPhone'), '')),
                'website' => $this->normalize_text($this->get_value($first_record, array('dealer_website', 'website', 'dealerWebsite'), '')),
                'address' => array(
                    'line1' => $this->normalize_text($this->get_value($first_record, array('dealer_address', 'dealerAddress', 'address'), '')),
                    'city' => $this->normalize_text($this->get_value($first_record, array('dealer_city_name', 'dealer_city', 'city'), '')),
                    'province' => $this->normalize_text($this->get_value($first_record, array('dealer_province_short', 'dealer_province', 'province'), 'AB')),
                    'country' => $this->normalize_text($this->get_value($first_record, array('dealer_country', 'country'), 'CA')),
                    'postalCode' => $this->normalize_text($this->get_value($first_record, array('dealer_postal_code', 'postal_code', 'postalCode'), '')),
                ),
                'location' => array(
                    'latitude' => $this->normalize_float($this->get_value($first_record, array('_geoloc.lat', 'latitude', 'dealer_latitude'), null)),
                    'longitude' => $this->normalize_float($this->get_value($first_record, array('_geoloc.lng', '_geoloc.lon', 'longitude', 'dealer_longitude'), null)),
                ),
            );
        }

        $address = $this->get_value($dealer, array('address', 'location.address'), array());
        $location = $this->get_value($dealer, array('location', 'geo'), array());

        return array(
            'dealer_external_id' => $this->normalize_text($this->get_value($dealer, array('dealerId', 'dealer_id', 'id', 'code'), '')),
            'dealer_name' => $this->normalize_text($this->get_value($dealer, array('name', 'dealerName', 'dealer_name'), 'Toyota Dealer')),
            'dealer_legal_name' => $this->normalize_text($this->get_value($dealer, array('legalName', 'dealerLegalName', 'legal_name'), '')),
            'dealer_slug' => $this->slugify($this->get_value($dealer, array('name', 'dealerName', 'dealer_name'), 'Toyota Dealer')),
            'dealer_group_name' => $this->normalize_text($this->get_value($dealer, array('groupName', 'dealerGroupName', 'dealer_group_name'), '')),
            'website_url' => $this->normalize_text($this->get_value($dealer, array('website', 'websiteUrl', 'website_url'), '')),
            'phone_number' => $this->normalize_text($this->get_value($dealer, array('phone', 'phoneNumber', 'phone_number'), '')),
            'email_address' => $this->normalize_text($this->get_value($dealer, array('email', 'emailAddress', 'email_address'), '')),
            'address_line1' => $this->normalize_text($this->get_value($address, array('line1', 'addressLine1', 'street', 'street1'), '')),
            'address_line2' => $this->normalize_text($this->get_value($address, array('line2', 'addressLine2', 'street2'), '')),
            'city' => $this->normalize_text($this->get_value($dealer, array('city', 'dealerCity'), $this->get_value($address, array('city', 'dealerCity'), ''))),
            'province' => $this->normalize_text($this->get_value($dealer, array('province', 'state', 'region'), $this->get_value($address, array('province', 'state', 'region'), ''))),
            'country' => $this->normalize_text($this->get_value($dealer, array('country', 'countryCode'), $this->get_value($address, array('country', 'countryCode'), 'CA'))),
            'postal_code' => $this->normalize_text($this->get_value($dealer, array('postalCode', 'postal_code', 'zip'), $this->get_value($address, array('postalCode', 'postal_code', 'zip'), ''))),
            'latitude' => $this->normalize_float($this->get_value($location, array('latitude', 'lat'), null)),
            'longitude' => $this->normalize_float($this->get_value($location, array('longitude', 'lng', 'lon'), null)),
            'timezone' => $this->normalize_text($this->get_value($dealer, array('timezone', 'timeZone'), '')),
            'canonical_dealer_hash' => hash('sha256', strtolower($this->normalize_text($this->get_value($dealer, array('name', 'dealerName', 'dealer_name'), 'Toyota Dealer')) . '|' . $this->normalize_text($this->get_value($address, array('line1', 'addressLine1', 'street', 'street1'), '')) . '|' . $this->normalize_text($this->get_value($address, array('city', 'dealerCity'), '')) . '|' . $this->normalize_text($this->get_value($address, array('postalCode', 'postal_code', 'zip'), '')))),
            'metadata' => array(
                'provider_raw_dealer' => $dealer,
                'provider_source' => 'toyota',
            ),
        );
    }

    private function normalize_record(array $record, array $dealer, array $payload)
    {
        $engine = $this->get_value($record, array('engine', 'powertrain.engine'), array());
        $price = $this->get_value($record, array('price', 'pricing'), array());
        $fuel_economy = $this->get_value($record, array('fuelEconomy', 'fuel_economy', 'economy'), array());
        $images = $this->coerce_array($this->get_value($record, array('images', 'photos', 'media.images'), array()));
        $features = $this->coerce_array($this->get_value($record, array('features', 'equipment', 'options'), array()));
        $specifications = $this->get_value($record, array('specifications', 'specs', 'dimensions'), array());
        $location = $this->get_value($record, array('location', 'dealer.location', 'dealerLocation'), array());

        $year = $this->normalize_int($this->get_value($record, array('year', 'modelYear', 'model_year'), null));
        $make = $this->normalize_text($this->get_value($record, array('make', 'brand', 'manufacturer'), 'Toyota'));
        $model = $this->normalize_text($this->get_value($record, array('model', 'modelName', 'name'), ''));
        $trim = $this->normalize_text($this->get_value($record, array('trim', 'trimName', 'grade'), ''));

        $raw = is_array($record) ? $record : array();
        $mapped_keys = array('vehicleId', 'vehicle_id', 'id', 'vin', 'stockNumber', 'stock_number', 'year', 'make', 'model', 'trim', 'generation', 'series', 'platform', 'bodyStyle', 'body_type', 'condition', 'listingStatus', 'drivetrain', 'transmission', 'fuelType', 'engine', 'fuelEconomy', 'price', 'mileage', 'exteriorColor', 'interiorColor', 'postalCode', 'city', 'province', 'country', 'images', 'photo_service_ids', 'photo_count', 'features', 'equipment', 'specifications', 'warranty', 'description', 'dealer_name', 'dealer_phone', 'dealer_address', 'dealer_city_name', 'dealer_province_short', 'dealer_province_name', 'dealer_postal_code', 'dealer_country', '_geoloc', 'groups', 'packages', 'option_codes', 'website_overlays', 'craft_site_ids', 'published_notes', 'make_model_trim', 'stock_type', 'stock_status_name', 'body_type_category', 'fuel_type_category', 'transmission_type', 'transmission_name', 'transmission_desc', 'drive_type_name', 'drive_type_desc', 'carfax_report_url', 'carfax_badging_url', 'video_url', 'sort_price', 'list_price', 'special_price', 'msrp', 'regular_price', 'lowest_monthly_lease_payment', 'lowest_monthly_finance_payment');
        $metadata = array(
            'provider' => 'toyota',
            'raw_record' => $raw,
            'unmapped_fields' => $this->map_unmapped_fields($raw, $mapped_keys),
            'dealer' => $dealer,
        );

        return array(
            'external_id' => $this->normalize_text($this->get_value($record, array('vehicleId', 'vehicle_id', 'id', 'listingId', 'inventoryId'), $this->hash_record($record))),
            'external_sub_id' => $this->normalize_text($this->get_value($record, array('subId', 'stockNumber', 'stock_number'), '')),
            'external_vin' => $this->normalize_upper($this->get_value($record, array('vin', 'VIN', 'vehicleIdentificationNumber'), '')),
            'stock_number' => $this->normalize_text($this->get_value($record, array('stockNumber', 'stock_number', 'stock'), '')),
            'source_entity_type' => 'vehicle',
            'source_rank' => $this->normalize_int($this->get_value($record, array('sourceRank', 'rank'), 1), 1),
            'source_priority' => $this->normalize_int($this->get_value($record, array('sourcePriority', 'priority'), 100), 100),
            'vin' => $this->normalize_upper($this->get_value($record, array('vin', 'VIN', 'vehicleIdentificationNumber'), '')),
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'trim' => $trim,
            'generation' => $this->normalize_text($this->get_value($record, array('generation', 'generationName', 'generation_name'), '')),
            'series' => $this->normalize_text($this->get_value($record, array('series', 'seriesName', 'series_name'), '')),
            'platform' => $this->normalize_text($this->get_value($record, array('platform', 'platformName', 'platform_name'), '')),
            'body_style' => $this->normalize_text($this->get_value($record, array('bodyStyle', 'body_style', 'bodyType'), '')),
            'condition' => $this->normalize_text($this->get_value($record, array('condition', 'vehicleCondition'), 'Used')),
            'listing_status' => $this->normalize_text($this->get_value($record, array('listingStatus', 'status', 'availabilityStatus'), 'Available')),
            'drivetrain' => $this->normalize_text($this->get_value($record, array('drivetrain', 'driveType', 'drive_type'), '')),
            'transmission' => $this->normalize_text($this->get_value($record, array('transmission', 'transmissionType', 'transmission_type'), '')),
            'fuel_type' => $this->normalize_text($this->get_value($record, array('fuelType', 'fuel_type', 'engineFuelType'), '')),
            'engine' => array(
                'code' => $this->normalize_text($this->get_value($engine, array('code', 'engineCode'), '')),
                'name' => $this->normalize_text($this->get_value($engine, array('name', 'engineName'), $this->normalize_text($this->get_value($record, array('engine', 'engineDescription'), '')))),
                'displacement_cc' => $this->normalize_float($this->get_value($engine, array('displacementCc', 'displacement_cc'), null)),
                'horsepower_hp' => $this->normalize_float($this->get_value($engine, array('horsepowerHp', 'horsepower_hp', 'horsepower'), null)),
                'torque_nm' => $this->normalize_float($this->get_value($engine, array('torqueNm', 'torque_nm', 'torque'), null)),
                'compression_ratio' => $this->normalize_float($this->get_value($engine, array('compressionRatio', 'compression_ratio'), null)),
                'turbo' => $this->normalize_bool($this->get_value($engine, array('turbo', 'isTurbocharged'), 0)),
                'supercharged' => $this->normalize_bool($this->get_value($engine, array('supercharger', 'isSupercharged'), 0)),
                'fuel_system' => $this->normalize_text($this->get_value($engine, array('fuelSystem', 'fuel_system'), '')),
                'cylinder_count' => $this->normalize_int($this->get_value($engine, array('cylinderCount', 'cylinder_count'), null)),
                'cylinder_layout' => $this->normalize_text($this->get_value($engine, array('cylinderLayout', 'cylinder_layout'), '')),
                'emission_standard' => $this->normalize_text($this->get_value($engine, array('emissionStandard', 'emission_standard'), '')),
                'start_stop' => $this->normalize_bool($this->get_value($engine, array('startStop', 'start_stop'), 0)),
                'hybrid_assist' => $this->normalize_bool($this->get_value($engine, array('hybridAssist', 'hybrid_assist'), 0)),
                'battery_capacity_kwh' => $this->normalize_float($this->get_value($engine, array('batteryCapacityKwh', 'battery_capacity_kwh'), null)),
                'charging_type' => $this->normalize_text($this->get_value($engine, array('chargingType', 'charging_type'), '')),
                'motor_power_kw' => $this->normalize_float($this->get_value($engine, array('motorPowerKw', 'motor_power_kw'), null)),
                'motor_torque_nm' => $this->normalize_float($this->get_value($engine, array('motorTorqueNm', 'motor_torque_nm'), null)),
                'cooling_type' => $this->normalize_text($this->get_value($engine, array('coolingType', 'cooling_type'), '')),
            ),
            'fuel_economy' => array(
                'city_l_per_100km' => $this->normalize_float($this->get_value($fuel_economy, array('cityLPer100km', 'city_l_per_100km'), null)),
                'highway_l_per_100km' => $this->normalize_float($this->get_value($fuel_economy, array('highwayLPer100km', 'highway_l_per_100km'), null)),
                'combined_l_per_100km' => $this->normalize_float($this->get_value($fuel_economy, array('combinedLPer100km', 'combined_l_per_100km'), null)),
                'fuel_tank_l' => $this->normalize_float($this->get_value($fuel_economy, array('fuelTankL', 'fuel_tank_l'), null)),
                'electric_range_km' => $this->normalize_float($this->get_value($fuel_economy, array('electricRangeKm', 'electric_range_km'), null)),
                'hybrid_range_km' => $this->normalize_float($this->get_value($fuel_economy, array('hybridRangeKm', 'hybrid_range_km'), null)),
                'battery_range_km' => $this->normalize_float($this->get_value($fuel_economy, array('batteryRangeKm', 'battery_range_km'), null)),
                'mpge' => $this->normalize_float($this->get_value($fuel_economy, array('mpge', 'mpgE'), null)),
                'consumption_l_per_100km' => $this->normalize_float($this->get_value($fuel_economy, array('consumptionLPer100km', 'consumption_l_per_100km'), null)),
            ),
            'price' => array(
                'current_price' => $this->normalize_float($this->get_value($price, array('current', 'currentPrice', 'price'), null)),
                'msrp' => $this->normalize_float($this->get_value($price, array('msrp', 'listPrice'), null)),
                'sale_price' => $this->normalize_float($this->get_value($price, array('sale', 'salePrice', 'internetPrice'), null)),
                'market_price' => $this->normalize_float($this->get_value($price, array('market', 'marketPrice'), null)),
                'dealer_price' => $this->normalize_float($this->get_value($price, array('dealer', 'dealerPrice'), null)),
                'currency' => $this->normalize_text($this->get_value($price, array('currency', 'currencyCode'), 'CAD')),
                'price_type' => $this->normalize_text($this->get_value($price, array('priceType'), 'current')),
                'effective_date' => $this->normalize_date($this->get_value($price, array('effectiveDate', 'date'), null)),
            ),
            'mileage' => $this->normalize_float($this->get_value($record, array('mileage', 'odometer', 'odometerKm', 'odometer_km'), null)),
            'exterior_color' => $this->normalize_text($this->get_value($record, array('exteriorColor', 'exterior_color', 'colorExterior'), '')),
            'interior_color' => $this->normalize_text($this->get_value($record, array('interiorColor', 'interior_color', 'colorInterior'), '')),
            'country' => $this->normalize_text($this->get_value($record, array('country', 'countryCode', 'location.country'), $this->get_value($dealer, 'country', 'CA'))),
            'province' => $this->normalize_text($this->get_value($record, array('province', 'state', 'region', 'location.province'), $this->get_value($dealer, 'province', 'AB'))),
            'city' => $this->normalize_text($this->get_value($record, array('city', 'location.city'), $this->get_value($dealer, 'city', ''))),
            'postal_code' => $this->normalize_text($this->get_value($record, array('postalCode', 'postal_code', 'zip', 'location.postalCode'), $this->get_value($dealer, 'postal_code', ''))),
            'latitude' => $this->normalize_float($this->get_value($location, array('latitude', 'lat'), $this->get_value($dealer, 'latitude', null))),
            'longitude' => $this->normalize_float($this->get_value($location, array('longitude', 'lng', 'lon'), $this->get_value($dealer, 'longitude', null))),
            'images' => $this->normalize_images($images, $record),
            'features' => $this->normalize_features($features),
            'specifications' => array(
                'wheelbase_mm' => $this->normalize_float($this->get_value($specifications, array('wheelbaseMm', 'wheelbase_mm'), null)),
                'length_mm' => $this->normalize_float($this->get_value($specifications, array('lengthMm', 'length_mm'), null)),
                'width_mm' => $this->normalize_float($this->get_value($specifications, array('widthMm', 'width_mm'), null)),
                'height_mm' => $this->normalize_float($this->get_value($specifications, array('heightMm', 'height_mm'), null)),
                'ground_clearance_mm' => $this->normalize_float($this->get_value($specifications, array('groundClearanceMm', 'ground_clearance_mm'), null)),
                'turning_radius_m' => $this->normalize_float($this->get_value($specifications, array('turningRadiusM', 'turning_radius_m'), null)),
                'cargo_capacity_l' => $this->normalize_float($this->get_value($specifications, array('cargoCapacityL', 'cargo_capacity_l'), null)),
                'passenger_volume_l' => $this->normalize_float($this->get_value($specifications, array('passengerVolumeL', 'passenger_volume_l'), null)),
                'curb_weight_kg' => $this->normalize_float($this->get_value($specifications, array('curbWeightKg', 'curb_weight_kg'), null)),
                'gvwr_kg' => $this->normalize_float($this->get_value($specifications, array('gvwrKg', 'gvwr_kg'), null)),
                'payload_kg' => $this->normalize_float($this->get_value($specifications, array('payloadKg', 'payload_kg'), null)),
                'towing_capacity_kg' => $this->normalize_float($this->get_value($specifications, array('towingCapacityKg', 'towing_capacity_kg'), null)),
                'safety_rating_overall' => $this->normalize_text($this->get_value($specifications, array('safetyRatingOverall', 'safety_rating_overall'), '')),
                'safety_rating_source' => $this->normalize_text($this->get_value($specifications, array('safetyRatingSource', 'safety_rating_source'), '')),
                'epa_rating_overall' => $this->normalize_text($this->get_value($specifications, array('epaRatingOverall', 'epa_rating_overall'), '')),
                'epa_city_mpg' => $this->normalize_float($this->get_value($specifications, array('epaCityMpg', 'epa_city_mpg'), null)),
                'epa_highway_mpg' => $this->normalize_float($this->get_value($specifications, array('epaHighwayMpg', 'epa_highway_mpg'), null)),
                'epa_combined_mpg' => $this->normalize_float($this->get_value($specifications, array('epaCombinedMpg', 'epa_combined_mpg'), null)),
                'warranty_basic_months' => $this->normalize_int($this->get_value($record, array('warranty.basicMonths', 'warrantyBasicMonths', 'warranty_basic_months'), null)),
                'warranty_basic_km' => $this->normalize_int($this->get_value($record, array('warranty.basicKm', 'warrantyBasicKm', 'warranty_basic_km'), null)),
                'warranty_powertrain_months' => $this->normalize_int($this->get_value($record, array('warranty.powertrainMonths', 'warrantyPowertrainMonths', 'warranty_powertrain_months'), null)),
                'warranty_powertrain_km' => $this->normalize_int($this->get_value($record, array('warranty.powertrainKm', 'warrantyPowertrainKm', 'warranty_powertrain_km'), null)),
                'warranty_battery_months' => $this->normalize_int($this->get_value($record, array('warranty.batteryMonths', 'warrantyBatteryMonths', 'warranty_battery_months'), null)),
                'warranty_battery_km' => $this->normalize_int($this->get_value($record, array('warranty.batteryKm', 'warrantyBatteryKm', 'warranty_battery_km'), null)),
            ),
            'description' => $this->normalize_text($this->get_value($record, array('description', 'vehicleDescription', 'details.description'), '')),
            'dealer' => $dealer,
            'metadata' => $metadata,
            'raw' => $raw,
            'source_record_hash' => hash('sha256', wp_json_encode(array(
                'dealer' => $dealer,
                'record' => $raw,
            ))),
        );
    }

    private function normalize_images(array $images, array $record = array())
    {
        $normalized = array();

        foreach ($images as $image) {
            if (! is_array($image)) {
                $image = array('url' => $image);
            }

            $url = $this->normalize_text($this->get_value($image, array('url', 'imageUrl', 'src', 'sourceUrl'), ''));
            if ('' === $url) {
                $source_media_id = $this->normalize_text($this->get_value($image, array('sourceMediaId', 'mediaId'), ''));
                if ('' !== $source_media_id) {
                    $url = self::IMAGE_PREFIX . rawurlencode($source_media_id) . '.jpg';
                } else {
                    continue;
                }
            }

            $normalized[] = array(
                'url' => $url,
                'thumbnail' => $this->normalize_text($this->get_value($image, array('thumbnail', 'thumb', 'thumbnailUrl'), $url)),
                'gallery' => $this->normalize_text($this->get_value($image, array('gallery', 'galleryUrl'), $url)),
                'role' => $this->normalize_text($this->get_value($image, array('role', 'imageRole'), 'gallery')),
                'hash' => $this->normalize_text($this->get_value($image, array('hash', 'imageHash'), hash('sha256', $url))),
                'resolution_width' => $this->normalize_int($this->get_value($image, array('width', 'resolutionWidth'), null)),
                'resolution_height' => $this->normalize_int($this->get_value($image, array('height', 'resolutionHeight'), null)),
                'source_media_id' => $this->normalize_text($this->get_value($image, array('sourceMediaId', 'mediaId'), '')),
            );
        }

        if (empty($normalized)) {
            $service_ids = $this->coerce_array($this->get_value($record, array('photo_service_ids', 'photoServiceIds', 'photos'), array()));
            foreach ($service_ids as $index => $service_id) {
                $service_id = $this->normalize_text($service_id);
                if ('' === $service_id) {
                    continue;
                }

                $normalized[] = array(
                    'url' => self::IMAGE_PREFIX . rawurlencode($service_id) . '.jpg',
                    'thumbnail' => self::IMAGE_PREFIX . rawurlencode($service_id) . '.jpg',
                    'gallery' => self::IMAGE_PREFIX . rawurlencode($service_id) . '.jpg',
                    'role' => 0 === $index ? 'primary' : 'gallery',
                    'hash' => hash('sha256', $service_id),
                    'resolution_width' => null,
                    'resolution_height' => null,
                    'source_media_id' => $service_id,
                );
            }
        }

        return $normalized;
    }

    private function normalize_features(array $features)
    {
        $normalized = array();
        $feature_index = 0;

        foreach ($features as $feature) {
            if (is_array($feature)) {
                $code = $this->normalize_text($this->get_value($feature, array('code', 'featureCode', 'id'), ''));
                $name = $this->normalize_text($this->get_value($feature, array('name', 'featureName', 'label'), ''));
                $category = $this->normalize_text($this->get_value($feature, array('category', 'featureCategory'), 'equipment'));
                $value = $this->get_value($feature, array('value', 'present', 'enabled'), true);
                $normalized[] = array(
                    'code' => '' !== $code ? $code : sanitize_title($name),
                    'name' => $name,
                    'category' => $category,
                    'value' => $value,
                    'value_type' => is_bool($value) || is_numeric($value) ? 'boolean' : 'text',
                    'is_primary' => $this->normalize_bool($this->get_value($feature, array('isPrimary', 'primary'), 0)),
                    'sort_order' => $this->normalize_int($this->get_value($feature, array('sortOrder', 'sort_order'), $feature_index), $feature_index),
                );
            } else {
                $name = $this->normalize_text($feature, '');
                if ('' === $name) {
                    continue;
                }

                $normalized[] = array(
                    'code' => sanitize_title($name),
                    'name' => $name,
                    'category' => 'equipment',
                    'value' => true,
                    'value_type' => 'boolean',
                    'is_primary' => 0,
                    'sort_order' => $feature_index,
                );
            }

            $feature_index++;
        }

        return $normalized;
    }
}
