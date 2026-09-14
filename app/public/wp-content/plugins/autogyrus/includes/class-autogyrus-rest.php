<?php
if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_REST
{
    public static function register()
    {
        add_action('rest_api_init', function () {
            register_rest_route('autogyrus/v1', '/search', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'search'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('autogyrus/v1', '/vehicle/(?P<id>\d+)/insights', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'vehicle_insights'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('autogyrus/v1', '/vehicle/(?P<id>\d+)/insurance', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'insurance_estimate'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('autogyrus/v1', '/vehicle/(?P<id>\d+)/favorite', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'toggle_favorite'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ));

            register_rest_route('autogyrus/v1', '/vehicle/(?P<id>\d+)/lead', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_lead'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('autogyrus/v1', '/vin-decode', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'vin_decode'),
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));

            register_rest_route('autogyrus/v1', '/dealer/dashboard', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'dealer_dashboard'),
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));
        });
    }

    public static function search(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        $filters = AutoGyrus_Services::parse_natural_language_query($payload['query'] ?? '');
        $query = AutoGyrus_Services::query_vehicles($filters);
        $results = array();

        foreach ($query->posts as $post) {
            $snapshot = AutoGyrus_Services::build_vehicle_snapshot($post->ID);
            $results[] = array(
                'id' => $post->ID,
                'title' => $snapshot['title'],
                'link' => $snapshot['link'],
                'price' => $snapshot['price'],
                'mileage' => $snapshot['mileage'],
                'make' => $snapshot['make'],
                'model' => $snapshot['model'],
                'year' => $snapshot['year'],
                'thumbnail' => $snapshot['thumbnail'],
                'dealer_name' => $snapshot['dealer_name'],
                'winter_score' => $snapshot['winter']['score'],
                'reliability_score' => $snapshot['reliability']['score'],
                'future_value_rating' => $snapshot['future_value']['rating'],
            );
        }

        return rest_ensure_response(array('filters' => $filters, 'results' => $results));
    }

    public static function vehicle_insights(WP_REST_Request $request)
    {
        $vehicle_id = (int) $request['id'];

        if ('vehicle' !== get_post_type($vehicle_id)) {
            return new WP_Error('invalid_vehicle', 'Vehicle not found.', array('status' => 404));
        }

        return rest_ensure_response(AutoGyrus_Services::build_vehicle_snapshot($vehicle_id));
    }

    public static function insurance_estimate(WP_REST_Request $request)
    {
        $vehicle_id = (int) $request['id'];
        $vehicle = AutoGyrus_Services::build_vehicle_snapshot($vehicle_id);
        $payload = $request->get_json_params();
        $payload['vehicle'] = $vehicle;

        return rest_ensure_response(AutoGyrus_Services::insurance_estimate(null, $payload));
    }

    public static function toggle_favorite(WP_REST_Request $request)
    {
        global $wpdb;

        $vehicle_id = (int) $request['id'];
        $user_id = get_current_user_id();
        $table = AutoGyrus_DB::get_favorites_table();
        $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d AND vehicle_id = %d", $user_id, $vehicle_id));

        if ($exists) {
            $wpdb->delete($table, array('id' => $exists), array('%d'));
            return rest_ensure_response(array('favorited' => false, 'count' => AutoGyrus_Services::get_vehicle_favorite_count($vehicle_id)));
        }

        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'vehicle_id' => $vehicle_id,
            'created_at' => current_time('mysql', 1),
        ), array('%d', '%d', '%s'));

        AutoGyrus_Services::log_event($vehicle_id, 'favorite');

        return rest_ensure_response(array('favorited' => true, 'count' => AutoGyrus_Services::get_vehicle_favorite_count($vehicle_id)));
    }

    public static function create_lead(WP_REST_Request $request)
    {
        $vehicle_id = (int) $request['id'];
        $payload = $request->get_json_params();

        $post_id = wp_insert_post(array(
            'post_type' => 'lead',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field(($payload['name'] ?? 'Buyer') . ' - ' . get_the_title($vehicle_id)),
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, 'vehicle_id', $vehicle_id);
        update_post_meta($post_id, 'lead_name', sanitize_text_field($payload['name'] ?? ''));
        update_post_meta($post_id, 'lead_email', sanitize_email($payload['email'] ?? ''));
        update_post_meta($post_id, 'lead_phone', sanitize_text_field($payload['phone'] ?? ''));
        update_post_meta($post_id, 'lead_type', sanitize_text_field($payload['type'] ?? 'contact'));
        update_post_meta($post_id, 'lead_message', sanitize_textarea_field($payload['message'] ?? ''));

        AutoGyrus_Services::log_event($vehicle_id, sanitize_key($payload['type'] ?? 'lead'));

        return rest_ensure_response(array('success' => true, 'lead_id' => $post_id));
    }

    public static function vin_decode(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();

        return rest_ensure_response(AutoGyrus_Services::decode_vin($payload['vin'] ?? ''));
    }

    public static function dealer_dashboard()
    {
        global $wpdb;

        $dealer_user_id = get_current_user_id();
        $vehicle_ids = get_posts(array(
            'post_type' => 'vehicle',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'author' => $dealer_user_id,
        ));

        if (empty($vehicle_ids)) {
            $vehicle_ids = get_posts(array(
                'post_type' => 'vehicle',
                'post_status' => 'publish',
                'fields' => 'ids',
                'posts_per_page' => 20,
            ));
        }

        $events_table = AutoGyrus_DB::get_events_table();
        $lead_count = 0;

        if (! empty($vehicle_ids)) {
            $placeholders = implode(',', array_fill(0, count($vehicle_ids), '%d'));
            $lead_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$events_table} WHERE vehicle_id IN ({$placeholders}) AND event_type IN ('lead','test_drive','contact')", $vehicle_ids));
        }

        $vehicles = array();
        foreach ($vehicle_ids as $vehicle_id) {
            $snapshot = AutoGyrus_Services::build_vehicle_snapshot($vehicle_id);
            $vehicles[] = array(
                'id' => $vehicle_id,
                'title' => $snapshot['title'],
                'views' => self::count_vehicle_events($vehicle_id, 'view'),
                'leads' => self::count_vehicle_events($vehicle_id, 'lead') + self::count_vehicle_events($vehicle_id, 'contact') + self::count_vehicle_events($vehicle_id, 'test_drive'),
                'favorites' => AutoGyrus_Services::get_vehicle_favorite_count($vehicle_id),
                'winter_score' => $snapshot['winter']['score'],
                'reliability_score' => $snapshot['reliability']['score'],
                'future_value_rating' => $snapshot['future_value']['rating'],
            );
        }

        return rest_ensure_response(array(
            'inventory_count' => count($vehicle_ids),
            'lead_count' => $lead_count,
            'vehicles' => $vehicles,
        ));
    }

    private static function count_vehicle_events($vehicle_id, $event_type)
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . AutoGyrus_DB::get_events_table() . " WHERE vehicle_id = %d AND event_type = %s",
                $vehicle_id,
                $event_type
            )
        );
    }
}
