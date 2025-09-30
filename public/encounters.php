<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
$config = require __DIR__ . '/../config/config.php';

$participantScenarioOptions = [
  'lead_partner' => 'Lead partner (primary focus)',
  'receiving_partner' => 'Receiving partner',
  'support_partner' => 'Support partner',
  'observer' => 'Observer / voyeur',
  'cuckold_partner' => 'Cuckold partner (present)',
  'cuckold_cleanup' => 'Cuckold cleanup participant',
  'aftercare_support' => 'Aftercare support',
  'other' => 'Other role'
];

$outcomeOptions = [
  'vaginal_internal' => 'Vaginal (internal)',
  'vaginal_external' => 'Vaginal (external)',
  'anal_internal' => 'Anal (internal)',
  'anal_external' => 'Anal (external)',
  'oral' => 'Oral (mouth/throat)',
  'facial' => 'Facial',
  'breasts_chest' => 'Breasts / chest',
  'stomach_thighs' => 'Stomach / thighs',
  'other_location' => 'Other location'
];

$cleanupMethods = ['none','tissues','wipe','shower','other'];

if (!function_exists('render_participant_row')) {
  function render_participant_row(int $index, array $partnerOptions, array $scenarioOptions, ?int $partnerId = null, ?string $scenarioRole = null): void {
    $scenarioRole = $scenarioRole ?? 'lead_partner';
    ?>
    <div class="participant-row row g-2 align-items-end mb-2" data-index="<?= $index ?>">
      <div class="col-md-5">
        <label class="form-label">Partner</label>
        <select name="participant_partner[]" class="form-select" required>
          <option value="">Select partner…</option>
          <?php foreach ($partnerOptions as $pid => $pname): ?>
            <option value="<?= (int)$pid ?>" <?= ($partnerId === (int)$pid) ? 'selected' : '' ?>><?= h($pname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Scenario role</label>
        <select name="participant_role[]" class="form-select" required>
          <?php foreach ($scenarioOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= ($scenarioRole === $value) ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex">
        <button type="button" class="btn btn-outline-danger btn-sm ms-md-2 mt-auto participant-remove">Remove</button>
      </div>
    </div>
    <?php
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

  $outcomeSelections = [];
  foreach ((array)($_POST['outcome_locations'] ?? []) as $value) {
    if (array_key_exists($value, $outcomeOptions)) {
      $outcomeSelections[] = $value;
    }
  }
  $outcomePayload = json_encode($outcomeSelections);
  if ($outcomePayload === false) {
    $outcomePayload = '[]';
  }
  $outcome = it_encrypt($outcomePayload);

  $cleanup_needed = isset($_POST['cleanup_needed']) ? 1 : 0;
  $cleanup_method_input = $_POST['cleanup_method'] ?? 'none';
  $cleanup_method = in_array($cleanup_method_input, $cleanupMethods, true) ? $cleanup_method_input : 'none';
  $cleanup_partner_id = $_POST['cleanup_partner_id'] !== '' ? (int)$_POST['cleanup_partner_id'] : null;
  if (!$cleanup_needed) {
    $cleanup_method = 'none';
    $cleanup_partner_id = null;
  }

  $aftercare = it_encrypt(trim($_POST['aftercare_notes'] ?? ''));
  $scenario_tag = in_array($_POST['scenario_tag'] ?? 'standard', ['standard','cuckold_observer','cuckold_present_partner','group','other']) ? $_POST['scenario_tag'] : 'standard';
  $summary_enc = it_encrypt(trim($_POST['summary'] ?? ''));

  $participantPartners = $_POST['participant_partner'] ?? [];
  $participantRoles = $_POST['participant_role'] ?? [];
  $participants = [];
  foreach ($participantPartners as $idx => $pidRaw) {
    $pid = (int)$pidRaw;
    if (!$pid) {
      continue;
    }
    $roleKey = $participantRoles[$idx] ?? 'lead_partner';
    if (!array_key_exists($roleKey, $participantScenarioOptions)) {
      $roleKey = 'other';
    }
    $participants[] = ['partner_id' => $pid, 'scenario_role' => $roleKey];
  }

  $participantPartnerIds = array_column($participants, 'partner_id');
  if ($cleanup_partner_id && !in_array($cleanup_partner_id, $participantPartnerIds, true)) {
    $participants[] = ['partner_id' => $cleanup_partner_id, 'scenario_role' => 'cuckold_cleanup'];
    $participantPartnerIds[] = $cleanup_partner_id;
  }

  if (($action === 'save_new') || ($action === 'save_edit' && $id)) {
    if ($action === 'save_new') {
      $stmt = $db->prepare('INSERT INTO encounters(user_id,occurred_at,location_label,location_type,latitude,longitude,physical_intensity,emotional_intensity,overall_rating,outcome_placement_enc,cleanup_needed,cleanup_method,cleanup_performed_by_partner_id,aftercare_notes_enc,scenario_tag,summary_enc) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $stmt->execute([$uid,$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$outcome,$cleanup_needed,$cleanup_method,$cleanup_partner_id,$aftercare,$scenario_tag,$summary_enc]);
      $enc_id = $db->lastInsertId();
    } else {
      $stmt = $db->prepare('UPDATE encounters SET occurred_at=?,location_label=?,location_type=?,latitude=?,longitude=?,physical_intensity=?,emotional_intensity=?,overall_rating=?,outcome_placement_enc=?,cleanup_needed=?,cleanup_method=?,cleanup_performed_by_partner_id=?,aftercare_notes_enc=?,scenario_tag=?,summary_enc=? WHERE id=? AND user_id=?');
      $stmt->execute([$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$outcome,$cleanup_needed,$cleanup_method,$cleanup_partner_id,$aftercare,$scenario_tag,$summary_enc,$id,$uid]);
      $enc_id = $id;
      $db->prepare('DELETE FROM encounter_participants WHERE encounter_id=?')->execute([$enc_id]);
    }
    // participants
    foreach ($participants as $participant) {
      $db->prepare('INSERT INTO encounter_participants(encounter_id,partner_id,scenario_role) VALUES(?,?,?)')
        ->execute([$enc_id, $participant['partner_id'], $participant['scenario_role']]);
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
  $enc = [
    'id' => 0,
    'occurred_at' => date('Y-m-d\TH:i'),
    'location_label' => '',
    'location_type' => 'home',
    'latitude' => '',
    'longitude' => '',
    'physical_intensity' => 5,
    'emotional_intensity' => 5,
    'overall_rating' => 3,
    'cleanup_needed' => 0,
    'cleanup_method' => 'none',
    'cleanup_performed_by_partner_id' => null,
    'cleanup_partner_id' => null,
    'aftercare_notes' => '',
    'outcome_locations' => [],
    'outcome_legacy_note' => '',
    'scenario_tag' => 'standard',
    'summary' => ''
  ];
  $participantRows = [
    ['partner_id' => null, 'scenario_role' => 'lead_partner']
  ];
  if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM encounters WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['id'],$uid]);
    $existing = $stmt->fetch();
    if ($existing) {
      $enc = array_merge($enc, $existing);
      $enc['occurred_at'] = date('Y-m-d\TH:i', strtotime($existing['occurred_at']));
      $enc['aftercare_notes'] = it_decrypt($existing['aftercare_notes_enc']);
      $rawOutcome = it_decrypt($existing['outcome_placement_enc']);
      $decodedOutcome = json_decode($rawOutcome ?: '[]', true);
      if (is_array($decodedOutcome)) {
        $enc['outcome_locations'] = array_values(array_filter($decodedOutcome, fn($value) => array_key_exists($value, $outcomeOptions)));
      } else {
        $enc['outcome_locations'] = [];
        $enc['outcome_legacy_note'] = trim((string)$rawOutcome);
      }
      $enc['summary'] = it_decrypt($existing['summary_enc']);
      $enc['cleanup_partner_id'] = $existing['cleanup_performed_by_partner_id'] !== null ? (int)$existing['cleanup_performed_by_partner_id'] : null;
      $enc['cleanup_needed'] = (int)$existing['cleanup_needed'];
      if (!in_array($enc['cleanup_method'], $cleanupMethods, true)) {
        $enc['cleanup_method'] = 'other';
      }
      if (!in_array($enc['scenario_tag'], ['standard','cuckold_observer','cuckold_present_partner','group','other'], true)) {
        $enc['scenario_tag'] = 'other';
      }

      $participantStmt = $db->prepare('SELECT partner_id, scenario_role FROM encounter_participants WHERE encounter_id=? ORDER BY id ASC');
      $participantStmt->execute([$existing['id']]);
      $participantRows = array_map(function ($row) use ($participantScenarioOptions) {
        if (!array_key_exists($row['scenario_role'], $participantScenarioOptions)) {
          $row['scenario_role'] = 'other';
        }
        $row['partner_id'] = (int)$row['partner_id'];
        return $row;
      }, $participantStmt->fetchAll());
      if (!$participantRows) {
        $participantRows = [['partner_id' => null, 'scenario_role' => 'lead_partner']];
      }
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

      <div class="col-12">
        <label class="form-label">Participants</label>
        <div id="participant-list">
          <?php foreach ($participantRows as $idx => $participant): ?>
            <?php render_participant_row($idx, $partner_opts, $participantScenarioOptions, $participant['partner_id'] ?? null, $participant['scenario_role'] ?? 'lead_partner'); ?>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="add-participant">Add participant</button>
        <div class="form-hint">Add each partner involved and select their specific scenario role, including cuckold observers or cleanup participants.</div>
        <template id="participant-row-template">
          <div class="participant-row row g-2 align-items-end mb-2" data-index="__INDEX__">
            <div class="col-md-5">
              <label class="form-label">Partner</label>
              <select name="participant_partner[]" class="form-select" required>
                <option value="">Select partner…</option>
                <?php foreach ($partner_opts as $pid => $pname): ?>
                  <option value="<?= (int)$pid ?>"><?= h($pname) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Scenario role</label>
              <select name="participant_role[]" class="form-select" required>
                <?php foreach ($participantScenarioOptions as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $value==='lead_partner' ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 d-flex">
              <button type="button" class="btn btn-outline-danger btn-sm ms-md-2 mt-auto participant-remove">Remove</button>
            </div>
          </div>
        </template>
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
        <label class="form-label">Outcome locations (health tracking)</label>
        <div class="row row-cols-1 row-cols-md-2 g-2">
          <?php foreach ($outcomeOptions as $value => $label):
            $checked = in_array($value, $enc['outcome_locations'] ?? [], true);
          ?>
            <div class="col">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="outcome-<?= h($value) ?>" name="outcome_locations[]" value="<?= h($value) ?>" <?= $checked ? 'checked' : '' ?>>
                <label class="form-check-label" for="outcome-<?= h($value) ?>"><?= h($label) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($enc['outcome_legacy_note'])): ?>
          <div class="alert alert-info mt-2 mb-0 small">
            Legacy note: <?= h($enc['outcome_legacy_note']) ?>
          </div>
        <?php endif; ?>
        <div class="form-hint">Select all locations that apply. Stored privately (encrypted).</div>
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
        <label class="form-label">Cleanup method</label>
        <select name="cleanup_method" class="form-select">
          <?php foreach ($cleanupMethods as $m): ?>
            <option value="<?= h($m) ?>" <?= ($enc['cleanup_method']??'none')===$m?'selected':'' ?>><?= ucfirst(str_replace('_',' ', $m)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Cleanup performed by (optional)</label>
        <select name="cleanup_partner_id" class="form-select">
          <option value="">Select participant…</option>
          <?php foreach ($partner_opts as $pid => $pname): ?>
            <option value="<?= (int)$pid ?>" <?= ((int)($enc['cleanup_partner_id'] ?? 0) === (int)$pid) ? 'selected' : '' ?>><?= h($pname) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Choose the participant (such as a cuckold partner) who handled cleanup.</div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  const participantList = document.getElementById('participant-list');
  const addParticipantBtn = document.getElementById('add-participant');
  const template = document.getElementById('participant-row-template');

  if (!participantList || !addParticipantBtn || !template) {
    return;
  }

  const updateRemoveButtons = () => {
    const rows = participantList.querySelectorAll('.participant-row');
    rows.forEach((row, idx) => {
      row.dataset.index = String(idx);
      const btn = row.querySelector('.participant-remove');
      if (!btn) return;
      const disable = rows.length <= 1;
      btn.disabled = disable;
      btn.classList.toggle('disabled', disable);
    });
  };

  addParticipantBtn.addEventListener('click', () => {
    const clone = template.content.cloneNode(true);
    participantList.appendChild(clone);
    updateRemoveButtons();
  });

  participantList.addEventListener('click', (event) => {
    const target = event.target.closest('.participant-remove');
    if (!target) return;
    const row = target.closest('.participant-row');
    if (!row) return;
    row.remove();
    updateRemoveButtons();
  });

  updateRemoveButtons();
});
</script>
<script src="../assets/js/app.js"></script>
</body>
</html>
