<?php
/**
 * NS Link - admin login.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

nslink_session_start();

if (nslink_check()) {

    header('Location: /admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';
    if (nslink_login($user, $pass)) {
        header('Location: /admin/index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - NS Link Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<main class="auth-card">
  <h1>NS Link Admin</h1>
  <?php if ($error !== ''): ?>
    <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" action="/admin/login.php" autocomplete="off">
    <label for="user">Username</label>
    <input type="text" id="user" name="user" required autofocus>
    <label for="pass">Password</label>
    <input type="password" id="pass" name="pass" required>
    <button type="submit" class="btn btn-primary">Log in</button>
  </form>
</main>
</body>
</html>