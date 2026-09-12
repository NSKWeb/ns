<?php
/**
 * NS Link - URL shortener web app (Part 2)
 * Configuration. CHANGE THE ADMIN PASSWORD BEFORE GOING LIVE.
 */

return [
    // SQLite database file (relative to this file)
    'db_file' => __DIR__ . '/data/ns-link.sqlite',

    // Single admin account
    'admin_user' => 'admin',
    // CHANGE THIS - default password
    'admin_pass' => 'ns-admin-2026',
    'admin_pass_hash' => '', // if set, overrides admin_pass (password_hash result)

    // Site URLs
    'base_url'  => '', // e.g. https://short.example.com  (auto-detected if empty)
    'default_wait' => 8, // seconds per chain step

    // Session
    'session_name' => 'nslink_admin',
    'cookie_secure' => false, // set true when serving over HTTPS

    // Security
    'allowed_hosts' => [], // optional whitelist of Host headers
];