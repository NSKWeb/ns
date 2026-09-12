<?php
/**
 * NS Link - task page entry (/task.php?chain=<code>&step=<n>).
 * Public: renders the chain step page, enables continue/destination flow.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/chain.php';
require_once __DIR__ . '/auth.php';

nslink_session_start();

$code = $_GET['chain'] ?? '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($code === '') {
    http_response_code(404);
    exit('Chain not found.');
}

$chain = nslink_chain_get($code);
if ($chain === null) {
    http_response_code(404);
    exit('Chain not found.');
}

header('Content-Type: text/html; charset=utf-8');
echo nslink_chain_render($chain, $step);