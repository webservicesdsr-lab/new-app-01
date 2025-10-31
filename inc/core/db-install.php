<?php
if (!defined('ABSPATH')) exit;

/**
 * Kingdom Nexus - Database Installer (v2)
 * 
 * Creates secure and prefixed tables for authentication and session management.
 * Uses $wpdb->prefix to remain dynamic across environments.
 * Designed to coexist with WordPress without interfering with core tables.
 */

/**
 * Creates all required tables for the Kingdom Nexus system.
 */
function knx_install_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Dynamic table names
    $users_table    = $wpdb->prefix . 'knx_users';
    $sessions_table = $wpdb->prefix . 'knx_sessions';

    // Users table
    $sql_users = "CREATE TABLE IF NOT EXISTS $users_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(191) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin','manager','menu_uploader','hub_management','driver','customer','user') DEFAULT 'user',
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (role)
    ) $charset_collate;";

    // Sessions table
    $sql_sessions = "CREATE TABLE IF NOT EXISTS $sessions_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token CHAR(64) NOT NULL UNIQUE,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES $users_table(id) ON DELETE CASCADE,
        INDEX (token),
        INDEX (expires_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_users);
    dbDelta($sql_sessions);
}

/**
 * Optional helper: verifies tables exist and are correctly structured.
 * Can be used later for maintenance or debugging.
 */
function knx_verify_tables() {
    global $wpdb;
    $required = [$wpdb->prefix . 'knx_users', $wpdb->prefix . 'knx_sessions'];
    foreach ($required as $table) {
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            error_log("Table missing or misconfigured: {$table}");
        }
    }
}
