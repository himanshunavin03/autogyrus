<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Compatibility
{
    public static function register()
    {
        add_action('save_post_vehicle', array(__CLASS__, 'sync_vehicle_to_autogyrus'), 35, 3);
    }

    public static function sync_vehicle_to_autogyrus($post_id, $post, $update)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || ! $post || 'vehicle' !== $post->post_type) {
            return;
        }

        AutoGyrus_Import_Service::sync_wordpress_vehicle($post_id, $post);
    }
}
