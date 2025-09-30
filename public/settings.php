<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
$config = require __DIR__ . '/../config/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($config['app_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link rel="icon" href="../assets/img/icons/shield.svg">
  <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://cdn.jsdelivr.net https://unpkg.com https://tile.openstreetmap.org; img-src 'self' data: https://tile.openstreetmap.org; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="../assets/img/icons/shield.svg" width="24" class="me-2" alt=""> <?= h($config['app_name']) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="encounters.php">Encounters</a></li>
        <li class="nav-item"><a class="nav-link" href="partners.php">Partners</a></li>
        <li class="nav-item"><a class="nav-link" href="analytics.php">Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="small-muted d-none d-md-inline"><?= h(current_user()['email'] ?? '') ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="logout.php">Sign out</a>
      </div>
    </div>
  </div>
</nav>
<main class="container my-4">
<?php
require_login();
require_once __DIR__ . '/../inc/crypto.php';
csrf_check();
$uid = current_user()['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['new_pass']) && $_POST['new_pass']) {
    $stmt = $db->prepare('UPDATE users SET pass_hash=? WHERE id=?');
    $stmt->execute([password_hash($_POST['new_pass'], PASSWORD_DEFAULT), $uid]);
    $msg = 'Passcode updated.';
  }
}
?>
<div class="card p-3">
  <h2 class="h5">Settings</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">New passcode</label>
        <input name="new_pass" type="password" class="form-control">
        <div class="form-hint">This updates your local sign‑in only.</div>
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-brand">Save</button>
    </div>
  </form>
  <hr>
  <h3 class="h6">Privacy</h3>
  <ul>
    <li>Field‑level encryption for sensitive notes and health outcomes (AES‑256‑GCM).</li>
    <li>Local‑first. No data sharing, no trackers.</li>
    <li>Session protection, CSRF tokens, and optional HTTPS enforcement (enable in config).</li>
  </ul>
</div>

</main>
<footer class="app container text-center small">
  <div>Private by design • Local-first journal • Encrypted fields</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
