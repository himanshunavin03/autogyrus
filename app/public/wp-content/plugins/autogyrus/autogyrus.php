<?php
/**
 * Plugin Name: AutoDrive AI Core
 * Description: Core marketplace, AI insights, dealer workflows, and REST services for AutoDrive AI.
 * Version: 1.1.0
 * Author: OpenAI Codex
 */

if (! defined('ABSPATH')) {
    exit;
}

define('AUTOGYRUS_VERSION', '1.1.0');
define('AUTOGYRUS_PATH', plugin_dir_path(__FILE__));
define('AUTOGYRUS_URL', plugin_dir_url(__FILE__));
define('AUTOGYRUS_FILE', __FILE__);

require_once AUTOGYRUS_PATH . 'includes/class-autogyrus-plugin.php';

function autogyrus()
{
    return AutoGyrus_Plugin::instance();
}

autogyrus();
