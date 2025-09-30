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

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $relationship_context = trim($_POST['relationship_context'] ?? '');
  $height_cm = $_POST['height_cm'] !== '' ? (int)$_POST['height_cm'] : null;
  $build = in_array($_POST['build'] ?? 'other', ['slim','average','athletic','curvy','plus','other']) ? $_POST['build'] : 'other';
  $dimensions_note = trim($_POST['dimensions_note'] ?? '');
  $notes_enc = it_encrypt(trim($_POST['notes'] ?? ''));

  if ($action === 'save_new') {
    $stmt = $db->prepare('INSERT INTO partners(user_id,name,relationship_context,height_cm,build,dimensions_note,notes_enc) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$name,$relationship_context,$height_cm,$build,$dimensions_note,$notes_enc]);
  } elseif ($action === 'save_edit' && $id) {
    $stmt = $db->prepare('UPDATE partners SET name=?, relationship_context=?, height_cm=?, build=?, dimensions_note=?, notes_enc=? WHERE id=? AND user_id=?');
    $stmt->execute([$name,$relationship_context,$height_cm,$build,$dimensions_note,$notes_enc,$id,$uid]);
  }
  redirect('partners.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $db->prepare('DELETE FROM partners WHERE id=? AND user_id=?')->execute([$id,$uid]);
  redirect('partners.php');
}

if ($action === 'new' || ($action === 'edit' && isset($_GET['id']))) {
  $partner = ['id'=>0,'name'=>'','relationship_context'=>'','height_cm'=>'','build'=>'other','dimensions_note'=>'','notes'=>''];
  if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM partners WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['id'],$uid]);
    $partner = $stmt->fetch();
    if ($partner) $partner['notes'] = it_decrypt($partner['notes_enc']);
  }
?>
<div class="card p-3">
  <h2 class="h5 mb-3"><?= $action==='new' ? 'Add Partner' : 'Edit Partner' ?></h2>
  <form method="post" action="partners.php?action=<?= $action==='new'?'save_new':'save_edit' ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$partner['id'] ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="<?= h($partner['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Relationship context</label>
        <input name="relationship_context" class="form-control" placeholder="e.g., spouse, partner, new relationship" value="<?= h($partner['relationship_context'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Height (cm)</label>
        <input name="height_cm" type="number" class="form-control" value="<?= h($partner['height_cm'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Build</label>
        <select name="build" class="form-select">
          <?php foreach (['slim','average','athletic','curvy','plus','other'] as $b): ?>
            <option value="<?= $b ?>" <?= ($partner['build']??'other')===$b?'selected':'' ?>><?= ucfirst($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Dimensions note (optional)</label>
        <input name="dimensions_note" class="form-control" placeholder="non-explicit, health reference only" value="<?= h($partner['dimensions_note'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Private notes</label>
        <textarea name="notes" class="form-control" rows="4"><?= h($partner['notes'] ?? '') ?></textarea>
        <div class="form-hint">Encrypted at rest.</div>
      </div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-brand">Save</button>
      <a class="btn btn-outline-secondary" href="partners.php">Cancel</a>
    </div>
  </form>
</div>
<?php
} else {
  $rows = $db->query("SELECT * FROM partners WHERE user_id = $uid ORDER BY name ASC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h5 m-0">Partners</h2>
  <a class="btn btn-sm btn-brand" href="partners.php?action=new">Add Partner</a>
</div>
<div class="card p-0">
  <table class="table table-hover align-middle m-0">
    <thead><tr><th>Name</th><th>Context</th><th>Build</th><th>Height</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['relationship_context']) ?></td>
          <td><?= ucfirst(h($r['build'])) ?></td>
          <td><?= h($r['height_cm']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="partners.php?action=edit&id=<?= (int)$r['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger" href="partners.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete partner?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php } ?>

</main>
<footer class="app container text-center small">
  <div>Private by design • Local-first journal • Encrypted fields</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
