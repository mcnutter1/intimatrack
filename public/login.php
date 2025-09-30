<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/db.php';

$config = require __DIR__ . '/../config/config.php';

if (current_user()) redirect('index.php');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $ok = register_first_user(trim($_POST['email']), $_POST['password']);
    if ($ok) {
      $msg = 'Registered. Please sign in.';
    } else {
      $msg = 'Registration disabled or already initialized.';
    }
  } else {
    if (login(trim($_POST['email']), $_POST['password'])) {
      redirect('index.php');
    } else {
      $msg = 'Invalid credentials.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($config['app_name']) ?> • Sign in</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">
  <div class="container" style="max-width:520px;">
    <div class="text-center mb-4">
      <img src="../assets/img/icons/shield.svg" width="48" alt="">
      <h1 class="h3 mt-3"><?= h($config['app_name']) ?></h1>
      <p class="small-muted">A private, wellness‑focused journal for intimate connection.</p>
    </div>
    <?php if ($msg): ?><div class="alert alert-warning"><?= h($msg) ?></div><?php endif; ?>
    <div class="card p-4 mb-3">
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Passcode</label>
          <input name="password" type="password" class="form-control" required>
        </div>
        <button class="btn btn-brand w-100">Sign in</button>
      </form>
    </div>
    <div class="card p-3">
      <form method="post">
        <input type="hidden" name="action" value="register">
        <p class="small-muted mb-2">First time? Create your account (local only).</p>
        <div class="row g-2">
          <div class="col-8">
            <input name="email" type="email" class="form-control" placeholder="you@example.com" required>
          </div>
          <div class="col-4">
            <input name="password" type="password" class="form-control" placeholder="passcode" required>
          </div>
        </div>
        <div class="mt-2 text-end">
          <button class="btn btn-outline-secondary btn-sm">Register</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
