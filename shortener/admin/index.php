<?php
/**
 * NS Link - admin dashboard (links CRUD + chain admin links).
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../chain.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();
nslink_require_login();

$db = nslink_db();

// Handle simple actions.
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_link') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM links WHERE id = ?')->execute([$id]);
    }

    if ($action === 'delete_chain') {
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

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $dest = trim($_POST['dest'] ?? '');
        $rawSteps = $_POST['steps'] ?? [];
        $links = [];
        $count = isset($_POST['count']) ? (int)$_POST['count'] : count($rawSteps);
        for ($i = 1; $i <= $count; $i++) {
            $url = trim((string)($_POST['step_url_' . $i] ?? ''));
            if ($url === '') { continue; }
            $wait = (int)($_POST['step_wait_' . $i] ?? 8);
            $links[] = ['url' => $url, 'wait' => $wait];
        }
        if ($links === [] && $dest === '') {
            header('Location: /admin/index.php?err=1');
            exit;
        }
        if ($links === []) {
            // Simple single redirect link: /go/<code> -> dest.
            $code = nslink_random_code(6);
            $stmt = $db->prepare('INSERT INTO links (code, url, title) VALUES (?, ?, ?)');
            $stmt->execute([$code, $dest, $name !== '' ? $name : 'Short link']);
            header('Location: /admin/index.php?link=' . $code);
            exit;
        }
        $chain = nslink_chain_create($name, nslink_chain_build($links, $dest));
        header('Location: /admin/index.php?saved=1&chain=' . $chain['code']);
        exit;
    }

    header('Location: /admin/index.php');
    exit;
}

$links = $db->query('SELECT * FROM links ORDER BY id DESC LIMIT 25')->fetchAll();
$chains = $db->query('SELECT * FROM chains ORDER BY id DESC LIMIT 25')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-topbar">
  <div class="admin-brand">NS Link Admin</div>
  <nav class="admin-nav">
    <a href="/admin/index.php" class="active">Dashboard</a>
    <a href="/admin/chains.php">Chains</a>
    <a href="/admin/links.php">Links</a>
    <a href="/admin/stats.php">Stats</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>
<main class="admin-main">
  <?php if (isset($_GET['err'])): ?>
    <p class="alert alert-error">Enter a destination URL or at least one step URL.</p>
  <?php endif; ?>
  <?php if (isset($_GET['link'])): ?>
    <p class="alert alert-success">Short link created: <a href="/go/<?= htmlspecialchars($_GET['link']) ?>">/go/<?= htmlspecialchars($_GET['link']) ?></a></p>
  <?php endif; ?>
  <?php if (isset($_GET['saved'])): ?>
    <p class="alert alert-success">Chain saved successfully. <a href="/admin/chains.php">View all chains</a></p>
  <?php endif; ?>

  <section class="admin-section admin-section-simple">
    <h2>Create Simple Link</h2>
    <form method="post" action="/admin/index.php">
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <label for="simple_name">Label (optional)</label>
        <input type="text" id="simple_name" name="name" placeholder="My link">
      </div>
      <div class="form-row">
        <label for="simple_dest">Destination URL</label>
        <input type="url" id="simple_dest" name="dest" placeholder="https://example.com/page" required>
      </div>
      <button type="submit" class="btn btn-primary">Create short link</button>
    </form>
  </section>

  <section class="admin-section">
    <h2>Create Chain</h2>
    <form method="post" action="/admin/index.php" id="chainForm">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="count" value="4" id="stepCount">
      <div class="form-row">
        <label for="name">Chain name (optional)</label>
        <input type="text" id="name" name="name" placeholder="Summer campaign">
      </div>
      <div class="form-row">
        <label for="dest">Final destination URL</label>
        <input type="url" id="dest" name="dest" placeholder="https://example.com/offer">
      </div>
      <div class="step-list" id="stepList">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="step-row">
          <span class="step-no">Step <?= $i ?></span>
          <input type="url" name="step_url_<?= $i ?>" placeholder="https://task-page.example/<?= $i ?>" class="step-url">
          <input type="number" name="step_wait_<?= $i ?>" value="8" min="1" max="120" class="step-wait" title="Wait seconds">
        </div>
        <?php endfor; ?>
      </div>
      <div class="form-actions">
        <button type="button" class="btn" id="addStepBtn">+ Add step</button>
        <button type="submit" class="btn btn-primary">Create chain</button>
      </div>
    </form>
  </section>

  <section class="admin-section">
    <h2>Recent Links</h2>
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
            <form method="post" action="/admin/index.php" onsubmit="return confirm('Delete this link?');">
              <input type="hidden" name="action" value="delete_link">
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

  <section class="admin-section">
    <h2>Recent Chains</h2>
    <?php if (!$chains): ?>
      <p class="muted">No chains yet.</p>
    <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Name</th><th>Steps</th><th>Created</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($chains as $c): ?>
        <?php $steps = json_decode($c['steps_json'], true); ?>
        <tr>
          <td><a href="/go/<?= htmlspecialchars($c['code']) ?>"><?= htmlspecialchars($c['code']) ?></a></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= (int)count($steps['steps'] ?? []) ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
          <td>
            <form method="post" action="/admin/index.php" onsubmit="return confirm('Delete this chain?');">
              <input type="hidden" name="action" value="delete_chain">
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
<script>
document.getElementById('addStepBtn').addEventListener('click', function () {
  var form = document.getElementById('chainForm');
  var n = parseInt(document.getElementById('stepCount').value, 10) + 1;
  document.getElementById('stepCount').value = n;
  var row = document.createElement('div');
  row.className = 'step-row';
  row.innerHTML = '<span class="step-no">Step ' + n + '</span>' +
    '<input type="url" name="step_url_' + n + '" placeholder="https://task-page.example/' + n + '" class="step-url">' +
    '<input type="number" name="step_wait_' + n + '" value="8" min="1" max="120" class="step-wait" title="Wait seconds">';
  document.getElementById('stepList').appendChild(row);
});
</script>
</body>
</html>