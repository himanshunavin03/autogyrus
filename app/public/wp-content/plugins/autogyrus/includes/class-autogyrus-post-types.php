<?php
if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Post_Types
{
    public static function sanitize_text_meta($value)
    {
        return sanitize_text_field((string) $value);
    }

    public static function sanitize_number_meta($value)
    {
        return is_numeric($value) ? (float) $value : 0;
    }

    public static function sanitize_integer_meta($value)
    {
        return absint($value);
    }

    public static function sanitize_boolean_meta($value)
    {
        return rest_sanitize_boolean($value);
    }

    public static function sanitize_rich_text_meta($value)
    {
        return wp_kses_post((string) $value);
    }

    public static function register()
    {
        register_post_type('vehicle', array(
            'labels' => array(
                'name' => 'Vehicles',
                'singular_name' => 'Vehicle',
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-car',
            'has_archive' => true,
            'rewrite' => array('slug' => 'vehicle', 'with_front' => false),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'menu_position' => 5,
        ));

        register_post_type('dealer', array(
            'labels' => array(
                'name' => 'Dealers',
                'singular_name' => 'Dealer',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-store',
            'supports' => array('title', 'editor', 'thumbnail'),
        ));

        register_post_type('lead', array(
            'labels' => array(
                'name' => 'Leads',
                'singular_name' => 'Lead',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-email',
            'supports' => array('title', 'custom-fields'),
        ));

        foreach (array('make', 'model', 'body_type', 'fuel_type', 'transmission', 'province', 'city', 'drivetrain') as $taxonomy) {
            register_taxonomy($taxonomy, 'vehicle', array(
                'label' => ucwords(str_replace('_', ' ', $taxonomy)),
                'public' => true,
                'show_in_rest' => true,
                'hierarchical' => true,
                'rewrite' => array('slug' => str_replace('_', '-', $taxonomy)),
            ));
        }

        foreach (self::meta_schema() as $meta_key => $schema) {
            register_post_meta('vehicle', $meta_key, array_merge(array(
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ), $schema));
        }

        add_filter('manage_vehicle_posts_columns', array(__CLASS__, 'vehicle_columns'));
        add_action('manage_vehicle_posts_custom_column', array(__CLASS__, 'render_vehicle_column'), 10, 2);
        add_action('save_post_vehicle', array(__CLASS__, 'sync_vehicle_terms'), 15, 1);
        add_action('save_post_vehicle', array(__CLASS__, 'sync_vehicle_slug'), 16, 2);
    }

    public static function meta_schema()
    {
        return array(
            'vin' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'make' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'model' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'year' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'trim' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'mileage' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'price' => array('type' => 'number', 'sanitize_callback' => array(__CLASS__, 'sanitize_number_meta')),
            'transmission' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'fuel_type' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'engine' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'drivetrain' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'body_type' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'exterior_color' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'interior_color' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'tire_size' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'province_registration_history' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'block_heater' => array('type' => 'boolean', 'sanitize_callback' => array(__CLASS__, 'sanitize_boolean_meta')),
            'remote_start' => array('type' => 'boolean', 'sanitize_callback' => array(__CLASS__, 'sanitize_boolean_meta')),
            'winter_tires_included' => array('type' => 'boolean', 'sanitize_callback' => array(__CLASS__, 'sanitize_boolean_meta')),
            'service_history' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_rich_text_meta')),
            'accident_history' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_rich_text_meta')),
            'dealer_user_id' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'city' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'postal_code' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'province' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'warranty' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'financing_available' => array('type' => 'boolean', 'sanitize_callback' => array(__CLASS__, 'sanitize_boolean_meta')),
            'ground_clearance_mm' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'winter_score' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'reliability_score' => array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_integer_meta')),
            'future_value_rating' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'future_value_summary' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'reliability_summary' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'known_issues' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'common_complaints' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
            'recall_history' => array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_text_meta')),
        );
    }

    public static function sync_vehicle_terms($post_id)
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        foreach (array('make', 'model', 'body_type', 'fuel_type', 'transmission', 'province', 'city', 'drivetrain') as $taxonomy) {
            $value = get_post_meta($post_id, $taxonomy, true);

            if (! empty($value)) {
                wp_set_object_terms($post_id, array($value), $taxonomy, false);
            }
        }
    }

    public static function vehicle_columns($columns)
    {
        $columns['price'] = 'Price';
        $columns['winter_score'] = 'Winter Score';
        $columns['reliability_score'] = 'Reliability';

        return $columns;
    }

    public static function render_vehicle_column($column, $post_id)
    {
        if ('price' === $column) {
            echo esc_html('$' . number_format_i18n((float) get_post_meta($post_id, 'price', true), 0));
        }

        if ('winter_score' === $column) {
            echo esc_html((int) get_post_meta($post_id, 'winter_score', true));
        }

        if ('reliability_score' === $column) {
            echo esc_html((int) get_post_meta($post_id, 'reliability_score', true));
        }
    }

    public static function sync_vehicle_slug($post_id, $post)
    {
        if (wp_is_post_revision($post_id) || 'vehicle' !== $post->post_type) {
            return;
        }

        $year = get_post_meta($post_id, 'year', true);
        $make = sanitize_title(get_post_meta($post_id, 'make', true));
        $model = sanitize_title(get_post_meta($post_id, 'model', true));
        $trim = sanitize_title(get_post_meta($post_id, 'trim', true));
        $slug = implode('-', array_filter(array($year, $make, $model, $trim)));

        if (empty($slug) || $post->post_name === $slug) {
            return;
        }

        remove_action('save_post_vehicle', array(__CLASS__, 'sync_vehicle_slug'), 16);
        wp_update_post(array(
            'ID' => $post_id,
            'post_name' => wp_unique_post_slug($slug, $post_id, $post->post_status, 'vehicle', 0),
        ));
        add_action('save_post_vehicle', array(__CLASS__, 'sync_vehicle_slug'), 16, 2);
    }
}
