<?php
/**
 * NS Link - auth helper (single admin, session-based).
 */

require_once __DIR__ . '/db.php';

function nslink_login(string $user, string $pass): bool
{
    $config = require __DIR__ . '/config.php';
    if ($user !== ($config['admin_user'] ?? 'admin')) {
        return false;
    }
    $hash = $config['admin_pass_hash'] ?? '';
    if ($hash !== '' && function_exists('password_verify')) {
        $ok = password_verify($pass, $hash);
    } else {
        $ok = hash_equals($config['admin_pass'] ?? '', $pass);
    }
    if (!$ok) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['nslink_admin'] = [
        'user' => $user,
        'login_at' => time(),
    ];
    return true;
}

function nslink_check(): bool
{
    return isset($_SESSION['nslink_admin']);
}

function nslink_require_login(): void
{
    if (!nslink_check()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function nslink_logout(): void
{
    unset($_SESSION['nslink_admin']);
    session_regenerate_id(true);
}

function nslink_session_start(): void
{
    $config = require __DIR__ . '/config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_name($config['session_name'] ?? 'nslink_admin');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($config['cookie_secure']),
        ]);
        session_start();
    }
}