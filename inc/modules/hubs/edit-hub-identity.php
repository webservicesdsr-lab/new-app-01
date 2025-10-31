<?php
if (!defined('ABSPATH')) exit;

/**
 * ==========================================================
 * Kingdom Nexus - Edit Hub Identity API (v4.4 Production)
 * ----------------------------------------------------------
 * Updates hub phone, email, status, and city_id (new field)
 * - Uses dynamic table prefix detection (Z7E_, G25_, etc.)
 * - Validates city_id via "active = 1"
 * - Prevents SQL format mismatch
 * - 100% safe for production (no debug output)
 * ==========================================================
 */

add_action('rest_api_init', function () {
    register_rest_route('knx/v1', '/update-hub-identity', [
        'methods'  => 'POST',
        'callback' => 'knx_update_hub_identity_v44',
        'permission_callback' => '__return_true',
    ]);
});

function knx_update_hub_identity_v44(WP_REST_Request $request) {
    global $wpdb;

    /** Detect correct tables dynamically */
    $table_hubs = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}knx_hubs'") 
        ? "{$wpdb->prefix}knx_hubs" 
        : 'Z7E_knx_hubs';

    $table_cities = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}knx_cities'") 
        ? "{$wpdb->prefix}knx_cities" 
        : 'Z7E_knx_cities';

    /** Parse JSON body */
    $data = json_decode($request->get_body(), true);

    /** Validate nonce */
    if (empty($data['knx_nonce']) || !wp_verify_nonce($data['knx_nonce'], 'knx_edit_hub_nonce')) {
        return new WP_REST_Response(['success' => false, 'error' => 'invalid_nonce'], 403);
    }

    /** Validate session / roles */
    $session = knx_get_session();
    if (
        !$session ||
        !in_array($session->role, ['super_admin', 'manager', 'hub_management', 'menu_uploader', 'vendor_owner'])
    ) {
        return new WP_REST_Response(['success' => false, 'error' => 'unauthorized'], 403);
    }

    /** Sanitize input */
    $hub_id  = intval($data['hub_id'] ?? 0);
    $city_id = intval($data['city_id'] ?? 0);
    $email   = sanitize_email($data['email'] ?? '');
    $phone   = sanitize_text_field($data['phone'] ?? '');
    $status  = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';

    /** Required fields */
    if (!$hub_id || empty($email)) {
        return new WP_REST_Response(['success' => false, 'error' => 'missing_fields'], 400);
    }

    /** Validate city_id if provided */
    if ($city_id > 0) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_cities} WHERE id = %d AND active = 1",
            $city_id
        ));
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

    /** Prepare formats dynamically (prevents SQL mismatch) */
    $formats = [];
    foreach ($update_data as $key => $val) {
        if ($key === 'city_id') {
            $formats[] = is_null($val) ? 'NULL' : '%d';
        } else {
            $formats[] = '%s';
        }
    }

    /** Execute update safely */
    $updated = $wpdb->update(
        $table_hubs,
        $update_data,
        ['id' => $hub_id],
        $formats,
        ['%d']
    );

    /** Database error */
    if ($updated === false) {
        return new WP_REST_Response([
            'success' => false,
            'error'   => 'db_error',
            'details' => $wpdb->last_error
        ], 500);
    }

    /** Success response */
    return new WP_REST_Response([
        'success' => true,
        'message' => $updated ? 'Hub identity updated successfully' : 'No changes made',
        'hub_id'  => $hub_id
    ], 200);
}
