<?php
/**
 * NS Link - admin links (all short links, delete).
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
        $db->prepare('DELETE FROM links WHERE id = ?')->execute([$id]);
    }
    header('Location: /admin/links.php');
    exit;
}

$links = $db->query('SELECT * FROM links ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Links - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-topbar">
  <div class="admin-brand">NS Link Admin</div>
  <nav class="admin-nav">
    <a href="/admin/index.php">Dashboard</a>
    <a href="/admin/chains.php">Chains</a>
    <a href="/admin/links.php" class="active">Links</a>
    <a href="/admin/stats.php">Stats</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>
<main class="admin-main">
  <section class="admin-section">
    <h2>All Links</h2>
    <?php if (!$links): ?>
      <p class="muted">No links yet.</p>
    <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Title</th><th>URL</th><th>Created</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($links as $l): ?>
        <tr>
          <td><a href="/go/<?= htmlspecialchars($l['code']) ?>"><?= htmlspecialchars($l['code']) ?></a></td>
          <td><?= htmlspecialchars($l['title']) ?></td>
          <td class="cell-url"><?= htmlspecialchars($l['url']) ?></td>
          <td><?= htmlspecialchars($l['created_at']) ?></td>
          <td>
            <form method="post" action="/admin/links.php" onsubmit="return confirm('Delete this link?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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