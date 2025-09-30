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
require_once __DIR__ . '/../inc/validation.php';
csrf_check();

$uid = current_user()['id'];
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $occurred_at = date('Y-m-d H:i:s', strtotime($_POST['occurred_at'] ?? 'now'));
  $location_label = trim($_POST['location_label'] ?? '');
  $location_type = in_array($_POST['location_type'] ?? 'other', ['home','hotel','outdoors','travel','other']) ? $_POST['location_type'] : 'other';
  $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
  $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
  $physical = clamp_int($_POST['physical_intensity'] ?? 0, 0, 10);
  $emotional = clamp_int($_POST['emotional_intensity'] ?? 0, 0, 10);
  $rating = clamp_int($_POST['overall_rating'] ?? 0, 0, 5);
  $outcome = it_encrypt(trim($_POST['outcome_placement'] ?? ''));
  $cleanup_needed = isset($_POST['cleanup_needed']) ? 1 : 0;
  $cleanup_method = in_array($_POST['cleanup_method'] ?? 'none', ['none','tissues','wipe','shower','other']) ? $_POST['cleanup_method'] : 'none';
  $aftercare = it_encrypt(trim($_POST['aftercare_notes'] ?? ''));
  $scenario_tag = in_array($_POST['scenario_tag'] ?? 'standard', ['standard','cuckold_observer','cuckold_present_partner','group','other']) ? $_POST['scenario_tag'] : 'standard';
  $summary_enc = it_encrypt(trim($_POST['summary'] ?? ''));

  if (($action === 'save_new') || ($action === 'save_edit' && $id)) {
    if ($action === 'save_new') {
      $stmt = $db->prepare('INSERT INTO encounters(user_id,occurred_at,location_label,location_type,latitude,longitude,physical_intensity,emotional_intensity,overall_rating,outcome_placement_enc,cleanup_needed,cleanup_method,aftercare_notes_enc,scenario_tag,summary_enc) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $stmt->execute([$uid,$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$outcome,$cleanup_needed,$cleanup_method,$aftercare,$scenario_tag,$summary_enc]);
      $enc_id = $db->lastInsertId();
    } else {
      $stmt = $db->prepare('UPDATE encounters SET occurred_at=?,location_label=?,location_type=?,latitude=?,longitude=?,physical_intensity=?,emotional_intensity=?,overall_rating=?,outcome_placement_enc=?,cleanup_needed=?,cleanup_method=?,aftercare_notes_enc=?,scenario_tag=?,summary_enc=? WHERE id=? AND user_id=?');
      $stmt->execute([$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$outcome,$cleanup_needed,$cleanup_method,$aftercare,$scenario_tag,$summary_enc,$id,$uid]);
      $enc_id = $id;
      $db->prepare('DELETE FROM encounter_participants WHERE encounter_id=?')->execute([$enc_id]);
    }
    // participants
    $partners = $_POST['partners'] ?? [];
    foreach ($partners as $pid) {
      if ($pid) $db->prepare('INSERT INTO encounter_participants(encounter_id,partner_id,role) VALUES(?,?,?)')->execute([$enc_id,(int)$pid,'primary']);
    }
  }
  redirect('encounters.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
  $db->prepare('DELETE FROM encounters WHERE id=? AND user_id=?')->execute([(int)$_GET['id'],$uid]);
  redirect('encounters.php');
}

// Load partner options
$partner_opts = $db->query("SELECT id, name FROM partners WHERE user_id=$uid ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);

if ($action === 'new' || ($action === 'edit' && isset($_GET['id']))) {
  $enc = ['id'=>0,'occurred_at'=>date('Y-m-d\TH:i'),'location_label'=>'','location_type'=>'home','latitude'=>'','longitude'=>'','physical_intensity'=>5,'emotional_intensity'=>5,'overall_rating'=>3,'outcome_placement'=>'','cleanup_needed'=>0,'cleanup_method'=>'none','aftercare_notes'=>'','scenario_tag'=>'standard','summary'=>''];
  $selected = [];
  if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM encounters WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['id'],$uid]);
    $enc = $stmt->fetch();
    if ($enc) {
      $enc['occurred_at'] = date('Y-m-d\TH:i', strtotime($enc['occurred_at']));
      $enc['aftercare_notes'] = it_decrypt($enc['aftercare_notes_enc']);
      $enc['outcome_placement'] = it_decrypt($enc['outcome_placement_enc']);
      $enc['summary'] = it_decrypt($enc['summary_enc']);
      $selected = $db->prepare('SELECT partner_id FROM encounter_participants WHERE encounter_id=?');
      $selected->execute([$enc['id']]);
      $selected = array_column($selected->fetchAll(), 'partner_id');
    }
  }
?>
<div class="card p-3">
  <h2 class="h5 mb-3"><?= $action==='new' ? 'Log Encounter' : 'Edit Encounter' ?></h2>
  <form method="post" action="encounters.php?action=<?= $action==='new'?'save_new':'save_edit' ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($enc['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Date & time</label>
        <input name="occurred_at" type="datetime-local" class="form-control" value="<?= h($enc['occurred_at']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Location label</label>
        <input name="location_label" class="form-control" placeholder="e.g., Home, Hotel, Outdoors" value="<?= h($enc['location_label']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Location type</label>
        <select name="location_type" class="form-select">
          <?php foreach (['home','hotel','outdoors','travel','other'] as $t): ?>
            <option value="<?= $t ?>" <?= ($enc['location_type']??'other')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Latitude</label>
        <input name="latitude" class="form-control" value="<?= h($enc['latitude']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Longitude</label>
        <input name="longitude" class="form-control" value="<?= h($enc['longitude']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Physical (1–10)</label>
        <input name="physical_intensity" type="number" min="1" max="10" class="form-control" value="<?= h($enc['physical_intensity']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Emotional (1–10)</label>
        <input name="emotional_intensity" type="number" min="1" max="10" class="form-control" value="<?= h($enc['emotional_intensity']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Overall (1–5)</label>
        <input name="overall_rating" type="number" min="1" max="5" class="form-control" value="<?= h($enc['overall_rating']) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Participants (select one or more)</label>
        <select name="partners[]" class="form-select" multiple size="4">
          <?php foreach ($partner_opts as $pid=>$pname): ?>
            <option value="<?= (int)$pid ?>" <?= in_array($pid, $selected??[])?'selected':'' ?>><?= h($pname) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Supports group sessions; add each participant.</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Scenario</label>
        <select name="scenario_tag" class="form-select">
          <option value="standard" <?= ($enc['scenario_tag']??'')==='standard'?'selected':'' ?>>Standard</option>
          <option value="cuckold_observer" <?= ($enc['scenario_tag']??'')==='cuckold_observer'?'selected':'' ?>>Cuckold (observer present)</option>
          <option value="cuckold_present_partner" <?= ($enc['scenario_tag']??'')==='cuckold_present_partner'?'selected':'' ?>>Cuckold (committed partner present)</option>
          <option value="group" <?= ($enc['scenario_tag']??'')==='group'?'selected':'' ?>>Group session</option>
          <option value="other" <?= ($enc['scenario_tag']??'')==='other'?'selected':'' ?>>Other</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Outcome detail (health tracking)</label>
        <input name="outcome_placement" class="form-control" placeholder="e.g., internal, external, protected, etc." value="<?= h($enc['outcome_placement'] ?? '') ?>">
        <div class="form-hint">Short, non-explicit health note. Encrypted.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Aftercare / hygiene notes</label>
        <input name="aftercare_notes" class="form-control" placeholder="e.g., rest, water, conversation" value="<?= h($enc['aftercare_notes'] ?? '') ?>">
      </div>

      <div class="col-md-3 form-check mt-2 ms-2">
        <input class="form-check-input" type="checkbox" value="1" name="cleanup_needed" <?= !empty($enc['cleanup_needed'])?'checked':'' ?>>
        <label class="form-check-label">Cleanup / aftercare performed</label>
      </div>
      <div class="col-md-3">
        <label class="form-label">Method</label>
        <select name="cleanup_method" class="form-select">
          <?php foreach (['none','tissues','wipe','shower','other'] as $m): ?>
            <option value="<?= $m ?>" <?= ($enc['cleanup_method']??'none')===$m?'selected':'' ?>><?= ucfirst($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Session summary (private)</label>
        <textarea name="summary" class="form-control" rows="4"><?= h($enc['summary'] ?? '') ?></textarea>
        <div class="form-hint">Reflect on emotions, connection, communication. Encrypted at rest.</div>
      </div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-brand">Save</button>
      <a class="btn btn-outline-secondary" href="encounters.php">Cancel</a>
    </div>
  </form>
</div>
<?php
} else {
  $rows = $db->query("SELECT e.*, (SELECT COUNT(*) FROM encounter_participants ep WHERE ep.encounter_id=e.id) as pc FROM encounters e WHERE user_id=$uid ORDER BY occurred_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h5 m-0">Encounters</h2>
  <a class="btn btn-sm btn-brand" href="encounters.php?action=new">Log Encounter</a>
</div>
<div class="card p-0">
  <table class="table table-hover align-middle m-0">
    <thead><tr><th>Date</th><th>Location</th><th>Intensity (P/E)</th><th>Participants</th><th>Overall</th><th>Scenario</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h(date('M j, Y g:ia', strtotime($r['occurred_at']))) ?></td>
          <td><?= h($r['location_label'] ?: ucfirst($r['location_type'])) ?></td>
          <td><?= (int)$r['physical_intensity'] ?>/<?= (int)$r['emotional_intensity'] ?></td>
          <td><?= (int)$r['pc'] ?></td>
          <td><?= (int)$r['overall_rating'] ?></td>
          <td><?= h(str_replace('_',' ', $r['scenario_tag'])) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="encounters.php?action=edit&id=<?= (int)$r['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger" href="encounters.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete entry?')">Delete</a>
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
