<?php
/**
 * NS Link - admin logout.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();
nslink_logout();
header('Location: /admin/login.php');
exit;