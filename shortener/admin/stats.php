<?php
/**
 * NS Link - admin stats (per-step clicks).
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();
nslink_require_login();

$db = nslink_db();

// Per-chain click summary
$rows = $db->query(
    'SELECT c.chain_id, ch.code AS chain_code, ch.name AS chain_name, l.code AS link_code, l.title AS link_title, ' .
    'c.step, COUNT(*) AS clicks, MAX(c.created_at) AS last_click ' .
    'FROM clicks c ' .
    'LEFT JOIN chains ch ON ch.id = c.chain_id ' .
    'LEFT JOIN links l ON l.id = c.link_id ' .
    'GROUP BY c.chain_id, c.step, c.link_id ' .
    'ORDER BY last_click DESC LIMIT 100'
)->fetchAll();

$totals = $db->query('SELECT COUNT(*) AS n FROM clicks')->fetch();
$totalClicks = (int)$totals['n'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stats - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-topbar">
  <div class="admin-brand">NS Link Admin</div>
  <nav class="admin-nav">
    <a href="/admin/index.php">Dashboard</a>
    <a href="/admin/chains.php">Chains</a>
    <a href="/admin/links.php">Links</a>
    <a href="/admin/stats.php" class="active">Stats</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>
<main class="admin-main">
  <section class="admin-section">
    <h2>Click Stats</h2>
    <p class="muted">Total clicks recorded: <strong><?= $totalClicks ?></strong></p>
    <?php if (!$rows): ?>
      <p class="muted">No clicks yet.</p>
    <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Chain</th><th>Step link</th><th>Step</th><th>Clicks</th><th>Last click</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars((string)($r['chain_name'] ?: $r['chain_code'])) ?></td>
          <td><?= htmlspecialchars((string)($r['link_title'] ?: $r['link_code'])) ?></td>
          <td><?= (int)$r['step'] ?></td>
          <td><?= (int)$r['clicks'] ?></td>
          <td><?= htmlspecialchars($r['last_click']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</main>
</body>
</html>