<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================================
 * Kingdom Nexus - API: Get Hub (v4.0 Production)
 * ----------------------------------------------------------
 * Retrieves hub data by ID for edit-hub template.
 * Supports city_id for dropdown and dynamic table prefixes.
 * ==========================================================
 */

add_action('rest_api_init', function() {
    register_rest_route('knx/v1', '/get-hub', [
        'methods'  => 'GET',
        'callback' => 'knx_api_get_hub_v40',
        'permission_callback' => '__return_true',
    ]);
});

function knx_api_get_hub_v40(WP_REST_Request $r) {
    global $wpdb;

    /** Detect correct table (supports dynamic prefixes) */
    $table_hubs   = $wpdb->prefix . 'knx_hubs';
    $table_cities = $wpdb->prefix . 'knx_cities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_hubs'") != $table_hubs)
        $table_hubs = 'Z7E_knx_hubs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_cities'") != $table_cities)
        $table_cities = 'Z7E_knx_cities';

    /** Get hub ID */
    $hub_id = intval($r->get_param('id'));
    if (!$hub_id) {
        return new WP_REST_Response([
            'success' => false,
            'error'   => 'missing_id'
        ], 400);
    }

    /** Fetch hub data */
    $hub = $wpdb->get_row($wpdb->prepare("
        SELECT h.*, c.name AS city_name
        FROM {$table_hubs} h
        LEFT JOIN {$table_cities} c ON h.city_id = c.id
        WHERE h.id = %d
        LIMIT 1
    ", $hub_id));

    if (!$hub) {
        return new WP_REST_Response([
            'success' => false,
            'error'   => 'hub_not_found'
        ], 404);
    }

    /** Normalize response */
    $response = [
        'id'        => intval($hub->id),
        'name'      => stripslashes($hub->name),
        'email'     => $hub->email,
        'phone'     => $hub->phone,
        'status'    => $hub->status,
        'city_id'   => intval($hub->city_id ?? 0),
        'city_name' => $hub->city_name ?? '',
        'address'   => $hub->address,
        'lat'       => $hub->latitude,
        'lng'       => $hub->longitude,
        'logo_url'  => $hub->logo_url,
        'delivery_radius' => $hub->delivery_radius,
        'timezone'  => $hub->timezone,
        'currency'  => $hub->currency,
    ];

    return new WP_REST_Response([
        'success' => true,
        'hub'     => $response
    ], 200);
}
