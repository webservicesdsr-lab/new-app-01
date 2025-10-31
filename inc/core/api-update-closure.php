<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - API: Update Hub Closure (v1.0)
 * ----------------------------------------------------------
 * Updates hub temporary closure settings (type, reason, reopen)
 */

add_action('rest_api_init', function() {
  register_rest_route('knx/v1', '/update-closure', [
    'methods' => 'POST',
    'callback' => 'knx_update_closure',
    'permission_callback' => '__return_true',
  ]);
});

function knx_update_closure(WP_REST_Request $r) {
  global $wpdb;

  /** Detect prefixed table */
  $table = $wpdb->prefix . 'knx_hubs';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table)
    $table = 'Z7E_knx_hubs';

  /** Verify nonce */
  $hub_id = intval($r['hub_id']);
  $nonce  = sanitize_text_field($r['knx_nonce']);
  if (!wp_verify_nonce($nonce, 'knx_edit_hub_nonce')) {
    return ['success' => false, 'error' => 'invalid_nonce'];
  }

  $is_closed = intval($r['is_closed']);
  $closure_type = sanitize_text_field($r['closure_type']);
  $closure_reason = sanitize_textarea_field($r['closure_reason']);
  $reopen_date = $r['reopen_date'] ? date('Y-m-d H:i:s', strtotime($r['reopen_date'])) : null;

  $wpdb->update($table, [
    'is_closed' => $is_closed,
    'closure_type' => $is_closed ? $closure_type : null,
    'closure_reason' => $is_closed ? $closure_reason : null,
    'reopen_date' => ($is_closed && $closure_type === 'temporary') ? $reopen_date : null,
  ], ['id' => $hub_id]);

  return ['success' => true];
}
