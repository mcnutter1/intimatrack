<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/validation.php';
$config = require __DIR__ . '/../config/config.php';

$participantRoleOptions = [
  'lead_partner' => 'Lead partner',
  'receiving_partner' => 'Receiving / receptive partner',
  'support_partner' => 'Support partner',
  'observer' => 'Observer / voyeur',
  'cuckold_partner' => 'Cuckold partner (present)',
  'cuckold_cleanup' => 'Cuckold cleanup participant',
  'aftercare_support' => 'Aftercare support',
  'other' => 'Other role'
];

$scenarioOptions = [
  'vaginal_intercourse' => 'Vaginal intercourse',
  'anal_intercourse' => 'Anal intercourse',
  'oral_giving' => 'Oral (giving)',
  'oral_receiving' => 'Oral (receiving)',
  'mutual_masturbation' => 'Mutual masturbation',
  'toy_play' => 'Toy play',
  'foreplay' => 'Extended foreplay',
  'voyeurism' => 'Voyeurism / watching',
  'cuckold_focus' => 'Cuckold-focused play',
  'aftercare_bonding' => 'Aftercare / bonding',
  'other' => 'Other scenario'
];

$positionOptions = [
  'missionary' => 'Missionary',
  'doggy' => 'Doggy style',
  'cowgirl' => 'Cowgirl',
  'reverse_cowgirl' => 'Reverse cowgirl',
  'standing' => 'Standing',
  'spooning' => 'Spooning',
  'seated' => 'Seated',
  'oral_kneeling' => 'Oral (kneeling)',
  'oral_standing' => 'Oral (standing)',
  'face_sitting' => 'Face sitting',
  'other' => 'Other position'
];

$climaxLocationOptions = [
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

$cleanupMethodOptions = [
  'none' => 'None / not recorded',
  'towel' => 'Towel or cloth',
  'wipes' => 'Wipes',
  'shower' => 'Shower / bathing',
  'oral_cleanup' => 'Oral cleanup',
  'self_cleanup' => 'Self cleanup',
  'other' => 'Other method'
];

function default_round_payload(): array {
  return [
    'role' => 'lead_partner',
    'scenario' => 'vaginal_intercourse',
    'positions' => [],
    'participant_climax' => '',
    'partner_climax' => '',
    'partner_climax_location' => '',
    'duration_minutes' => '',
    'satisfaction_rating' => '',
    'cleanup_partner_id' => '',
    'cleanup_method' => 'none'
  ];
}

function render_round_row(
  $participantIndex,
  $roundIndex,
  array $round,
  array $participantRoleOptions,
  array $scenarioOptions,
  array $positionOptions,
  array $climaxLocationOptions,
  array $cleanupMethodOptions,
  array $partnerOptions
): void {
  $prefix = "participants[$participantIndex][rounds][$roundIndex]";
  $positionsSelected = (array)($round['positions'] ?? []);
  $cleanupPartner = $round['cleanup_partner_id'] ?? '';
  $cleanupMethod = $round['cleanup_method'] ?? 'none';
  $participantClimax = $round['participant_climax'] ?? '';
  $partnerClimax = $round['partner_climax'] ?? '';
  $partnerClimaxLocation = $round['partner_climax_location'] ?? '';
  $durationMinutes = $round['duration_minutes'] ?? '';
  if ($durationMinutes === null) $durationMinutes = '';
  $satisfactionRating = $round['satisfaction_rating'] ?? '';
  if ($satisfactionRating === null) $satisfactionRating = '';
?>
  <div class="round-row border rounded p-3 mb-2" data-round-index="<?= h((string)$roundIndex) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Role</label>
        <select name="<?= $prefix ?>[role]" class="form-select form-select-sm" required>
          <?php foreach ($participantRoleOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= (($round['role'] ?? 'lead_partner') === $value) ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Scenario</label>
        <select name="<?= $prefix ?>[scenario]" class="form-select form-select-sm" required>
          <?php foreach ($scenarioOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= (($round['scenario'] ?? 'other') === $value) ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Positions used</label>
        <select name="<?= $prefix ?>[positions][]" class="form-select form-select-sm" multiple size="4">
          <?php foreach ($positionOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= in_array($value, $positionsSelected, true) ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Hold ⌘/Ctrl to select more than one.</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Climax experienced</label>
        <select name="<?= $prefix ?>[participant_climax]" class="form-select form-select-sm">
          <option value="" <?= $participantClimax === '' ? 'selected' : '' ?>>Not recorded</option>
          <option value="yes" <?= $participantClimax === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no" <?= $participantClimax === 'no' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Partner climax experienced</label>
        <select name="<?= $prefix ?>[partner_climax]" class="form-select form-select-sm partner-climax-select">
          <option value="" <?= $partnerClimax === '' ? 'selected' : '' ?>>Not recorded</option>
          <option value="yes" <?= $partnerClimax === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no" <?= $partnerClimax === 'no' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-3 partner-climax-location" <?= $partnerClimax === 'yes' ? '' : 'style="display:none;"' ?>>
        <label class="form-label">Partner climax location</label>
        <select name="<?= $prefix ?>[partner_climax_location]" class="form-select form-select-sm">
          <option value="">Select location…</option>
          <?php foreach ($climaxLocationOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $partnerClimaxLocation === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Duration (minutes)</label>
        <input name="<?= $prefix ?>[duration_minutes]" type="number" min="0" class="form-control form-control-sm" value="<?= h($durationMinutes) ?>" placeholder="e.g., 25">
      </div>
      <div class="col-md-3">
        <label class="form-label">Satisfaction (1–10)</label>
        <input name="<?= $prefix ?>[satisfaction_rating]" type="number" min="1" max="10" class="form-control form-control-sm" value="<?= h($satisfactionRating) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Cleanup performed by</label>
        <select name="<?= $prefix ?>[cleanup_partner_id]" class="form-select form-select-sm">
          <option value="">Not recorded</option>
          <?php foreach ($partnerOptions as $pid => $pname): ?>
            <option value="<?= (int)$pid ?>" <?= ((int)$cleanupPartner === (int)$pid) ? 'selected' : '' ?>><?= h($pname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Cleanup method</label>
        <select name="<?= $prefix ?>[cleanup_method]" class="form-select form-select-sm">
          <?php foreach ($cleanupMethodOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $cleanupMethod === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="button" class="btn btn-outline-danger btn-sm round-remove">Remove round</button>
      </div>
    </div>
  </div>
<?php
}

function render_participant_block(
  int $participantIndex,
  array $participant,
  array $partnerOptions,
  array $participantRoleOptions,
  array $scenarioOptions,
  array $positionOptions,
  array $climaxLocationOptions,
  array $cleanupMethodOptions
): void {
  $rounds = $participant['rounds'] ?? [default_round_payload()];
  if (!$rounds) {
    $rounds = [default_round_payload()];
  }
  $nextRoundIndex = count($rounds);
?>
  <div class="participant-block border rounded p-3 mb-3" data-participant-index="<?= h((string)$participantIndex) ?>" data-next-round-index="<?= h((string)$nextRoundIndex) ?>">
    <div class="d-flex flex-column flex-md-row align-items-md-end gap-3 mb-3">
      <div class="flex-grow-1">
        <label class="form-label">Participant</label>
        <select name="participants[<?= $participantIndex ?>][partner_id]" class="form-select participant-partner-select" required>
          <option value="">Select partner…</option>
          <?php foreach ($partnerOptions as $pid => $pname): ?>
            <option value="<?= (int)$pid ?>" <?= ((int)($participant['partner_id'] ?? 0) === (int)$pid) ? 'selected' : '' ?>><?= h($pname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button type="button" class="btn btn-outline-danger btn-sm participant-remove">Remove participant</button>
      </div>
    </div>
    <div class="rounds-list">
      <?php foreach (array_values($rounds) as $roundIndex => $round): ?>
        <?php render_round_row($participantIndex, $roundIndex, $round, $participantRoleOptions, $scenarioOptions, $positionOptions, $climaxLocationOptions, $cleanupMethodOptions, $partnerOptions); ?>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm add-round">Add round</button>
  </div>
<?php
}

function build_encounter_summary(
  array $participants,
  array $partnerOptions,
  array $participantRoleOptions,
  array $scenarioOptions,
  array $positionOptions,
  array $cleanupMethodOptions,
  array $climaxLocationOptions
): array {
  $summary = [];
  foreach ($participants as $participant) {
    $partnerId = (int)($participant['partner_id'] ?? 0);
    $partnerName = $partnerOptions[$partnerId] ?? 'Unassigned partner';
    $roundSummaries = [];
    foreach ($participant['rounds'] ?? [] as $round) {
      $roleLabel = $participantRoleOptions[$round['role'] ?? ''] ?? ($round['role'] ?? '');
      $scenarioLabel = $scenarioOptions[$round['scenario'] ?? ''] ?? ($round['scenario'] ?? '');
      $positionsSelected = (array)($round['positions'] ?? []);
      $positionLabels = array_map(fn($pos) => $positionOptions[$pos] ?? $pos, $positionsSelected);
      $participantClimax = $round['participant_climax'] ?? '';
      $partnerClimax = $round['partner_climax'] ?? '';
      $partnerClimaxLocation = $round['partner_climax_location'] ?? '';
      $durationMinutes = $round['duration_minutes'] ?? null;
      $satisfactionRating = $round['satisfaction_rating'] ?? null;
      $cleanupMethod = $cleanupMethodOptions[$round['cleanup_method'] ?? ''] ?? null;
      $cleanupPartnerId = (int)($round['cleanup_partner_id'] ?? 0);
      $cleanupPartnerName = $cleanupPartnerId ? ($partnerOptions[$cleanupPartnerId] ?? ('Partner #' . $cleanupPartnerId)) : null;
      $roundSummaries[] = [
        'role' => $roleLabel,
        'scenario' => $scenarioLabel,
        'positions' => $positionLabels,
        'participant_climax' => $participantClimax,
        'partner_climax' => $partnerClimax,
        'partner_climax_location' => $climaxLocationOptions[$partnerClimaxLocation] ?? '',
        'duration_minutes' => $durationMinutes,
        'satisfaction_rating' => $satisfactionRating,
        'cleanup_method' => $cleanupMethod,
        'cleanup_partner' => $cleanupPartnerName
      ];
    }
    $summary[] = [
      'partner' => $partnerName,
      'rounds' => $roundSummaries
    ];
  }
  return $summary;
}

require_login();
require_once __DIR__ . '/../inc/crypto.php';
csrf_check();

$uid = current_user()['id'];
$action = $_GET['action'] ?? 'list';
$formErrors = [];

// Load partner options once
$partner_opts = $db->prepare('SELECT id, name FROM partners WHERE user_id = ? ORDER BY name');
$partner_opts->execute([$uid]);
$partner_opts = $partner_opts->fetchAll(PDO::FETCH_KEY_PAIR);

$savedLocationsStmt = $db->prepare('SELECT label, latitude, longitude FROM user_locations WHERE user_id=? ORDER BY times_used DESC, last_used_at DESC, label ASC');
$savedLocationsStmt->execute([$uid]);
$savedLocations = $savedLocationsStmt->fetchAll(PDO::FETCH_ASSOC);
if (!$savedLocations) {
  $fallbackStmt = $db->prepare('SELECT DISTINCT COALESCE(NULLIF(location_label, \'\'), location_type) AS label, latitude, longitude FROM encounters WHERE user_id=? AND (location_label <> \'\' OR latitude IS NOT NULL OR longitude IS NOT NULL) ORDER BY occurred_at DESC LIMIT 25');
  $fallbackStmt->execute([$uid]);
  $savedLocations = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
}
$seenLabels = [];
$savedLocations = array_values(array_filter($savedLocations, function ($loc) use (&$seenLabels) {
  $label = trim($loc['label'] ?? '');
  if ($label === '' || isset($seenLabels[strtolower($label)])) {
    return false;
  }
  $seenLabels[strtolower($label)] = true;
  return true;
}));

function sanitize_participants_input(array $input, array $partnerOptions, array $participantRoleOptions, array $scenarioOptions, array $positionOptions, array $cleanupMethodOptions, array $climaxLocationOptions): array {
  $result = [];
  foreach (array_values($input) as $participant) {
    $partnerId = (int)($participant['partner_id'] ?? 0);
    if (!$partnerId || !array_key_exists($partnerId, $partnerOptions)) {
      continue;
    }
    $rounds = [];
    foreach (array_values($participant['rounds'] ?? []) as $round) {
      $role = $round['role'] ?? 'lead_partner';
      if (!array_key_exists($role, $participantRoleOptions)) {
        $role = 'other';
      }
      $scenario = $round['scenario'] ?? 'other';
      if (!array_key_exists($scenario, $scenarioOptions)) {
        $scenario = 'other';
      }
      $positionsRaw = (array)($round['positions'] ?? []);
      $positions = array_values(array_filter($positionsRaw, fn($value) => array_key_exists($value, $positionOptions)));

      $participantClimax = $round['participant_climax'] ?? '';
      $partnerClimax = $round['partner_climax'] ?? '';
      $partnerClimaxLocation = $round['partner_climax_location'] ?? '';
     if ($partnerClimax !== 'yes') {
       $partnerClimaxLocation = '';
     } elseif (!array_key_exists($partnerClimaxLocation, $climaxLocationOptions)) {
       $partnerClimaxLocation = '';
     }

      $durationMinutes = $round['duration_minutes'] ?? '';
      $durationMinutes = $durationMinutes === '' ? null : max(0, (int)$durationMinutes);

      $satisfactionRating = $round['satisfaction_rating'] ?? '';
      if ($satisfactionRating === '') {
        $satisfactionRating = null;
      } else {
        $satisfactionRating = clamp_int((int)$satisfactionRating, 1, 10);
      }

      $cleanupPartnerId = (int)($round['cleanup_partner_id'] ?? 0);
      if (!$cleanupPartnerId || !array_key_exists($cleanupPartnerId, $partnerOptions)) {
        $cleanupPartnerId = null;
      }
      $cleanupMethod = $round['cleanup_method'] ?? 'none';
      if (!array_key_exists($cleanupMethod, $cleanupMethodOptions)) {
        $cleanupMethod = 'other';
      }

      $rounds[] = [
        'role' => $role,
        'scenario' => $scenario,
        'positions' => $positions,
        'participant_climax' => in_array($participantClimax, ['yes','no'], true) ? $participantClimax : '',
        'partner_climax' => in_array($partnerClimax, ['yes','no'], true) ? $partnerClimax : '',
        'partner_climax_location' => $partnerClimaxLocation,
        'duration_minutes' => $durationMinutes,
        'satisfaction_rating' => $satisfactionRating,
        'cleanup_partner_id' => $cleanupPartnerId,
        'cleanup_method' => $cleanupMethod
      ];
    }
    if ($rounds) {
      $result[] = [
        'partner_id' => $partnerId,
        'rounds' => $rounds
      ];
    }
  }
  return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $occurred_at_input = $_POST['occurred_at'] ?? date('Y-m-d H:i');
  $occurred_at = date('Y-m-d H:i:s', strtotime($occurred_at_input));
  $location_label = trim($_POST['location_label'] ?? '');
  $location_type = $_POST['location_type'] ?? 'other';
  if (!in_array($location_type, ['home','hotel','outdoors','travel','other'], true)) {
    $location_type = 'other';
  }
  $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
  $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
  $physical = clamp_int($_POST['physical_intensity'] ?? 0, 0, 10);
  $emotional = clamp_int($_POST['emotional_intensity'] ?? 0, 0, 10);
  $rating = clamp_int($_POST['overall_rating'] ?? 0, 0, 5);
  $scenario_tag = $_POST['scenario_tag'] ?? 'standard';
  if (!in_array($scenario_tag, ['standard','cuckold_observer','cuckold_present_partner','group','other'], true)) {
    $scenario_tag = 'other';
  }
  $summary_input = trim($_POST['summary'] ?? '');
  $summary_enc = it_encrypt($summary_input);

  $participantsInput = $_POST['participants'] ?? [];
  $participantsData = sanitize_participants_input($participantsInput, $partner_opts, $participantRoleOptions, $scenarioOptions, $positionOptions, $cleanupMethodOptions, $climaxLocationOptions);

  if (!$participantsData) {
    $formErrors[] = 'Add at least one participant with at least one round.';
  }

  if (!$formErrors) {
    try {
      $db->beginTransaction();
      if ($action === 'save_new') {
        $stmt = $db->prepare('INSERT INTO encounters(user_id,occurred_at,location_label,location_type,latitude,longitude,physical_intensity,emotional_intensity,overall_rating,scenario_tag,summary_enc) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$uid,$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$scenario_tag,$summary_enc]);
        $encounterId = (int)$db->lastInsertId();
      } elseif ($action === 'save_edit' && $id) {
        $stmt = $db->prepare('UPDATE encounters SET occurred_at=?,location_label=?,location_type=?,latitude=?,longitude=?,physical_intensity=?,emotional_intensity=?,overall_rating=?,scenario_tag=?,summary_enc=? WHERE id=? AND user_id=?');
        $stmt->execute([$occurred_at,$location_label,$location_type,$latitude,$longitude,$physical,$emotional,$rating,$scenario_tag,$summary_enc,$id,$uid]);
        $encounterId = $id;
        $db->prepare('DELETE FROM encounter_participants WHERE encounter_id=?')->execute([$encounterId]);
      } else {
        throw new RuntimeException('Unsupported action.');
      }

      $participantInsert = $db->prepare('INSERT INTO encounter_participants(encounter_id, partner_id) VALUES(?, ?)');
      $roundInsert = $db->prepare('INSERT INTO encounter_rounds(participant_id, round_order, role, scenario, positions, participant_climax, partner_climax, partner_climax_location, duration_minutes, satisfaction_rating, cleanup_performed_by_partner_id, cleanup_method) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');

      foreach ($participantsData as $participantIndex => $participant) {
        $participantInsert->execute([$encounterId, $participant['partner_id']]);
        $participantId = (int)$db->lastInsertId();
        foreach (array_values($participant['rounds']) as $roundIndex => $round) {
          $positionsJson = $round['positions'] ? json_encode($round['positions']) : null;
          $participantClimaxValue = $round['participant_climax'] === '' ? null : ($round['participant_climax'] === 'yes' ? 1 : 0);
          $partnerClimaxValue = $round['partner_climax'] === '' ? null : ($round['partner_climax'] === 'yes' ? 1 : 0);
          $roundInsert->execute([
            $participantId,
            $roundIndex,
            $round['role'],
            $round['scenario'],
            $positionsJson,
            $participantClimaxValue,
            $partnerClimaxValue,
            $round['partner_climax_location'] ?: null,
            $round['duration_minutes'] ?? null,
            $round['satisfaction_rating'] ?? null,
            $round['cleanup_partner_id'] ?: null,
            $round['cleanup_method']
          ]);
        }
      }

      if ($location_label !== '') {
        $upsertLocation = $db->prepare('INSERT INTO user_locations (user_id, label, latitude, longitude, times_used, last_used_at) VALUES (?,?,?,?,1,?)
          ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), times_used = times_used + 1, last_used_at = VALUES(last_used_at)');
        $upsertLocation->execute([$uid, $location_label, $latitude, $longitude, $occurred_at]);
      }

      $db->commit();
      redirect('encounters.php');
    } catch (Throwable $e) {
      $db->rollBack();
      $formErrors[] = 'Unable to save encounter. Please try again.';
    }
  }

  // Rehydrate encounter values for re-render in case of errors
  $enc = [
    'id' => $id,
    'occurred_at' => date('Y-m-d\TH:i', strtotime($occurred_at)),
    'location_label' => $location_label,
    'location_type' => $location_type,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'physical_intensity' => $physical,
    'emotional_intensity' => $emotional,
    'overall_rating' => $rating,
    'scenario_tag' => $scenario_tag,
    'summary' => $summary_input
  ];
  if (empty($participantsData)) {
    $participantsData = [
      [
        'partner_id' => null,
        'rounds' => [default_round_payload()]
      ]
    ];
  }
  $action = $id ? 'edit' : 'new';
} elseif ($action === 'delete' && isset($_GET['id'])) {
  $db->prepare('DELETE FROM encounters WHERE id=? AND user_id=?')->execute([(int)$_GET['id'],$uid]);
  redirect('encounters.php');
} elseif ($action === 'new' || ($action === 'edit' && isset($_GET['id']))) {
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
    'scenario_tag' => 'standard',
    'summary' => ''
  ];
  $participantsData = [
    [
      'partner_id' => null,
      'rounds' => [default_round_payload()]
    ]
  ];
  if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM encounters WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['id'],$uid]);
    $existing = $stmt->fetch();
    if ($existing) {
      $enc = [
        'id' => (int)$existing['id'],
        'occurred_at' => date('Y-m-d\TH:i', strtotime($existing['occurred_at'])),
        'location_label' => $existing['location_label'],
        'location_type' => $existing['location_type'],
        'latitude' => $existing['latitude'],
        'longitude' => $existing['longitude'],
        'physical_intensity' => $existing['physical_intensity'],
        'emotional_intensity' => $existing['emotional_intensity'],
        'overall_rating' => $existing['overall_rating'],
        'scenario_tag' => $existing['scenario_tag'],
        'summary' => it_decrypt($existing['summary_enc'])
      ];

      $participantStmt = $db->prepare('SELECT id, partner_id FROM encounter_participants WHERE encounter_id=? ORDER BY id ASC');
      $participantStmt->execute([$existing['id']]);
      $participants = $participantStmt->fetchAll(PDO::FETCH_ASSOC);
      if ($participants) {
        $participantIds = array_column($participants, 'id');
        $placeholders = implode(',', array_fill(0, count($participantIds), '?'));
        $roundStmt = $db->prepare("SELECT participant_id, round_order, role, scenario, positions, participant_climax, partner_climax, partner_climax_location, duration_minutes, satisfaction_rating, cleanup_performed_by_partner_id, cleanup_method FROM encounter_rounds WHERE participant_id IN ($placeholders) ORDER BY round_order ASC, id ASC");
        $roundStmt->execute($participantIds);
        $roundRows = $roundStmt->fetchAll(PDO::FETCH_ASSOC);
        $roundsByParticipant = [];
        foreach ($roundRows as $row) {
          $positions = $row['positions'] ? json_decode($row['positions'], true) : [];
          $roundsByParticipant[$row['participant_id']][] = [
            'role' => $row['role'],
            'scenario' => $row['scenario'],
            'positions' => is_array($positions) ? $positions : [],
            'participant_climax' => $row['participant_climax'] === null ? '' : ($row['participant_climax'] ? 'yes' : 'no'),
            'partner_climax' => $row['partner_climax'] === null ? '' : ($row['partner_climax'] ? 'yes' : 'no'),
            'partner_climax_location' => $row['partner_climax_location'] ?? '',
            'duration_minutes' => $row['duration_minutes'] ?? '',
            'satisfaction_rating' => $row['satisfaction_rating'] ?? '',
            'cleanup_partner_id' => $row['cleanup_performed_by_partner_id'],
            'cleanup_method' => $row['cleanup_method']
          ];
        }
        $participantsData = [];
        foreach ($participants as $participant) {
          $rounds = $roundsByParticipant[$participant['id']] ?? [];
          if (!$rounds) {
            $rounds = [default_round_payload()];
          }
          $participantsData[] = [
            'partner_id' => (int)$participant['partner_id'],
            'rounds' => $rounds
          ];
        }
      }
    }
  }
}

$summaryData = [];
if (!empty($participantsData)) {
  $summaryData = build_encounter_summary($participantsData, $partner_opts, $participantRoleOptions, $scenarioOptions, $positionOptions, $cleanupMethodOptions, $climaxLocationOptions);
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
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="icon" href="../assets/img/icons/shield.svg">
  <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://cdn.jsdelivr.net https://unpkg.com https://tile.openstreetmap.org; img-src 'self' data: https://tile.openstreetmap.org; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'">
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
<?php if (!empty($formErrors)): ?>
  <div class="alert alert-warning">
    <?php foreach ($formErrors as $error): ?>
      <div><?= h($error) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
if ($action === 'new' || $action === 'edit') {
?>
<div class="card p-3">
  <h2 class="h5 mb-3"><?= $action === 'new' ? 'Log Encounter' : 'Edit Encounter' ?></h2>
  <form method="post" action="encounters.php?action=<?= $action === 'new' ? 'save_new' : 'save_edit' ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($enc['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Date & time</label>
        <input name="occurred_at" type="datetime-local" class="form-control" value="<?= h($enc['occurred_at']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Location label</label>
        <input name="location_label" id="location-label" list="location-suggestions" class="form-control" placeholder="e.g., Home, Hotel, Outdoors" autocomplete="off" value="<?= h($enc['location_label']) ?>">
        <datalist id="location-suggestions">
          <?php foreach ($savedLocations as $loc): ?>
            <?php if (!empty($loc['label'])): ?>
              <option value="<?= h($loc['label']) ?>"></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </datalist>
        <div class="form-hint">Start typing to reuse a saved location.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Location type</label>
        <select name="location_type" class="form-select">
          <?php foreach (['home','hotel','outdoors','travel','other'] as $t): ?>
            <option value="<?= $t ?>" <?= ($enc['location_type'] ?? 'other') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Saved locations</label>
        <select id="saved-location-select" class="form-select">
          <option value="">Select saved location…</option>
          <?php foreach ($savedLocations as $loc): ?>
            <?php if (!empty($loc['label'])): ?>
              <option value="<?= h($loc['label']) ?>" data-lat="<?= h($loc['latitude']) ?>" data-lng="<?= h($loc['longitude']) ?>">
                <?= h($loc['label']) ?><?php if ($loc['latitude'] !== null && $loc['longitude'] !== null): ?> (<?= round((float)$loc['latitude'], 4) ?>, <?= round((float)$loc['longitude'], 4) ?>)<?php endif; ?>
              </option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Selecting fills coordinates automatically.</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Latitude</label>
        <input name="latitude" class="form-control" value="<?= h($enc['latitude']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Longitude</label>
        <input name="longitude" class="form-control" value="<?= h($enc['longitude']) ?>">
      </div>
      <div class="col-12">
        <div id="encounter-map" class="w-100" style="height:260px;" data-lat="<?= h($enc['latitude']) ?>" data-lng="<?= h($enc['longitude']) ?>"></div>
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
    </div>

    <hr class="my-4">
    <h3 class="h6 mb-3">Participants & rounds</h3>
    <div id="participant-list" data-next-index="<?= count($participantsData) ?>">
      <?php foreach (array_values($participantsData) as $index => $participant): ?>
        <?php render_participant_block($index, $participant, $partner_opts, $participantRoleOptions, $scenarioOptions, $positionOptions, $climaxLocationOptions, $cleanupMethodOptions); ?>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-participant">Add participant</button>

    <div class="card bg-light border-0 p-3 mb-4" id="encounter-summary">
      <h4 class="h6">Encounter summary</h4>
      <div id="encounter-summary-body">
        <?php if (!$summaryData): ?>
          <div class="text-muted small">Add participants and rounds to see the summary.</div>
        <?php else: ?>
          <?php foreach ($summaryData as $participantSummary): ?>
            <div class="mb-2">
              <strong><?= h($participantSummary['partner']) ?></strong>
              <ul class="small mb-1">
                <?php foreach ($participantSummary['rounds'] as $roundSummary): ?>
                  <li>
                    <?= h($roundSummary['role']) ?> • <?= h($roundSummary['scenario']) ?>
                    <?php if ($roundSummary['positions']): ?>
                      <span class="text-muted">(<?= h(implode(', ', $roundSummary['positions'])) ?>)</span>
                    <?php endif; ?>
                    <?php if ($roundSummary['participant_climax'] !== ''): ?>
                      • You: <?= $roundSummary['participant_climax'] === 'yes' ? 'climaxed' : 'no climax' ?>
                    <?php endif; ?>
                    <?php if ($roundSummary['partner_climax'] !== ''): ?>
                      • Partner: <?= $roundSummary['partner_climax'] === 'yes' ? 'climaxed' : 'no climax' ?>
                      <?php if ($roundSummary['partner_climax'] === 'yes' && $roundSummary['partner_climax_location']): ?>
                        (<?= h($roundSummary['partner_climax_location']) ?>)
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($roundSummary['duration_minutes'])): ?>
                      • Duration: <?= (int)$roundSummary['duration_minutes'] ?> min
                    <?php endif; ?>
                    <?php if (!empty($roundSummary['satisfaction_rating'])): ?>
                      • Satisfaction: <?= (int)$roundSummary['satisfaction_rating'] ?>/10
                    <?php endif; ?>
                    <?php if ($roundSummary['cleanup_partner'] || $roundSummary['cleanup_method']): ?>
                      • Cleanup: <?= h($roundSummary['cleanup_method'] ?? 'Not recorded') ?><?php if ($roundSummary['cleanup_partner']): ?> by <?= h($roundSummary['cleanup_partner']) ?><?php endif; ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Scenario tag</label>
        <select name="scenario_tag" class="form-select">
          <option value="standard" <?= ($enc['scenario_tag'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
          <option value="cuckold_observer" <?= ($enc['scenario_tag'] ?? '') === 'cuckold_observer' ? 'selected' : '' ?>>Cuckold (observer present)</option>
          <option value="cuckold_present_partner" <?= ($enc['scenario_tag'] ?? '') === 'cuckold_present_partner' ? 'selected' : '' ?>>Cuckold (committed partner present)</option>
          <option value="group" <?= ($enc['scenario_tag'] ?? '') === 'group' ? 'selected' : '' ?>>Group session</option>
          <option value="other" <?= ($enc['scenario_tag'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
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

<template id="participant-template">
  <div class="participant-block border rounded p-3 mb-3" data-participant-index="__PID__" data-next-round-index="1">
    <div class="d-flex flex-column flex-md-row align-items-md-end gap-3 mb-3">
      <div class="flex-grow-1">
        <label class="form-label">Participant</label>
        <select name="participants[__PID__][partner_id]" class="form-select participant-partner-select" required>
          <option value="">Select partner…</option>
<?php foreach ($partner_opts as $pid => $pname): ?>
          <option value="<?= (int)$pid ?>"><?= h($pname) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div>
        <button type="button" class="btn btn-outline-danger btn-sm participant-remove">Remove participant</button>
      </div>
    </div>
    <div class="rounds-list">
<?php render_round_row('__PID__', 0, default_round_payload(), $participantRoleOptions, $scenarioOptions, $positionOptions, $climaxLocationOptions, $cleanupMethodOptions, $partner_opts); ?>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm add-round">Add round</button>
  </div>
</template>

<template id="round-template">
<?php render_round_row('__PID__', '__RID__', default_round_payload(), $participantRoleOptions, $scenarioOptions, $positionOptions, $climaxLocationOptions, $cleanupMethodOptions, $partner_opts); ?>
</template>
<?php
} else {
  $rows = $db->query("SELECT e.*, 
    (SELECT COUNT(*) FROM encounter_participants ep WHERE ep.encounter_id = e.id) AS participant_count,
    (SELECT COUNT(*) FROM encounter_rounds er JOIN encounter_participants ep ON er.participant_id = ep.id WHERE ep.encounter_id = e.id) AS round_count
    FROM encounters e WHERE user_id = $uid ORDER BY occurred_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h5 m-0">Encounters</h2>
  <a class="btn btn-sm btn-brand" href="encounters.php?action=new">Log Encounter</a>
</div>
<div class="card p-0">
  <table class="table table-hover align-middle m-0">
    <thead><tr><th>Date</th><th>Location</th><th>Intensity (P/E)</th><th>Participants</th><th>Rounds</th><th>Overall</th><th>Scenario</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h(date('M j, Y g:ia', strtotime($r['occurred_at']))) ?></td>
          <td><?= h($r['location_label'] ?: ucfirst($r['location_type'])) ?></td>
          <td><?= (int)$r['physical_intensity'] ?>/<?= (int)$r['emotional_intensity'] ?></td>
          <td><?= (int)$r['participant_count'] ?></td>
          <td><?= (int)$r['round_count'] ?></td>
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
<script type="application/json" id="saved-location-data"><?= json_encode($savedLocations) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/encounter.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
