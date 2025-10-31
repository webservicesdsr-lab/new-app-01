<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================================
 * Kingdom Nexus - API: Update Hub Identity (v4.3 Production)
 * ----------------------------------------------------------
 * Updates hub identity information:
 * ✅ City ID (active cities only)
 * ✅ Email, Phone, and Status
 * ✅ Secure nonce + session role validation
 * ✅ Dynamic prefix detection (Z7E_, wp_, etc.)
 * ✅ Safe for production (no debug logs)
 * ==========================================================
 */

add_action('rest_api_init', function () {
    register_rest_route('knx/v1', '/update-hub-identity', [
        'methods'  => 'POST',
        'callback' => 'knx_api_update_hub_identity_v43',
        'permission_callback' => '__return_true',
    ]);
});

function knx_api_update_hub_identity_v43(WP_REST_Request $request) {
    global $wpdb;

    /** Detect correct tables */
    $table_hubs   = $wpdb->prefix . 'knx_hubs';
    $table_cities = $wpdb->prefix . 'knx_cities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_hubs'") != $table_hubs)
        $table_hubs = 'Z7E_knx_hubs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_cities'") != $table_cities)
        $table_cities = 'Z7E_knx_cities';

    /** Parse JSON body */
    $data = json_decode($request->get_body(), true);

    /** Validate nonce */
    $nonce = sanitize_text_field($data['knx_nonce'] ?? '');
    if (empty($nonce) || !wp_verify_nonce($nonce, 'knx_edit_hub_nonce')) {
        return new WP_REST_Response(['success' => false, 'error' => 'invalid_nonce'], 403);
    }

    /** Validate session / roles */
    $session = knx_get_session();
    if (!$session || !in_array($session->role, ['super_admin', 'manager', 'hub_management', 'menu_uploader', 'vendor_owner'])) {
        return new WP_REST_Response(['success' => false, 'error' => 'unauthorized'], 403);
    }

    /** Sanitize inputs */
    $hub_id  = intval($data['hub_id'] ?? 0);
    $city_id = intval($data['city_id'] ?? 0);
    $email   = sanitize_email($data['email'] ?? '');
    $phone   = sanitize_text_field($data['phone'] ?? '');
    $status  = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';

    /** Validate required fields */
    if (!$hub_id || empty($email)) {
        return new WP_REST_Response(['success' => false, 'error' => 'missing_fields'], 400);
    }

    /** Verify city exists and is active (if selected) */
    if ($city_id > 0) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_cities} WHERE id = %d AND active = 1", $city_id));
        if (!$exists) {
            return new WP_REST_Response(['success' => false, 'error' => 'invalid_city'], 404);
        }
    }

    /** Prepare update data */
    $update_data = [
        'email'   => $email,
        'phone'   => $phone,
        'status'  => $status,
        'city_id' => $city_id ?: null
    ];

    /** Perform update */
    $updated = $wpdb->update(
        $table_hubs,
        $update_data,
        ['id' => $hub_id],
        ['%s', '%s', '%s', $city_id ? '%d' : 'NULL'],
        ['%d']
    );

    /** Handle DB result */
    if ($updated === false) {
        return new WP_REST_Response([
            'success' => false,
            'error'   => 'db_error'
        ], 500);
    }

    /** Success response */
    return new WP_REST_Response([
        'success'   => true,
        'message'   => $updated ? 'Hub identity updated successfully' : 'No changes made',
        'hub_id'    => $hub_id,
        'timestamp' => current_time('mysql')
    ], 200);
}
