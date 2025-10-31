<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Global Helper Functions (v2)
 *
 * Handles secure session validation, role hierarchy,
 * and access guards for all restricted modules.
 */

/**
 * Get the current active session.
 * Returns a user object if valid, otherwise false.
 */
function knx_get_session() {
    global $wpdb;
    $sessions_table = $wpdb->prefix . 'knx_sessions';
    $users_table    = $wpdb->prefix . 'knx_users';

    if (empty($_COOKIE['knx_session'])) {
        return false;
    }

    $token = sanitize_text_field($_COOKIE['knx_session']);
    $query = $wpdb->prepare("
        SELECT s.*, u.id AS user_id, u.username, u.email, u.role, u.status
        FROM $sessions_table s
        JOIN $users_table u ON s.user_id = u.id
        WHERE s.token = %s
        AND s.expires_at > NOW()
        AND u.status = 'active'
        LIMIT 1
    ", $token);

    $session = $wpdb->get_row($query);
    return $session ? $session : false;
}

/**
 * Require a minimum role hierarchy.
 * Returns the session object or false if unauthorized.
 */
function knx_require_role($role = 'user') {
    $session = knx_get_session();
    if (!$session) {
        return false;
    }

    $hierarchy = [
        'user'           => 1,
        'customer'       => 1,
        'menu_uploader'  => 2,
        'hub_management' => 3,
        'manager'        => 4,
        'super_admin'    => 5
    ];

    $user_role = $session->role;

    if (!isset($hierarchy[$user_role]) || !isset($hierarchy[$role])) {
        return false;
    }

    if ($hierarchy[$user_role] < $hierarchy[$role]) {
        return false;
    }

    return $session;
}

/**
 * Guard a restricted page or shortcode.
 * If unauthorized, redirect safely to the login page.
 */
function knx_guard($required_role = 'user') {
    $session = knx_require_role($required_role);

    if (!$session) {
        wp_safe_redirect(site_url('/login'));
        exit;
    }

    return $session;
}

/**
 * Secure logout handler.
 * Deletes the current session and clears the cookie.
 */
function knx_logout_user() {
    global $wpdb;
    $sessions_table = $wpdb->prefix . 'knx_sessions';

    if (isset($_COOKIE['knx_session'])) {
        $token = sanitize_text_field($_COOKIE['knx_session']);

        // Delete session from database
        $wpdb->delete($sessions_table, ['token' => $token]);

        // Clear browser cookie securely
        setcookie('knx_session', '', time() - 3600, '/', '', is_ssl(), true);
    }

    // Ensure user is redirected to home or login
    wp_safe_redirect(site_url('/login'));
    exit;
}
