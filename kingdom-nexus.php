<?php
/**
 * Plugin Name: Kingdom Nexus
 * Description: Modular secure framework for authentication, roles, and dashboards with smart redirects.
 * Version: 2.8.0
 * Author: Kingdom Builders
 */

if (!defined('ABSPATH')) exit;

define('KNX_PATH', plugin_dir_path(__FILE__));
define('KNX_URL', plugin_dir_url(__FILE__));
define('KNX_VERSION', '2.8.0');

if (!is_ssl() && !defined('WP_DEBUG')) {
    if (strpos($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '', 'https') !== false) {
        $_SERVER['HTTPS'] = 'on';
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

function knx_require($r) {
    $p = KNX_PATH . ltrim($r, '/');
    if (file_exists($p)) require_once $p;
}

add_action('plugins_loaded', function() {

    knx_require('inc/functions/helpers.php');
    knx_require('inc/functions/security.php');
    knx_require('inc/core/db-install.php');
    knx_require('inc/core/session-cleaner.php');
    knx_require('inc/core/api-settings.php');
    knx_require('inc/core/api.php');
    knx_require('inc/core/api-get-hub.php');
    knx_require('inc/core/api-cities.php');
    knx_require('inc/core/api-edit-city.php');
    knx_require('inc/core/api-delivery-rates.php');

    knx_require('inc/core/api-hub-items.php');
    knx_require('inc/core/api-reorder-item.php');

    knx_require('inc/core/api-get-item-categories.php');
    knx_require('inc/core/api-save-item-category.php');
    knx_require('inc/core/api-reorder-item-category.php');
    knx_require('inc/core/api-toggle-item-category.php');
    knx_require('inc/core/api-delete-item-category.php');
    
    knx_require('inc/core/api-get-item-details.php');
    knx_require('inc/core/api-update-item.php');
    knx_require('inc/modules/items/edit-item.php');

    // Modifiers system (WIX-style)
    knx_require('inc/core/api-modifiers.php');

    $core_apis = [
        'api-edit-hub-identity.php',
        'api-edit-hub-location.php',
        'api-upload-logo.php',
        'api-update-settings.php',
        'api-hub-hours.php',
        'api-update-closure.php',
    ];
    foreach ($core_apis as $f) knx_require('inc/core/' . $f);

    knx_require('inc/modules/hubs/hubs-shortcode.php');
    knx_require('inc/modules/hubs/edit-hub-template.php');
    knx_require('inc/modules/hubs/edit-hub-identity.php');
    knx_require('inc/modules/cities/cities-shortcode.php');
    knx_require('inc/modules/cities/edit-city.php');

    knx_require('inc/modules/items/edit-hub-items.php');
    knx_require('inc/modules/items/edit-item-categories.php');
    // Optional frontend app (menu uploader) loader. The loader will enqueue
    // a built bundle at menu-uploading-frontend/dist or point to the Vite dev
    // server during development.
    knx_require('inc/modules/items/menu-uploading-frontend/loader.php');

    knx_require('inc/modules/navbar/navbar-render.php');
    knx_require('inc/modules/sidebar/sidebar-render.php');

    knx_require('inc/modules/auth/auth-shortcode.php');
    knx_require('inc/modules/auth/auth-handler.php');
    knx_require('inc/modules/auth/auth-redirects.php');

    knx_require('inc/modules/home/home-shortcode.php');
    knx_require('inc/modules/admin/admin-menu.php');
});

if (!wp_next_scheduled('knx_hourly_cleanup')) {
    wp_schedule_event(time(), 'hourly', 'knx_hourly_cleanup');
}
add_action('knx_hourly_cleanup', 'knx_cleanup_sessions');

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('knx-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', [], '6.5.1');
    wp_enqueue_style('knx-toast', KNX_URL . 'inc/modules/core/knx-toast.css', [], KNX_VERSION);
    wp_enqueue_script('knx-toast', KNX_URL . 'inc/modules/core/knx-toast.js', [], KNX_VERSION, true);
    wp_enqueue_style('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css', [], null);
    wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', [], null, true);
});

if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function() {
        error_log('Kingdom Nexus v2.6 loaded (Hubs, Cities, Items, Categories).');
    });
}
