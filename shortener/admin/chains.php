<?php
/**
 * NS Link - admin chains (full CRUD + preview links).
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();
nslink_require_login();

$db = nslink_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $db->prepare('SELECT steps_json FROM chains WHERE id = ?');
        $row->execute([$id]);
        $chain = $row->fetch();
        if ($chain) {
            $steps = json_decode($chain['steps_json'], true);
            foreach (($steps['steps'] ?? []) as $s) {
                if (isset($s['link_id'])) {
                    $db->prepare('DELETE FROM links WHERE id = ?')->execute([(int)$s['link_id']]);
                }
            }
        }
        $db->prepare('DELETE FROM chains WHERE id = ?')->execute([$id]);
    }
    header('Location: /admin/chains.php');
    exit;
}

$chains = $db->query('SELECT * FROM chains ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chains - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-topbar">
  <div class="admin-brand">NS Link Admin</div>
  <nav class="admin-nav">
    <a href="/admin/index.php">Dashboard</a>
    <a href="/admin/chains.php" class="active">Chains</a>
    <a href="/admin/links.php">Links</a>
    <a href="/admin/stats.php">Stats</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>
<main class="admin-main">
  <section class="admin-section">
    <h2>All Chains</h2>
    <?php if (!$chains): ?>
      <p class="muted">No chains yet. <a href="/admin/index.php">Create one</a></p>
    <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Name</th><th>Steps</th><th>Created</th><th>Short link</th><th>Chain link</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($chains as $c): ?>
        <?php $steps = json_decode($c['steps_json'], true); $n = count($steps['steps'] ?? []); ?>
        <tr>
          <td><code><?= htmlspecialchars($c['code']) ?></code></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= (int)$n ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
          <td>
            <?php if ($n > 0 && isset($steps['steps'][0]['link_id'])): ?>
              <?php
                $s = $db->prepare('SELECT code FROM links WHERE id = ?');
                $s->execute([(int)$steps['steps'][0]['link_id']]);
                $first = $s->fetch();
              ?>
              <?php if ($first): ?><a href="/go/<?= htmlspecialchars($first['code']) ?>">/go/<?= htmlspecialchars($first['code']) ?></a><?php endif; ?>
            <?php endif; ?>
          </td>
          <td><a href="/go/<?= htmlspecialchars($c['code']) ?>">/go/<?= htmlspecialchars($c['code']) ?></a></td>
          <td>
            <form method="post" action="/admin/chains.php" onsubmit="return confirm('Delete this chain and all its step links?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</main>
</body>
</html>