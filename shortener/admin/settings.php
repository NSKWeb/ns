<?php
/**
 * NS Link - admin settings (change password).
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();
nslink_require_login();

$configFile = __DIR__ . '/../config.php';
$message = '';
$error = '';
$config = require $configFile;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        $hash = $config['admin_pass_hash'] ?? '';
        $ok = false;
        if ($hash !== '' && function_exists('password_verify')) {
            $ok = password_verify($current, $hash);
        } else {
            $ok = hash_equals($config['admin_pass'] ?? '', $current);
        }

        if (!$ok) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $config['admin_pass_hash'] = $newHash;

            // Rewrite config.php with the new hash. var_export round-trips
            // every byte of the hash (including $ and \), unlike naive
            // regex/string replacement.
            $out = "<?php\n"
                . "/**\n"
                . " * NS Link - URL shortener web app (Part 2)\n"
                . " * Configuration. CHANGE THE ADMIN PASSWORD BEFORE GOING LIVE.\n"
                . " */\n\n"
                . "return " . var_export($config, true) . ";\n";
            if (!is_writable($configFile) || file_put_contents($configFile, $out) === false) {
                $error = 'Could not write config file. Check file permissions.';
            } else {
                $message = 'Password updated successfully.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-topbar">
  <div class="admin-brand">NS Link Admin</div>
  <nav class="admin-nav">
    <a href="/admin/index.php">Dashboard</a>
    <a href="/admin/chains.php">Chains</a>
    <a href="/admin/links.php">Links</a>
    <a href="/admin/stats.php">Stats</a>
    <a href="/admin/settings.php" class="active">Settings</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>
<main class="admin-main">
  <?php if ($message): ?><p class="alert alert-success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <section class="admin-section">
    <h2>Change Password</h2>
    <form method="post" action="/admin/settings.php" autocomplete="off">
      <input type="hidden" name="action" value="change_password">
      <div class="form-row">
        <label for="current">Current password</label>
        <input type="password" id="current" name="current" required>
      </div>
      <div class="form-row">
        <label for="new">New password (at least 8 characters)</label>
        <input type="password" id="new" name="new" required minlength="8">
      </div>
      <div class="form-row">
        <label for="confirm">Confirm new password</label>
        <input type="password" id="confirm" name="confirm" required>
      </div>
      <button type="submit" class="btn btn-primary">Update password</button>
    </form>
  </section>
</main>
</body>
</html>