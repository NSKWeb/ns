<?php
/**
 * NS Link - front controller for the PHP built-in server.
 *
 * Usage:  php -S 127.0.0.1:8099 router.php
 *
 * Routing:
 *   /go/<code>            -> go.php
 *   /task.php             -> task.php
 *   /admin/*              -> admin files
 *   static assets         -> served directly
 *   everything else       -> index.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve real files directly (static assets, /admin/*.php, etc.).
$real = __DIR__ . $path;
if ($path !== '/' && is_file($real)) {
    return false;
}

// Clean query string handling for /go/<code>.
if (preg_match('#^/go/([A-Za-z0-9]+)/?$#', $path, $m)) {
    $_GET['code'] = $m[1];
    require __DIR__ . '/go.php';
    return true;
}

// Everything else: landing page.
$_GET = $_REQUEST;
require __DIR__ . '/index.php';
return true;