<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Admin
{
    public static function register()
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function register_menu()
    {
        add_menu_page(
            'AutoDrive AI',
            'AutoDrive AI',
            'manage_options',
            'autogyrus-settings',
            array(__CLASS__, 'render_settings_page'),
            'dashicons-admin-generic',
            58
        );
    }

    public static function register_settings()
    {
        register_setting('autogyrus_settings', 'autogyrus_openai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));

        register_setting('autogyrus_settings', 'autogyrus_openai_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-4o-mini',
        ));
    }

    public static function render_settings_page()
    {
        $seed_url = wp_nonce_url(admin_url('admin-post.php?action=autogyrus_seed_demo'), 'autogyrus_seed_demo');
        ?>
        <div class="wrap">
            <h2>AutoDrive AI Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('autogyrus_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="autogyrus_openai_api_key">OpenAI API Key</label></th>
                        <td><input type="password" class="regular-text" id="autogyrus_openai_api_key" name="autogyrus_openai_api_key" value="<?php echo esc_attr((string) get_option('autogyrus_openai_api_key', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="autogyrus_openai_model">OpenAI Model</label></th>
                        <td><input type="text" class="regular-text" id="autogyrus_openai_model" name="autogyrus_openai_model" value="<?php echo esc_attr((string) get_option('autogyrus_openai_model', 'gpt-4o-mini')); ?>"></td>
                    </tr>
                </table>
                <?php submit_button('Save AI Settings'); ?>
            </form>

            <hr>

            <h3>Phase 1 Tools</h3>
            <p><a class="button button-primary" href="<?php echo esc_url($seed_url); ?>">Seed 50 Sample Vehicles</a></p>
            <p>Use the dealer dashboard shortcode <code>[autogyrus_dealer_dashboard]</code> on a page for dealer inventory and lead management.</p>
            <p>Use the AI search shortcode <code>[autogyrus_search]</code> anywhere you want the natural-language vehicle search box.</p>
            <p>Use the provider menus under <strong>AutoDrive AI</strong> to import Toyota inventory and review sync history.</p>
        </div>
        <?php
    }
}
