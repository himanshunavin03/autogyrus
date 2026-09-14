<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_DB
{
    public static function create_tables()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $favorites_table = $wpdb->prefix . 'autogyrus_favorites';
        $events_table = $wpdb->prefix . 'autogyrus_vehicle_events';

        $sql = "
        CREATE TABLE {$favorites_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_vehicle (user_id, vehicle_id),
            KEY vehicle_id (vehicle_id)
        ) {$charset_collate};

        CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            dealer_user_id BIGINT UNSIGNED DEFAULT 0,
            event_type VARCHAR(50) NOT NULL,
            session_hash VARCHAR(64) DEFAULT '',
            meta_value LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY vehicle_id (vehicle_id),
            KEY dealer_user_id (dealer_user_id),
            KEY event_type (event_type)
        ) {$charset_collate};
        ";

        dbDelta($sql);
    }

    public static function get_favorites_table()
    {
        global $wpdb;

        return $wpdb->prefix . 'autogyrus_favorites';
    }

    public static function get_events_table()
    {
        global $wpdb;

        return $wpdb->prefix . 'autogyrus_vehicle_events';
    }
}
