<?php
/**
 * NS Link - /go/<code> redirect handler.
 * Resolves a short code to a plain link or a chain step.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/chain.php';
require_once __DIR__ . '/auth.php';

nslink_session_start();

$code = $_GET['code'] ?? '';
$code = preg_replace('/[^A-Za-z0-9]/', '', $code);

if ($code === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Bad request - missing code.');
}

// 1. Direct short link?
$stmt = nslink_db()->prepare('SELECT id, url FROM links WHERE code = ?');
$stmt->execute([$code]);
$link = $stmt->fetch();

if ($link) {
    if (preg_match('/chain=([A-Za-z0-9]+).*step=(\d+)/', $link['url'], $m)) {
        // Chain step link -> log + render task page for that step.

        $chain = nslink_chain_get($m[1]);
        if ($chain === null) {
            http_response_code(404);
            exit('Chain not found.');
        }
        $step = (int)$m[2];
        nslink_log_click((int)$link['id'], (int)$chain['id'], $step);
        header('Content-Type: text/html; charset=utf-8');
        echo nslink_chain_render($chain, $step);
        exit;
    }
    nslink_log_click((int)$link['id'], null, 1);
    header('Location: ' . $link['url'], true, 302);
    exit;
}

// 2. Chain code itself -> redirect to its first step link
$chain = nslink_chain_get($code);
if ($chain !== null) {
    $first = $chain['steps'][0] ?? null;
    if ($first && isset($first['link_id'])) {
        // find the short link code for first step
        $stmt = nslink_db()->prepare('SELECT code FROM links WHERE id = ?');
        $stmt->execute([(int)$first['link_id']]);
        $row = $stmt->fetch();
        if ($row) {
            header('Location: /go/' . rawurlencode($row['code']), true, 302);
            exit;
        }
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
exit('Not found - this short link does not exist.');