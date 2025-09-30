<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
$config = require __DIR__ . '/../config/config.php';

$relationshipOptions = [
  'spouse' => 'Spouse / married',
  'long_term_partner' => 'Long-term partner',
  'dating' => 'Exclusive dating',
  'casual' => 'Casual relationship',
  'poly_partner' => 'Poly partner',
  'friend' => 'Friend with benefits',
  'other' => 'Other'
];
$buildOptions = [
  'slim' => 'Slim',
  'average' => 'Average',
  'athletic' => 'Athletic',
  'curvy' => 'Curvy',
  'plus' => 'Plus sized',
  'other' => 'Other'
];
$circumcisedOptions = [
  '' => 'Prefer not to say',
  'yes' => 'Yes',
  'no' => 'No'
];
$raceOptions = [
  '' => 'Prefer not to say',
  'asian' => 'Asian',
  'black' => 'Black / African descent',
  'white' => 'White / European descent',
  'latino' => 'Latino / Hispanic',
  'middle_eastern' => 'Middle Eastern / North African',
  'south_asian' => 'South Asian',
  'pacific_islander' => 'Native Hawaiian / Pacific Islander',
  'indigenous' => 'Indigenous / First Nations',
  'mixed' => 'Mixed / Multi-racial',
  'other' => 'Other (specify in notes)'
];
$penisSizeOptions = [
  '' => 'Prefer not to say',
  'xs_under_4' => 'X-Small (<= 4\")',
  'small_4_5' => 'Small (4\" - 5\")',
  'average_5_6' => 'Average (5\" - 6\")',
  'above_avg_6_7' => 'Above average (6\" - 7\")',
  'large_7_8' => 'Large (7\" - 8\")',
  'xl_over_8' => 'X-Large (>= 8\")'
];
$photoMimeAllowList = ['image/jpeg','image/png','image/gif','image/webp'];
$maxPhotoSizeBytes = 5 * 1024 * 1024; // 5MB per image

function load_partner(PDO $db, int $id, int $uid): ?array {
  $stmt = $db->prepare('SELECT * FROM partners WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $uid]);
  return $stmt->fetch() ?: null;
}

function redirect_with_params(string $path, array $params = []): void {
  $qs = $params ? ('?' . http_build_query($params)) : '';
  redirect($path . $qs);
}

function height_cm_from_feet_inches(?int $feet, ?int $inches): ?int {
  if ($feet === null && $inches === null) {
    return null;
  }
  $feet = max(0, $feet ?? 0);
  $inches = max(0, min(11, $inches ?? 0));
  $totalInches = ($feet * 12) + $inches;
  if ($totalInches <= 0) {
    return null;
  }
  return (int)round($totalInches * 2.54);
}

function height_components_from_cm($cm): array {
  if (!$cm) {
    return [null, null];
  }
  $cmInt = (int)$cm;
  $totalInches = (int)round($cmInt / 2.54);
  $feet = intdiv($totalInches, 12);
  $inches = $totalInches % 12;
  return [$feet, $inches];
}

function format_height_display($cm): string {
  if (!$cm) {
    return '—';
  }
  [$feet, $inches] = height_components_from_cm($cm);
  if ($feet === null) {
    return '—';
  }
  return sprintf("%d' %d\"", $feet, $inches);
}

function penis_size_label(?string $value, array $options): string {
  if ($value === null || $value === '') {
    return '—';
  }
  return $options[$value] ?? $value;
}

require_login();
require_once __DIR__ . '/../inc/crypto.php';
csrf_check();

$uid = current_user()['id'];
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);

  if (in_array($action, ['save_new', 'save_edit'], true)) {
    $name = trim($_POST['name'] ?? '');
    $relationship_context = $_POST['relationship_context'] ?? 'other';
    if (!array_key_exists($relationship_context, $relationshipOptions)) {
      $relationship_context = 'other';
    }
    $relationship_details = trim($_POST['relationship_details'] ?? '');

    $heightFeet = $_POST['height_feet'] !== '' ? (int)$_POST['height_feet'] : null;
    $heightInches = $_POST['height_inches'] !== '' ? (int)$_POST['height_inches'] : null;
    $height_cm = height_cm_from_feet_inches($heightFeet, $heightInches);

    $build = $_POST['build'] ?? 'other';
    if (!array_key_exists($build, $buildOptions)) {
      $build = 'other';
    }

    $penisSizeInput = $_POST['penis_size_rating'] ?? '';
    if (!array_key_exists($penisSizeInput, $penisSizeOptions)) {
      $penisSizeInput = '';
    }
    $penisSizeRating = $penisSizeInput === '' ? null : $penisSizeInput;

    $circumcisedInput = $_POST['circumcised'] ?? '';
    $circumcised = null;
    if ($circumcisedInput === 'yes') {
      $circumcised = 1;
    } elseif ($circumcisedInput === 'no') {
      $circumcised = 0;
    }

    $raceInput = $_POST['race'] ?? '';
    if (!array_key_exists($raceInput, $raceOptions)) {
      $raceInput = '';
    }
    $race = $raceInput === '' ? null : $raceInput;

    $met_location = trim($_POST['met_location'] ?? '');
    if ($met_location === '') $met_location = null;

    $first_met_notes = trim($_POST['first_met_notes'] ?? '');
    if ($first_met_notes === '') $first_met_notes = null;

    $dimensions_note = trim($_POST['dimensions_note'] ?? '');
    if ($dimensions_note === '') $dimensions_note = null;

    $notes_enc = it_encrypt(trim($_POST['notes'] ?? ''));

    if ($action === 'save_new') {
      $stmt = $db->prepare('INSERT INTO partners (user_id, name, relationship_context, relationship_details, height_cm, build, penis_size_rating, circumcised, race, met_location, first_met_notes, dimensions_note, notes_enc) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $stmt->execute([$uid, $name, $relationship_context, $relationship_details ?: null, $height_cm, $build, $penisSizeRating, $circumcised, $race, $met_location, $first_met_notes, $dimensions_note, $notes_enc]);
      redirect('partners.php');
    } elseif ($action === 'save_edit' && $id) {
      $stmt = $db->prepare('UPDATE partners SET name=?, relationship_context=?, relationship_details=?, height_cm=?, build=?, penis_size_rating=?, circumcised=?, race=?, met_location=?, first_met_notes=?, dimensions_note=?, notes_enc=? WHERE id=? AND user_id=?');
      $stmt->execute([$name, $relationship_context, $relationship_details ?: null, $height_cm, $build, $penisSizeRating, $circumcised, $race, $met_location, $first_met_notes, $dimensions_note, $notes_enc, $id, $uid]);
      redirect('partners.php');
    }
  } elseif ($action === 'upload_photo') {
    $partnerId = (int)($_POST['partner_id'] ?? 0);
    $partner = $partnerId ? load_partner($db, $partnerId, $uid) : null;
    if (!$partner) {
      redirect('partners.php');
    }

    $errors = [];
    if (isset($_FILES['photos'])) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $files = $_FILES['photos'];
      $fileCount = is_array($files['name']) ? count($files['name']) : 0;

      for ($i = 0; $i < $fileCount; $i++) {
        $error = $files['error'][$i];
        if ($error === UPLOAD_ERR_NO_FILE) {
          continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
          $errors[] = 'upload';
          continue;
        }
        if ($files['size'][$i] > $maxPhotoSizeBytes) {
          $errors[] = 'size';
          continue;
        }
        $tmpPath = $files['tmp_name'][$i];
        $mime = $finfo->file($tmpPath) ?: $files['type'][$i];
        if (!in_array($mime, $photoMimeAllowList, true)) {
          $errors[] = 'type';
          continue;
        }
        $imageData = file_get_contents($tmpPath);
        if ($imageData === false) {
          $errors[] = 'read';
          continue;
        }
        $stmt = $db->prepare('INSERT INTO partner_photos (partner_id, user_id, mime_type, image_data) VALUES (?, ?, ?, ?)');
        $stmt->bindValue(1, $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $uid, PDO::PARAM_INT);
        $stmt->bindValue(3, $mime, PDO::PARAM_STR);
        $stmt->bindValue(4, $imageData, PDO::PARAM_LOB);
        $stmt->execute();
      }
    }

    $params = ['action' => 'view', 'id' => $partnerId];
    if ($errors) {
      $params['error'] = implode(',', array_unique($errors));
    } else {
      $params['uploaded'] = 1;
    }
    redirect_with_params('partners.php', $params);
  } elseif ($action === 'delete_photo') {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $targetPartnerId = (int)($_POST['partner_id'] ?? 0);
    $stmt = $db->prepare('SELECT partner_id FROM partner_photos WHERE id = ? AND user_id = ?');
    $stmt->execute([$photoId, $uid]);
    $photo = $stmt->fetch();
    if ($photo) {
      $partnerId = (int)$photo['partner_id'];
      $del = $db->prepare('DELETE FROM partner_photos WHERE id = ? AND user_id = ?');
      $del->execute([$photoId, $uid]);
    } else {
      $partnerId = $targetPartnerId;
    }
    redirect_with_params('partners.php', ['action' => 'view', 'id' => $partnerId]);
  }
}
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
$notice = $_GET['uploaded'] ?? null;
$error = $_GET['error'] ?? null;
if ($notice): ?>
  <div class="alert alert-success">Photos uploaded successfully.</div>
<?php elseif ($error): ?>
  <div class="alert alert-warning">
    <?php
    $errors = explode(',', $error);
    $messages = [];
    foreach ($errors as $err) {
      switch ($err) {
        case 'type':
          $messages[] = 'Unsupported image type.';
          break;
        case 'size':
          $messages[] = 'One or more images exceeded the size limit (5MB).';
          break;
        case 'upload':
          $messages[] = 'An image failed to upload.';
          break;
        case 'read':
          $messages[] = 'Could not read one of the uploaded files.';
          break;
        default:
          break;
      }
    }
    echo h($messages ? implode(' ', $messages) : 'Upload failed.');
    ?>
  </div>
<?php endif; ?>
<?php
if ($action === 'new' || ($action === 'edit' && isset($_GET['id']))) {
  $partner = [
    'id' => 0,
    'name' => '',
    'relationship_context' => 'other',
    'relationship_details' => '',
    'height_cm' => null,
    'height_feet' => '',
    'height_inches' => '',
    'build' => 'other',
    'penis_size_rating' => '',
    'circumcised' => null,
    'circumcised_value' => '',
    'race' => '',
    'met_location' => '',
    'first_met_notes' => '',
    'dimensions_note' => '',
    'notes' => ''
  ];
  if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM partners WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['id'], $uid]);
    $partner = $stmt->fetch();
    if ($partner) {
      $partner['notes'] = it_decrypt($partner['notes_enc']);
      $partner['circumcised_value'] = $partner['circumcised'] === null ? '' : ($partner['circumcised'] ? 'yes' : 'no');
      if (!array_key_exists($partner['penis_size_rating'] ?? '', $penisSizeOptions)) {
        if (!empty($partner['penis_size_rating'])) {
          $penisSizeOptions[$partner['penis_size_rating']] = 'Legacy rating (' . $partner['penis_size_rating'] . ')';
        } else {
          $partner['penis_size_rating'] = '';
        }
      }
      if (!array_key_exists($partner['race'] ?? '', $raceOptions)) {
        if (!empty($partner['race'])) {
          $raceOptions[$partner['race']] = $partner['race'];
        } else {
          $partner['race'] = '';
        }
      }
      [$feet, $inches] = height_components_from_cm($partner['height_cm'] ?? null);
      $partner['height_feet'] = $feet ?? '';
      $partner['height_inches'] = $inches ?? '';
    }
  }
?>
<div class="card p-3">
  <h2 class="h5 mb-3"><?= $action==='new' ? 'Add Partner' : 'Edit Partner' ?></h2>
  <form method="post" action="partners.php?action=<?= $action==='new'?'save_new':'save_edit' ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($partner['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="<?= h($partner['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Relationship</label>
        <select name="relationship_context" class="form-select">
          <?php foreach ($relationshipOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($partner['relationship_context'] ?? 'other') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Relationship details</label>
        <input name="relationship_details" class="form-control" placeholder="Optional context" value="<?= h($partner['relationship_details'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Height (feet)</label>
        <input name="height_feet" type="number" min="0" class="form-control" value="<?= h($partner['height_feet'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Height (inches)</label>
        <input name="height_inches" type="number" min="0" max="11" class="form-control" value="<?= h($partner['height_inches'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Build</label>
        <select name="build" class="form-select">
          <?php foreach ($buildOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($partner['build'] ?? 'other') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Penis Size Rating</label>
        <select name="penis_size_rating" class="form-select">
          <?php foreach ($penisSizeOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($partner['penis_size_rating'] ?? '') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Circumcised</label>
        <select name="circumcised" class="form-select">
          <?php foreach ($circumcisedOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($partner['circumcised_value'] ?? '') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Race / Ethnicity</label>
        <select name="race" class="form-select">
          <?php foreach ($raceOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($partner['race'] ?? '') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Where you first met</label>
        <input name="met_location" class="form-control" placeholder="e.g., conference, college" value="<?= h($partner['met_location'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">First meeting notes</label>
        <input name="first_met_notes" class="form-control" placeholder="Optional memory" value="<?= h($partner['first_met_notes'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Dimensions note (optional)</label>
        <input name="dimensions_note" class="form-control" placeholder="Non-explicit health reference" value="<?= h($partner['dimensions_note'] ?? '') ?>">
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
} elseif ($action === 'view' && isset($_GET['id'])) {
  $partnerId = (int)$_GET['id'];
  $partner = load_partner($db, $partnerId, $uid);
  if (!$partner) {
    echo '<div class="alert alert-warning">Partner not found.</div>';
  } else {
    $partner['notes'] = it_decrypt($partner['notes_enc']);
    $photoStmt = $db->prepare('SELECT id, mime_type, image_data, created_at FROM partner_photos WHERE partner_id = ? AND user_id = ? ORDER BY created_at DESC');
    $photoStmt->execute([$partnerId, $uid]);
    $photos = $photoStmt->fetchAll();
    $circumcisedLabel = $partner['circumcised'] === null ? 'Unknown' : ($partner['circumcised'] ? 'Yes' : 'No');
    $raceLabel = $partner['race'] ? ($raceOptions[$partner['race']] ?? $partner['race']) : '—';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 m-0">Partner profile</h2>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="partners.php?action=edit&id=<?= (int)$partner['id'] ?>">Edit details</a>
    <a class="btn btn-outline-secondary btn-sm" href="partners.php">Back to list</a>
  </div>
</div>
<div class="card p-4 mb-4">
  <h3 class="h5 mb-3"><?= h($partner['name']) ?></h3>
  <dl class="row small mb-0">
    <dt class="col-sm-4">Relationship</dt>
    <dd class="col-sm-8"><?= h($relationshipOptions[$partner['relationship_context']] ?? ucfirst(str_replace('_', ' ', $partner['relationship_context']))) ?><?php if ($partner['relationship_details']): ?> <span class="text-muted">(<?= h($partner['relationship_details']) ?>)</span><?php endif; ?></dd>
    <dt class="col-sm-4">Build</dt>
    <dd class="col-sm-8"><?= h($buildOptions[$partner['build']] ?? ucfirst($partner['build'])) ?></dd>
    <dt class="col-sm-4">Height</dt>
    <dd class="col-sm-8"><?= h(format_height_display($partner['height_cm'])) ?></dd>
    <dt class="col-sm-4">Penis size rating</dt>
    <dd class="col-sm-8"><?= h(penis_size_label($partner['penis_size_rating'], $penisSizeOptions)) ?></dd>
    <dt class="col-sm-4">Circumcised</dt>
    <dd class="col-sm-8"><?= h($circumcisedLabel) ?></dd>
    <dt class="col-sm-4">Race / Ethnicity</dt>
    <dd class="col-sm-8"><?= h($raceLabel) ?></dd>
    <dt class="col-sm-4">First met</dt>
    <dd class="col-sm-8"><?= $partner['met_location'] ? h($partner['met_location']) : '—' ?></dd>
    <dt class="col-sm-4">First meeting notes</dt>
    <dd class="col-sm-8"><?= $partner['first_met_notes'] ? h($partner['first_met_notes']) : '—' ?></dd>
    <dt class="col-sm-4">Dimensions note</dt>
    <dd class="col-sm-8"><?= $partner['dimensions_note'] ? h($partner['dimensions_note']) : '—' ?></dd>
    <dt class="col-sm-4">Private notes</dt>
    <dd class="col-sm-8"><?= $partner['notes'] ? nl2br(h($partner['notes'])) : '—' ?></dd>
  </dl>
</div>
<div class="card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="h6 m-0">Photo gallery</h3>
    <form class="d-flex gap-2" method="post" action="partners.php?action=upload_photo" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="partner_id" value="<?= (int)$partner['id'] ?>">
      <input class="form-control form-control-sm" type="file" name="photos[]" accept="image/*" multiple required>
      <button class="btn btn-sm btn-brand">Upload</button>
    </form>
  </div>
  <?php if ($photos): ?>
    <div class="partner-gallery">
      <?php foreach ($photos as $photo):
        $dataUri = 'data:' . $photo['mime_type'] . ';base64,' . base64_encode($photo['image_data']);
      ?>
        <div class="partner-gallery-item">
          <img src="<?= h($dataUri) ?>" alt="<?= h($partner['name']) ?> photo" class="img-thumbnail">
          <form method="post" action="partners.php?action=delete_photo" class="mt-2">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
            <input type="hidden" name="partner_id" value="<?= (int)$partner['id'] ?>">
            <button class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Remove this photo?')">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-muted small mb-0">No photos saved yet. Upload one to start the gallery.</p>
  <?php endif; ?>
</div>
<?php
  }
} else {
  $rows = $db->query("SELECT * FROM partners WHERE user_id = $uid ORDER BY name ASC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h5 m-0">Partners</h2>
  <a class="btn btn-sm btn-brand" href="partners.php?action=new">Add Partner</a>
</div>
<div class="card p-0">
  <table class="table table-hover align-middle m-0">
    <thead><tr><th>Name</th><th>Relationship</th><th>Penis size</th><th>Met at</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r):
        $penisLabel = penis_size_label($r['penis_size_rating'] ?? null, $penisSizeOptions);
      ?>
        <tr>
          <td><?= h($r['name']) ?></td>
          <td><?= h($relationshipOptions[$r['relationship_context']] ?? ucfirst(str_replace('_',' ', $r['relationship_context']))) ?></td>
          <td><?= h($penisLabel) ?></td>
          <td><?= $r['met_location'] ? h($r['met_location']) : '—' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="partners.php?action=view&id=<?= (int)$r['id'] ?>">View</a>
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
