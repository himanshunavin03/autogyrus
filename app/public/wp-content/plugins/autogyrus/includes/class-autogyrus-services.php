<?php
if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Services
{
    public static function register()
    {
        add_filter('autogyrus_future_value', [__CLASS__, 'future_value'], 10, 2);
        add_filter('autogyrus_winter_score', [__CLASS__, 'winter_score'], 10, 2);
        add_filter('autogyrus_maintenance_forecast', [__CLASS__, 'maintenance_forecast'], 10, 2);
        add_filter('autogyrus_reliability_score', [__CLASS__, 'reliability_score'], 10, 2);
        add_filter('autogyrus_insurance_estimate', [__CLASS__, 'insurance_estimate'], 10, 2);
        add_action('wp', array(__CLASS__, 'track_vehicle_view'));
    }

    public static function parse_natural_language_query($query)
    {
        $query = trim((string) $query);

        $openai_filters = self::parse_query_with_openai($query);
        if (! empty($openai_filters)) {
            return $openai_filters;
        }

        $filters = array();
        $make_map = array('Toyota', 'Honda', 'Ford', 'Chevrolet', 'Mazda', 'Subaru', 'Hyundai', 'Kia', 'Jeep', 'Volkswagen');
        $body_types = array('SUV', 'Truck', 'Sedan', 'Coupe', 'Wagon', 'Van', 'Hatchback');
        $city_map = array_keys(self::city_coordinates());

        if (preg_match('/under\s*\$?(\d+(?:,\d+)*)/i', $query, $matches)) {
            $filters['maxPrice'] = (int) str_replace(',', '', $matches[1]);
        }

        if (preg_match('/(\d{4})/', $query, $matches)) {
            $filters['year'] = (int) $matches[1];
        }

        foreach ($make_map as $make) {
            if (false !== stripos($query, $make)) {
                $filters['make'] = $make;
                break;
            }
        }

        foreach ($body_types as $body_type) {
            if (false !== stripos($query, strtolower($body_type))) {
                $filters['bodyType'] = $body_type;
                break;
            }
        }

        if (preg_match('/(\d+)\s*(km|kilometres|kilometers)/i', $query, $matches)) {
            $filters['maxMileage'] = (int) $matches[1];
        }

        if (preg_match('/(?:within|under|less than|radius of)\s*(\d+)\s*(km|kilometres|kilometers)/i', $query, $matches)) {
            $filters['distance'] = (int) $matches[1];
        }

        if (preg_match('/\b([A-Z]\d[A-Z][ -]?\d[A-Z]\d)\b/i', $query, $matches)) {
            $filters['postalCode'] = strtoupper(preg_replace('/\s+/', '', $matches[1]));
        }

        if (false !== stripos($query, 'awd') || false !== stripos($query, '4wd') || false !== stripos($query, 'winter-ready')) {
            $filters['drivetrain'] = 'AWD';
        }

        if (false !== stripos($query, 'reliable')) {
            $filters['reliability'] = 'high';
        }

        if (false !== stripos($query, 'family')) {
            $filters['bodyType'] = empty($filters['bodyType']) ? 'SUV' : $filters['bodyType'];
        }

        if (false !== stripos($query, 'towing')) {
            $filters['bodyType'] = 'Truck';
        }

        foreach ($city_map as $city) {
            if (false !== stripos($query, $city)) {
                $filters['location'] = $city;
                $filters['city'] = $city;
                break;
            }
        }

        return $filters;
    }

    public static function parse_query_with_openai($query)
    {
        $api_key = trim((string) get_option('autogyrus_openai_api_key', ''));
        if (empty($api_key) || empty($query)) {
            return array();
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => get_option('autogyrus_openai_model', 'gpt-4o-mini'),
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'Convert vehicle shopping text into JSON filters using keys make, model, bodyType, maxPrice, maxMileage, drivetrain, fuelType, reliability, province, city, postalCode, location, distance, year.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => $query,
                    ),
                ),
                'response_format' => array('type' => 'json_object'),
                'temperature' => 0.1,
            )),
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        $json = json_decode($content, true);

        return is_array($json) ? $json : array();
    }

    public static function build_vehicle_snapshot($vehicle_id)
    {
        $fields = array(
            'vin', 'make', 'model', 'year', 'trim', 'mileage', 'price', 'transmission', 'fuel_type',
            'engine', 'drivetrain', 'body_type', 'exterior_color', 'interior_color', 'tire_size',
            'province_registration_history', 'block_heater', 'remote_start', 'winter_tires_included',
            'service_history', 'accident_history', 'dealer_user_id', 'city', 'province', 'warranty',
            'financing_available', 'ground_clearance_mm', 'winter_score', 'reliability_score',
            'future_value_rating', 'future_value_summary', 'reliability_summary', 'known_issues',
            'common_complaints', 'recall_history',
        );

        $snapshot = array(
            'id' => $vehicle_id,
            'title' => get_the_title($vehicle_id),
            'link' => get_permalink($vehicle_id),
        );

        foreach ($fields as $field) {
            $snapshot[$field] = get_post_meta($vehicle_id, $field, true);
        }

        $snapshot['thumbnail'] = get_the_post_thumbnail_url($vehicle_id, 'large');
        $snapshot['dealer_user_id'] = self::get_vehicle_owner_id($vehicle_id);
        $snapshot['dealer_name'] = self::get_dealer_name($snapshot['dealer_user_id']);
        $snapshot['future_value'] = self::future_value(null, $snapshot);
        $snapshot['winter'] = self::winter_score(null, $snapshot);
        $snapshot['maintenance'] = self::maintenance_forecast(null, $snapshot);
        $snapshot['reliability'] = self::reliability_score(null, $snapshot);

        return $snapshot;
    }

    public static function refresh_vehicle_scores($vehicle_id)
    {
        $snapshot = self::build_vehicle_snapshot($vehicle_id);
        $future = self::future_value(null, $snapshot);
        $winter = self::winter_score(null, $snapshot);
        $reliability = self::reliability_score(null, $snapshot);

        update_post_meta($vehicle_id, 'winter_score', $winter['score']);
        update_post_meta($vehicle_id, 'reliability_score', $reliability['score']);
        update_post_meta($vehicle_id, 'future_value_rating', $future['rating']);
        update_post_meta($vehicle_id, 'future_value_summary', $future['summary']);
        update_post_meta($vehicle_id, 'reliability_summary', $reliability['summary']);
        update_post_meta($vehicle_id, 'known_issues', implode('; ', $reliability['known_issues']));
        update_post_meta($vehicle_id, 'common_complaints', implode('; ', $reliability['common_complaints']));
        update_post_meta($vehicle_id, 'recall_history', implode('; ', $reliability['recall_history']));
    }

    public static function future_value($value, $vehicle)
    {
        $price = (float) ($vehicle['price'] ?? 0);
        $year = (int) ($vehicle['year'] ?? date('Y'));
        $mileage = (int) ($vehicle['mileage'] ?? 0);
        $age = max(1, ((int) gmdate('Y')) - $year + 1);
        $demand_factor = self::demand_factor($vehicle);
        $mileage_penalty = min(0.22, $mileage / 300000);
        $base_retention = max(0.42, 0.94 - ($age * 0.045) - $mileage_penalty + $demand_factor);

        $predictions = array(
            '1_year' => round($price * ($base_retention - 0.03), 0),
            '2_year' => round($price * ($base_retention - 0.08), 0),
            '3_year' => round($price * ($base_retention - 0.13), 0),
            '5_year' => round($price * ($base_retention - 0.22), 0),
        );

        $percentile = min(92, max(48, (int) round(($base_retention * 100) + 10)));
        $rating = 'Average';
        if ($percentile >= 82) {
            $rating = 'Excellent';
        } elseif ($percentile >= 72) {
            $rating = 'Good';
        } elseif ($percentile < 58) {
            $rating = 'Poor';
        }

        return array(
            'current_price' => $price,
            'predictions' => $predictions,
            'rating' => $rating,
            'summary' => sprintf(
                '%s %s is expected to retain value better than %d%% of comparable %s vehicles.',
                $vehicle['make'] ?? 'This',
                $vehicle['model'] ?? 'vehicle',
                $percentile,
                strtolower($vehicle['body_type'] ?? 'used')
            ),
        );
    }

    public static function winter_score($value, $vehicle)
    {
        $score = 45;

        if (! empty($vehicle['drivetrain']) && false !== stripos($vehicle['drivetrain'], 'AWD')) {
            $score += 20;
        }

        if (! empty($vehicle['drivetrain']) && false !== stripos($vehicle['drivetrain'], '4WD')) {
            $score += 24;
        }

        $score += ! empty($vehicle['block_heater']) ? 8 : 0;
        $score += ! empty($vehicle['remote_start']) ? 5 : 0;
        $score += ! empty($vehicle['winter_tires_included']) ? 12 : 0;
        $score += ((int) ($vehicle['ground_clearance_mm'] ?? 180)) >= 205 ? 7 : 0;

        if (in_array($vehicle['make'] ?? '', array('Toyota', 'Subaru', 'Mazda', 'Honda'), true)) {
            $score += 6;
        }

        $score = max(0, min(100, $score));

        $badge = 'Needs Winter Upgrades';
        if ($score >= 90) {
            $badge = 'Excellent Winter Vehicle';
        } elseif ($score >= 82) {
            $badge = 'Canada Winter Certified';
        } elseif ($score >= 72) {
            $badge = 'Winter Ready';
        }

        return array(
            'score' => $score,
            'badge' => $badge,
            'summary' => 'Scored using drivetrain, cold-start features, winter tire readiness, clearance, and Canada ownership heuristics.',
        );
    }

    public static function maintenance_forecast($value, $vehicle)
    {
        $mileage = (int) ($vehicle['mileage'] ?? 0);
        $age = max(1, ((int) gmdate('Y')) - (int) ($vehicle['year'] ?? gmdate('Y')) + 1);
        $intervals = array(90000, 120000, 150000);
        $services = array();
        $annual_costs = array();

        foreach ($intervals as $interval) {
            if ($mileage < $interval) {
                $distance_remaining = max(3000, $interval - $mileage);
                $months = max(2, (int) round($distance_remaining / 1500));
                $cost = $interval <= 90000 ? 650 : ($interval <= 120000 ? 1200 : 1800);

                $services[] = array(
                    'label' => number_format_i18n($interval, 0) . ' KM Service',
                    'cost' => $cost,
                    'due_in' => $months . ' Months',
                );
            }
        }

        $annual_costs['year_1'] = 850 + ($age * 60);
        $annual_costs['year_2'] = 980 + ($age * 70);
        $annual_costs['year_3'] = 1180 + ($age * 85);

        $risk = 'Low';
        if ($mileage > 90000 || $age >= 5) {
            $risk = 'Medium';
        }
        if ($mileage > 140000 || $age >= 8) {
            $risk = 'High';
        }

        return array(
            'risk' => $risk,
            'services' => $services,
            'ownership_cost_forecast' => $annual_costs,
        );
    }

    public static function reliability_score($value, $vehicle)
    {
        $make = $vehicle['make'] ?? '';
        $score = 76;
        $boosted_makes = array('Toyota' => 12, 'Honda' => 10, 'Mazda' => 8, 'Subaru' => 6);
        $score += $boosted_makes[$make] ?? 0;

        $mileage = (int) ($vehicle['mileage'] ?? 0);
        $score -= (int) floor($mileage / 50000) * 3;
        $score = max(52, min(96, $score));

        $rating = 'Average';
        if ($score >= 88) {
            $rating = 'Excellent';
        } elseif ($score >= 78) {
            $rating = 'Good';
        } elseif ($score < 65) {
            $rating = 'Poor';
        }

        return array(
            'score' => $score,
            'rating' => $rating,
            'categories' => array(
                'Engine' => min(100, $score + 2),
                'Transmission' => max(50, $score - 1),
                'Electrical' => max(48, $score - 4),
                'Safety' => min(100, $score + 4),
                'Interior' => max(50, $score - 3),
                'Cooling System' => max(49, $score - 2),
            ),
            'known_issues' => array(
                'Monitor battery health in extreme cold.',
                'Inspect suspension bushings during winter tire changeovers.',
            ),
            'common_complaints' => array(
                'Infotainment lag reported on some trims.',
                'Road noise rises as all-season tires wear.',
            ),
            'recall_history' => array(
                'Check VIN-specific recall status before delivery.',
            ),
            'summary' => sprintf(
                '%d %s %s is rated %s for long-term ownership with strong confidence for Canadian daily driving.',
                (int) ($vehicle['year'] ?? gmdate('Y')),
                $make,
                $vehicle['model'] ?? 'vehicle',
                strtolower($rating)
            ),
        );
    }

    public static function insurance_estimate($value, $input)
    {
        $age = max(16, (int) ($input['driver_age'] ?? 35));
        $experience = max(0, (int) ($input['driving_experience'] ?? 10));
        $province = strtoupper(sanitize_text_field((string) ($input['province'] ?? 'AB')));
        $vehicle = is_array($input['vehicle'] ?? null) ? $input['vehicle'] : array();

        $base = 132;
        $base += $age < 25 ? 48 : 0;
        $base -= $experience >= 10 ? 14 : 0;
        $base += in_array($vehicle['body_type'] ?? '', array('Truck', 'SUV'), true) ? 8 : 0;
        $base -= in_array($vehicle['make'] ?? '', array('Toyota', 'Honda', 'Subaru'), true) ? 6 : 0;

        $province_multipliers = array(
            'BC' => 1.22,
            'AB' => 1.00,
            'ON' => 1.28,
        );

        $province_labels = array(
            'BC' => 'British Columbia',
            'AB' => 'Alberta',
            'ON' => 'Ontario',
        );

        $selected_multiplier = $province_multipliers[$province] ?? 1.00;
        $monthly = max(95, (int) round($base * $selected_multiplier));
        $annual = $monthly * 12;
        $risk = 'Average';
        if ($monthly < 130) {
            $risk = 'Low Insurance Cost';
        } elseif ($monthly > 175) {
            $risk = 'High Insurance Cost';
        }

        $province_rows = array();
        foreach ($province_multipliers as $code => $multiplier) {
            $average = max(1200, (int) round($base * $multiplier * 12));
            $province_rows[] = array(
                'code' => $code,
                'label' => $province_labels[$code],
                'low' => (int) round($average * 0.78),
                'average' => $average,
                'high' => (int) round($average * 1.52),
            );
        }

        return array(
            'monthly' => $monthly,
            'annual' => $annual,
            'risk' => $risk,
            'provinces' => $province_rows,
        );
    }

    public static function decode_vin($vin)
    {
        $vin = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $vin));

        if (strlen($vin) !== 17) {
            return array();
        }

        $makes = array(
            '1HG' => array('make' => 'Honda', 'model' => 'CR-V', 'body_type' => 'SUV'),
            '2HK' => array('make' => 'Honda', 'model' => 'Pilot', 'body_type' => 'SUV'),
            '2T3' => array('make' => 'Toyota', 'model' => 'RAV4', 'body_type' => 'SUV'),
            '1FT' => array('make' => 'Ford', 'model' => 'F-150', 'body_type' => 'Truck'),
            'JF2' => array('make' => 'Subaru', 'model' => 'Outback', 'body_type' => 'Wagon'),
        );

        $prefix = substr($vin, 0, 3);
        $decoded = $makes[$prefix] ?? array('make' => 'Unknown', 'model' => 'Unknown', 'body_type' => 'SUV');
        $year_codes = array(
            'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023, 'R' => 2024, 'S' => 2025, 'T' => 2026,
        );
        $year_code = substr($vin, 9, 1);

        return array(
            'vin' => $vin,
            'make' => $decoded['make'],
            'model' => $decoded['model'],
            'body_type' => $decoded['body_type'],
            'year' => $year_codes[$year_code] ?? 2023,
            'drivetrain' => in_array($decoded['body_type'], array('SUV', 'Truck', 'Wagon'), true) ? 'AWD' : 'FWD',
            'fuel_type' => 'Gasoline',
            'transmission' => 'Automatic',
        );
    }

    public static function get_dealer_name($dealer_user_id)
    {
        $user = $dealer_user_id ? get_userdata((int) $dealer_user_id) : null;

        if ($user && ! empty($user->display_name)) {
            return $user->display_name;
        }

        return 'AutoDrive Dealer';
    }

    public static function get_vehicle_owner_id($vehicle_id)
    {
        $dealer_user_id = (int) get_post_meta($vehicle_id, 'dealer_user_id', true);

        if ($dealer_user_id > 0) {
            return $dealer_user_id;
        }

        $post = get_post($vehicle_id);

        return $post ? (int) $post->post_author : 0;
    }

    public static function normalize_location($location)
    {
        $location = trim((string) $location);
        if ($location === '') {
            return '';
        }

        $postal_prefix = strtoupper(substr(preg_replace('/\s+/', '', $location), 0, 3));
        $postal_map = array(
            'T2P' => 'Calgary',
            'T2T' => 'Calgary',
            'T2E' => 'Calgary',
            'T2N' => 'Calgary',
            'T2R' => 'Calgary',
            'T2C' => 'Calgary',
            'T5J' => 'Edmonton',
            'T5K' => 'Edmonton',
            'T5H' => 'Edmonton',
            'T6B' => 'Edmonton',
            'T6C' => 'Edmonton',
            'T6E' => 'Edmonton',
            'T6G' => 'Edmonton',
            'T4N' => 'Red Deer',
            'T1J' => 'Lethbridge',
            'T4B' => 'Airdrie',
        );

        if (isset($postal_map[$postal_prefix])) {
            return $postal_map[$postal_prefix];
        }

        return ucwords(strtolower($location));
    }

    public static function looks_like_postal_code($value)
    {
        return (bool) preg_match('/^[A-Z]\d[A-Z][\s-]?\d[A-Z]\d$/i', trim((string) $value));
    }

    public static function city_coordinates()
    {
        return array(
            'Calgary' => array('lat' => 51.0447, 'lng' => -114.0719),
            'Edmonton' => array('lat' => 53.5461, 'lng' => -113.4938),
            'Red Deer' => array('lat' => 52.2681, 'lng' => -113.8112),
            'Lethbridge' => array('lat' => 49.6942, 'lng' => -112.8328),
            'Airdrie' => array('lat' => 51.2929, 'lng' => -114.0144),
            'Toronto' => array('lat' => 43.6532, 'lng' => -79.3832),
            'Ottawa' => array('lat' => 45.4215, 'lng' => -75.6972),
            'Vancouver' => array('lat' => 49.2827, 'lng' => -123.1207),
            'Montreal' => array('lat' => 45.5017, 'lng' => -73.5673),
            'Winnipeg' => array('lat' => 49.8951, 'lng' => -97.1384),
        );
    }

    public static function coordinate_distance_km($start, $end)
    {
        if (empty($start) || empty($end) || ! isset($start['lat'], $start['lng'], $end['lat'], $end['lng'])) {
            return null;
        }

        $earth_radius = 6371;
        $lat_from = deg2rad((float) $start['lat']);
        $lat_to = deg2rad((float) $end['lat']);
        $lat_delta = deg2rad((float) $end['lat'] - (float) $start['lat']);
        $lng_delta = deg2rad((float) $end['lng'] - (float) $start['lng']);

        $a = sin($lat_delta / 2) * sin($lat_delta / 2) + cos($lat_from) * cos($lat_to) * sin($lng_delta / 2) * sin($lng_delta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }

    public static function city_distance_allowed($vehicle_city, $origin_location, $distance_limit)
    {
        $distance_limit = (int) $distance_limit;
        if ($distance_limit <= 0) {
            return true;
        }

        $vehicle_city = self::normalize_location($vehicle_city);
        $origin_location = self::normalize_location($origin_location);

        if ($vehicle_city === '' || $origin_location === '') {
            return false;
        }

        $coordinates = self::city_coordinates();
        $vehicle_point = $coordinates[$vehicle_city] ?? null;
        $origin_point = $coordinates[$origin_location] ?? null;

        if ($vehicle_point && $origin_point) {
            $distance = self::coordinate_distance_km($origin_point, $vehicle_point);

            if (null !== $distance) {
                return $distance <= $distance_limit;
            }
        }

        return $vehicle_city === $origin_location;
    }

    public static function query_vehicles($filters)
    {
        $meta_query = array('relation' => 'AND');
        $tax_query = array('relation' => 'AND');
        $distance_limit = ! empty($filters['distance']) ? (int) $filters['distance'] : 0;
        $model_filter = ! empty($filters['model']) ? sanitize_text_field((string) $filters['model']) : '';
        $location_filter = '';
        if (! empty($filters['location'])) {
            $location_filter = $filters['location'];
        } elseif (! empty($filters['city'])) {
            $location_filter = $filters['city'];
        } elseif (! empty($filters['postalCode'])) {
            $location_filter = $filters['postalCode'];
        }

        if (! empty($filters['maxPrice'])) {
            $meta_query[] = array('key' => 'price', 'value' => (float) $filters['maxPrice'], 'type' => 'NUMERIC', 'compare' => '<=');
        }
        if (! empty($filters['maxMileage'])) {
            $meta_query[] = array('key' => 'mileage', 'value' => (int) $filters['maxMileage'], 'type' => 'NUMERIC', 'compare' => '<=');
        }
        if (! empty($filters['year'])) {
            $meta_query[] = array('key' => 'year', 'value' => (int) $filters['year'], 'type' => 'NUMERIC', 'compare' => '>=');
        }
        if (! empty($filters['reliability']) && 'high' === strtolower((string) $filters['reliability'])) {
            $meta_query[] = array('key' => 'reliability_score', 'value' => 78, 'type' => 'NUMERIC', 'compare' => '>=');
        }
        $postal_like_location = ! empty($filters['location']) && self::looks_like_postal_code($filters['location']);
        if (! $distance_limit) {
            if (! empty($filters['postalCode'])) {
                $meta_query[] = array('key' => 'postal_code', 'value' => sanitize_text_field((string) $filters['postalCode']), 'compare' => '=');
            } elseif ($postal_like_location) {
                $meta_query[] = array('key' => 'postal_code', 'value' => sanitize_text_field((string) $filters['location']), 'compare' => '=');
            } elseif (! empty($filters['location'])) {
                $meta_query[] = array('key' => 'city', 'value' => sanitize_text_field((string) $filters['location']), 'compare' => '=');
            } elseif (! empty($filters['city'])) {
                $meta_query[] = array('key' => 'city', 'value' => sanitize_text_field((string) $filters['city']), 'compare' => '=');
            }
        }

        $taxonomy_map = array(
            'make' => 'make',
            'model' => 'model',
            'bodyType' => 'body_type',
            'fuelType' => 'fuel_type',
            'province' => 'province',
            'city' => 'city',
            'drivetrain' => 'drivetrain',
        );

        foreach ($taxonomy_map as $filter_key => $taxonomy) {
            if (! empty($filters[$filter_key])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'name',
                    'terms' => array($filters[$filter_key]),
                );
            }
        }

        $args = array(
            'post_type' => 'vehicle',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'meta_query' => count($meta_query) > 1 ? $meta_query : array(),
            'tax_query' => count($tax_query) > 1 ? $tax_query : array(),
        );

        $query = new WP_Query($args);

        if ($query->post_count === 0 && $model_filter !== '') {
            $fallback_args = $args;
            unset($fallback_args['tax_query']);
            $fallback_args['s'] = $model_filter;
            $fallback_query = new WP_Query($fallback_args);

            if ($fallback_query->post_count > 0) {
                $query = $fallback_query;
            }
        }

        if ($distance_limit > 0 && $location_filter !== '') {
            $filtered_posts = array();
            foreach ($query->posts as $post) {
                $vehicle_city = (string) get_post_meta($post->ID, 'city', true);
                if (self::city_distance_allowed($vehicle_city, $location_filter, $distance_limit)) {
                    $filtered_posts[] = $post;
                }
            }

            $query->posts = $filtered_posts;
            $query->post_count = count($filtered_posts);
            $query->found_posts = count($filtered_posts);
            $query->max_num_pages = $query->post_count > 0 ? 1 : 0;
        }

        return $query;
    }

    public static function log_event($vehicle_id, $event_type, $meta_value = array())
    {
        global $wpdb;

        $wpdb->insert(
            AutoGyrus_DB::get_events_table(),
            array(
                'vehicle_id' => (int) $vehicle_id,
                'dealer_user_id' => (int) get_post_meta($vehicle_id, 'dealer_user_id', true),
                'event_type' => sanitize_key($event_type),
                'session_hash' => md5((string) wp_get_session_token()),
                'meta_value' => wp_json_encode($meta_value),
                'created_at' => current_time('mysql', 1),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }

    public static function track_vehicle_view()
    {
        if (! is_singular('vehicle')) {
            return;
        }

        $vehicle_id = get_queried_object_id();
        if (! $vehicle_id) {
            return;
        }

        self::log_event($vehicle_id, 'view');
    }

    public static function get_vehicle_favorite_count($vehicle_id)
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . AutoGyrus_DB::get_favorites_table() . " WHERE vehicle_id = %d",
                $vehicle_id
            )
        );
    }

    public static function demand_factor($vehicle)
    {
        $factor = 0.0;
        if (in_array($vehicle['make'] ?? '', array('Toyota', 'Honda', 'Subaru'), true)) {
            $factor += 0.06;
        }
        if (in_array($vehicle['body_type'] ?? '', array('SUV', 'Truck'), true)) {
            $factor += 0.03;
        }
        if (in_array($vehicle['fuel_type'] ?? '', array('Hybrid', 'Electric', 'Plug-in Hybrid'), true)) {
            $factor += 0.02;
        }

        return $factor;
    }
}
