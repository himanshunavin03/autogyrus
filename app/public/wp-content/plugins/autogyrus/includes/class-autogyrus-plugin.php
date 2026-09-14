<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-post-types.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-rest.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-services.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-dashboard.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-db.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-acf.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-sample-data.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-admin.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-provider-interface.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-provider-base.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-provider-registry.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-import-service.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-compatibility.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-provider-admin.php';
require_once AUTOGYRUS_PATH . 'includes/class-provider-interface.php';
require_once AUTOGYRUS_PATH . 'includes/class-abstract-provider.php';
require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-toyota-provider.php';
require_once AUTOGYRUS_PATH . 'includes/class-toyota-provider.php';
require_once AUTOGYRUS_PATH . 'includes/class-import-pipeline.php';
require_once AUTOGYRUS_PATH . 'includes/class-validation-pipeline.php';
require_once AUTOGYRUS_PATH . 'includes/class-canonical-mapper.php';
require_once AUTOGYRUS_PATH . 'includes/class-persistence-service.php';
require_once AUTOGYRUS_PATH . 'includes/class-duplicate-detection-service.php';
require_once AUTOGYRUS_PATH . 'includes/class-conflict-resolution-service.php';
require_once AUTOGYRUS_PATH . 'includes/class-history-service.php';
require_once AUTOGYRUS_PATH . 'includes/class-audit-service.php';

class AutoGyrus_Plugin
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        AutoGyrus_ACF::register();
        add_action('init', [$this, 'boot']);
        register_activation_hook(AUTOGYRUS_FILE, [$this, 'activate']);
    }

    public function boot()
    {
        AutoGyrus_Post_Types::register();
        AutoGyrus_Services::register();
        AutoGyrus_REST::register();
        AutoGyrus_Dashboard::register();
        AutoGyrus_Sample_Data::register();
        AutoGyrus_Admin::register();
        AutoGyrus_Provider_Registry::register_defaults();
        AutoGyrus_Import_Service::register();
        AutoGyrus_Compatibility::register();
        AutoGyrus_Provider_Admin::register();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('save_post_vehicle', [$this, 'sync_vehicle_scores'], 20, 2);
        add_shortcode('autogyrus_search', [$this, 'render_search_shortcode']);
    }

    public function activate()
    {
        $this->register_roles();
        AutoGyrus_Post_Types::register();
        AutoGyrus_DB::create_tables();
        flush_rewrite_rules();
    }

    public function enqueue_assets()
    {
        wp_enqueue_script('autogyrus-search', AUTOGYRUS_URL . 'templates/search.js', ['jquery'], AUTOGYRUS_VERSION, true);
        wp_localize_script('autogyrus-search', 'AutoGyrusData', [
            'restUrl' => esc_url_raw(rest_url('autogyrus/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'siteUrl' => esc_url_raw(home_url('/')),
            'archiveUrl' => esc_url_raw(get_post_type_archive_link('vehicle')),
        ]);
    }

    public function enqueue_admin_assets()
    {
        wp_enqueue_style('dashicons');

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || 'vehicle' !== $screen->post_type) {
            return;
        }

        wp_enqueue_script('autogyrus-admin-vin', AUTOGYRUS_URL . 'assets/js/admin-vin.js', array(), AUTOGYRUS_VERSION, true);
        wp_localize_script('autogyrus-admin-vin', 'AutoGyrusAdmin', array(
            'restUrl' => esc_url_raw(rest_url('autogyrus/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function render_search_shortcode()
    {
        ob_start();
        include AUTOGYRUS_PATH . 'templates/search-form.php';
        return ob_get_clean();
    }

    public function sync_vehicle_scores($post_id, $post)
    {
        if (wp_is_post_revision($post_id) || 'vehicle' !== $post->post_type) {
            return;
        }

        AutoGyrus_Services::refresh_vehicle_scores($post_id);
    }

    private function register_roles()
    {
        add_role('dealer', 'Dealer', array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
        ));
    }
}
