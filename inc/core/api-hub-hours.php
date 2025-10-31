<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - API: Save Hub Hours (v4.0)
 * ------------------------------------------------
 * Stores structured weekly opening hours for each Hub.
 * Sunday remains locked (handled by JS).
 */

add_action('rest_api_init', function () {
    register_rest_route('knx/v1', '/save-hours', [
        'methods'  => 'POST',
        'callback' => 'knx_api_save_hours',
        'permission_callback' => '__return_true',
    ]);
});

function knx_api_save_hours(WP_REST_Request $r)
{
    global $wpdb;

    /** Detect correct table (supports prefixed installs) */
    $table = $wpdb->prefix . 'knx_hubs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table)
        $table = 'Z7E_knx_hubs';

    $hub_id = intval($r['hub_id']);
    $nonce  = sanitize_text_field($r['knx_nonce']);
    $hours  = $r['hours'];

    if (!$hub_id || !$hours || !is_array($hours))
        return ['success' => false, 'error' => 'invalid_schedule'];

    // Sunday always locked (ignore changes)
    unset($hours['sunday']);

    $json = wp_json_encode($hours);
    $wpdb->update($table, ['opening_hours' => $json], ['id' => $hub_id]);

    return ['success' => true];
}
