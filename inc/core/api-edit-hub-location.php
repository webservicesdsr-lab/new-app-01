<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Edit Hub Location API (v2.2)
 * ---------------------------------------------
 * Updates address, latitude, longitude, delivery_radius.
 */

add_action('rest_api_init', function () {
    register_rest_route('knx/v1', '/update-hub-location', [
        'methods' => 'POST',
        'callback' => 'knx_update_hub_location',
        'permission_callback' => '__return_true',
    ]);
});

function knx_update_hub_location(WP_REST_Request $request)
{
    global $wpdb;
    $table = $wpdb->prefix . 'knx_hubs'; // example: Z7E_knx_hubs

    $data = json_decode($request->get_body(), true);

    // --- Security ---
    if (empty($data['knx_nonce']) || !wp_verify_nonce($data['knx_nonce'], 'knx_edit_hub_nonce')) {
        return knx_json_response(false, ['error' => 'invalid_nonce'], 403);
    }

    $session = knx_get_session();
    if (!$session || !in_array($session->role, ['super_admin', 'manager', 'hub_management'])) {
        return knx_json_response(false, ['error' => 'unauthorized'], 403);
    }

    // --- Data ---
    $hub_id = intval($data['hub_id'] ?? 0);
    if (!$hub_id) {
        return knx_json_response(false, ['error' => 'missing_hub_id'], 400);
    }

    $address = sanitize_text_field($data['address'] ?? '');
    $lat     = floatval($data['lat'] ?? 0);
    $lng     = floatval($data['lng'] ?? 0);
    $radius  = floatval($data['delivery_radius'] ?? 0);

    // --- Update ---
    $updated = $wpdb->update(
        $table,
        [
            'address'         => $address,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'delivery_radius' => $radius,
        ],
        ['id' => $hub_id],
        ['%s', '%f', '%f', '%f'],
        ['%d']
    );

    if ($updated === false) {
        return knx_json_response(false, ['error' => 'db_error'], 500);
    }

    return knx_json_response(true, ['message' => '✅ Location updated successfully']);
}
