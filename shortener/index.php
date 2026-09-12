<?php
/**
 * NS Link - public entry point (index.php).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/chain.php';
require_once __DIR__ . '/auth.php';

nslink_session_start();

// Minimal landing page - simple form to create a short link (optional; admin is the primary tool.
$config = require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NS Link - URL Shortener</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="public-page">
<main class="public-card">
  <h1>NS Link</h1>
  <p class="tagline">One link, four readings, ad-funded press run.</p>
  <p>This is a URL shortener + chain generator web app. Admin panel: <a href="/admin/">/admin/</a></p>
</main>
</body>
</html>