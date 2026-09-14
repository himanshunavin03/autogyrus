<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Provider_Admin
{
    public static function register()
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
    }

    public static function register_menu()
    {
        add_submenu_page(
            'autogyrus-settings',
            'Providers',
            'Providers',
            'manage_options',
            'autogyrus-providers',
            array(__CLASS__, 'render_providers_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Imports',
            'Imports',
            'manage_options',
            'autogyrus-imports',
            array(__CLASS__, 'render_imports_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Diagnostics',
            'Diagnostics',
            'manage_options',
            'autogyrus-diagnostics',
            array(__CLASS__, 'render_diagnostics_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Import History',
            'Import History',
            'manage_options',
            'autogyrus-import-history',
            array(__CLASS__, 'render_import_history_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Mapping',
            'Mapping',
            'manage_options',
            'autogyrus-mapping',
            array(__CLASS__, 'render_mapping_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Logs',
            'Logs',
            'manage_options',
            'autogyrus-logs',
            array(__CLASS__, 'render_logs_page')
        );

        add_submenu_page(
            'autogyrus-settings',
            'Toyota Import',
            'Toyota Import',
            'manage_options',
            'autogyrus-toyota',
            array(__CLASS__, 'render_toyota_page')
        );
    }

    public static function render_providers_page()
    {
        $providers = AutoGyrus_Provider_Registry::all();
        ?>
        <div class="wrap">
            <h1>AutoGyrus Providers</h1>
            <p>Registered provider integrations are wired through a reusable import pipeline.</p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Code</th>
                        <th>Supports</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($providers as $provider) : ?>
                        <tr>
                            <td><?php echo esc_html($provider->get_name()); ?></td>
                            <td><?php echo esc_html($provider->get_code()); ?></td>
                            <td><?php echo esc_html(implode(', ', array('normalize', 'import', 'vehicle_inventory'))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_toyota_page()
    {
        global $wpdb;

        $latest_batch = $wpdb->get_row("SELECT * FROM ag_import_batch WHERE batch_type = 'toyota_inventory' ORDER BY id DESC LIMIT 1");
        $history = $wpdb->get_results("SELECT batch_code, batch_status, rows_total, rows_processed, rows_inserted, rows_errors, duration_seconds, started_at, finished_at FROM ag_import_batch WHERE batch_type = 'toyota_inventory' ORDER BY id DESC LIMIT 10");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM ag_import_log WHERE source_system = 'toyota' AND log_level = 'error'");
        $warnings = $wpdb->get_var("SELECT COUNT(*) FROM ag_import_log WHERE source_system = 'toyota' AND log_level = 'warning'");
        $vehicles = $wpdb->get_var("SELECT COUNT(*) FROM ag_vehicle_master WHERE source_system = 'toyota'");
        $last_sync = $latest_batch ? $latest_batch->finished_at : null;
        $smoke_url = wp_nonce_url(admin_url('admin-post.php?action=autogyrus_toyota_smoke_test'), 'autogyrus_toyota_smoke_test');
        ?>
        <div class="wrap">
            <h1>Toyota Provider Import</h1>
            <p>Upload Toyota dealer inventory JSON or paste raw JSON to import into AutoGyrus tables.</p>

            <div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0;">
                <div class="postbox" style="padding:16px;min-width:220px;">
                    <strong>Vehicles Imported</strong><br>
                    <span style="font-size:28px;"><?php echo esc_html((string) $vehicles); ?></span>
                </div>
                <div class="postbox" style="padding:16px;min-width:220px;">
                    <strong>Failed Records</strong><br>
                    <span style="font-size:28px;"><?php echo esc_html((string) $failed); ?></span>
                </div>
                <div class="postbox" style="padding:16px;min-width:220px;">
                    <strong>Warnings</strong><br>
                    <span style="font-size:28px;"><?php echo esc_html((string) $warnings); ?></span>
                </div>
                <div class="postbox" style="padding:16px;min-width:220px;">
                    <strong>Last Sync</strong><br>
                    <span style="font-size:18px;"><?php echo esc_html($last_sync ?: 'Not run yet'); ?></span>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('autogyrus_toyota_import'); ?>
                <input type="hidden" name="action" value="autogyrus_toyota_import">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="autogyrus_toyota_file">JSON File</label></th>
                        <td><input type="file" name="autogyrus_toyota_file" id="autogyrus_toyota_file" accept=".json,application/json"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="autogyrus_toyota_payload">Raw JSON</label></th>
                        <td><textarea name="autogyrus_toyota_payload" id="autogyrus_toyota_payload" rows="16" class="large-text code" placeholder="Paste Toyota inventory JSON here"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Import Toyota Inventory'); ?>
            </form>

            <p><a class="button" href="<?php echo esc_url($smoke_url); ?>">Run Smoke Test</a></p>

            <h2>Import History</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Processed</th>
                        <th>Inserted</th>
                        <th>Errors</th>
                        <th>Duration</th>
                        <th>Started</th>
                        <th>Finished</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($history)) : ?>
                        <?php foreach ($history as $item) : ?>
                            <tr>
                                <td><?php echo esc_html($item->batch_code); ?></td>
                                <td><?php echo esc_html($item->batch_status); ?></td>
                                <td><?php echo esc_html((string) $item->rows_total); ?></td>
                                <td><?php echo esc_html((string) $item->rows_processed); ?></td>
                                <td><?php echo esc_html((string) $item->rows_inserted); ?></td>
                                <td><?php echo esc_html((string) $item->rows_errors); ?></td>
                                <td><?php echo esc_html((string) $item->duration_seconds); ?></td>
                                <td><?php echo esc_html((string) $item->started_at); ?></td>
                                <td><?php echo esc_html((string) $item->finished_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="9">No Toyota imports have been run yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($latest_batch) : ?>
                <h2>Latest Batch Summary</h2>
                <pre style="background:#fff;border:1px solid #ddd;padding:16px;overflow:auto;"><?php echo esc_html(wp_json_encode($latest_batch, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_imports_page()
    {
        ?>
        <div class="wrap">
            <h1>AutoGyrus Imports</h1>
            <p>Use Toyota Import to load the current provider payload. Future providers plug into the same import engine.</p>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=autogyrus-toyota')); ?>">Open Toyota Import</a></p>
        </div>
        <?php
    }

    public static function render_diagnostics_page()
    {
        global $wpdb;
        $metrics = array(
            'Vehicles' => (int) $wpdb->get_var("SELECT COUNT(*) FROM ag_vehicle_master"),
            'Dealers' => (int) $wpdb->get_var("SELECT COUNT(*) FROM ag_dealer_master"),
            'Vehicle Maps' => (int) $wpdb->get_var("SELECT COUNT(*) FROM ag_vehicle_map"),
            'Import Batches' => (int) $wpdb->get_var("SELECT COUNT(*) FROM ag_import_batch"),
            'Import Logs' => (int) $wpdb->get_var("SELECT COUNT(*) FROM ag_import_log"),
        );
        ?>
        <div class="wrap">
            <h1>Diagnostics</h1>
            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                <?php foreach ($metrics as $label => $value) : ?>
                    <tr><th><?php echo esc_html($label); ?></th><td><?php echo esc_html((string) $value); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_import_history_page()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT batch_code, batch_status, rows_total, rows_processed, rows_inserted, rows_errors, started_at, finished_at FROM ag_import_batch ORDER BY id DESC LIMIT 25");
        ?>
        <div class="wrap">
            <h1>Import History</h1>
            <table class="widefat striped">
                <thead><tr><th>Batch</th><th>Status</th><th>Total</th><th>Processed</th><th>Inserted</th><th>Errors</th><th>Started</th><th>Finished</th></tr></thead>
                <tbody>
                <?php if ($rows) : foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->batch_code); ?></td>
                        <td><?php echo esc_html($row->batch_status); ?></td>
                        <td><?php echo esc_html((string) $row->rows_total); ?></td>
                        <td><?php echo esc_html((string) $row->rows_processed); ?></td>
                        <td><?php echo esc_html((string) $row->rows_inserted); ?></td>
                        <td><?php echo esc_html((string) $row->rows_errors); ?></td>
                        <td><?php echo esc_html((string) $row->started_at); ?></td>
                        <td><?php echo esc_html((string) $row->finished_at); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="8">No imports yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_mapping_page()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT source_system, external_id, dealer_id, vehicle_id, mapping_status, sync_status, last_synced_at FROM ag_vehicle_map ORDER BY id DESC LIMIT 25");
        ?>
        <div class="wrap">
            <h1>Mapping</h1>
            <table class="widefat striped">
                <thead><tr><th>Source</th><th>External ID</th><th>Dealer</th><th>Vehicle</th><th>Status</th><th>Sync</th><th>Last Sync</th></tr></thead>
                <tbody>
                <?php if ($rows) : foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->source_system); ?></td>
                        <td><?php echo esc_html($row->external_id); ?></td>
                        <td><?php echo esc_html((string) $row->dealer_id); ?></td>
                        <td><?php echo esc_html((string) $row->vehicle_id); ?></td>
                        <td><?php echo esc_html($row->mapping_status); ?></td>
                        <td><?php echo esc_html($row->sync_status); ?></td>
                        <td><?php echo esc_html((string) $row->last_synced_at); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7">No mappings yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_logs_page()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT log_level, log_code, log_message, event_at FROM ag_import_log ORDER BY id DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1>Logs</h1>
            <table class="widefat striped">
                <thead><tr><th>Level</th><th>Code</th><th>Message</th><th>At</th></tr></thead>
                <tbody>
                <?php if ($rows) : foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->log_level); ?></td>
                        <td><?php echo esc_html((string) $row->log_code); ?></td>
                        <td><?php echo esc_html(wp_strip_all_tags((string) $row->log_message)); ?></td>
                        <td><?php echo esc_html((string) $row->event_at); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="4">No logs yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
