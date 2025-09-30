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
$uid = current_user()['id'];

$scenarioLabels = [
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

$penisSizeLabels = [
  'xs_under_4' => 'X-Small (≤ 4")',
  'small_4_5' => 'Small (4" - 5")',
  'average_5_6' => 'Average (5" - 6")',
  'above_avg_6_7' => 'Above average (6" - 7")',
  'large_7_8' => 'Large (7" - 8")',
  'xl_over_8' => 'X-Large (≥ 8")'
];

$climaxLocationLabels = [
  'vaginal_internal' => 'Vaginal (internal)',
  'vaginal_external' => 'Vaginal (external)',
  'anal_internal' => 'Anal (internal)',
  'anal_external' => 'Anal (external)',
  'oral' => 'Oral',
  'facial' => 'Facial',
  'breasts_chest' => 'Breasts / chest',
  'stomach_thighs' => 'Stomach / thighs',
  'other_location' => 'Other location'
];

$roundSummaryStmt = $db->prepare('SELECT COUNT(*) AS rounds, AVG(er.satisfaction_rating) AS avg_satisfaction, AVG(er.duration_minutes) AS avg_duration
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN encounters e ON ep.encounter_id = e.id
  WHERE e.user_id = ?');
$roundSummaryStmt->execute([$uid]);
$roundSummary = $roundSummaryStmt->fetch() ?: ['rounds' => 0, 'avg_satisfaction' => null, 'avg_duration' => null];

$scenarioStatsStmt = $db->prepare('SELECT er.scenario, COUNT(*) AS rounds, AVG(er.satisfaction_rating) AS avg_satisfaction, AVG(er.duration_minutes) AS avg_duration
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN encounters e ON ep.encounter_id = e.id
  WHERE e.user_id = ?
  GROUP BY er.scenario
  ORDER BY rounds DESC');
$scenarioStatsStmt->execute([$uid]);
$scenarioStats = $scenarioStatsStmt->fetchAll();

$partnerSatisfactionStmt = $db->prepare('SELECT p.id, p.name, COUNT(*) AS rounds, AVG(er.satisfaction_rating) AS avg_satisfaction, SUM(er.duration_minutes) AS total_duration, AVG(er.duration_minutes) AS avg_duration
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN encounters e ON ep.encounter_id = e.id
  JOIN partners p ON p.id = ep.partner_id
  WHERE e.user_id = ?
  GROUP BY p.id, p.name
  HAVING rounds > 0
  ORDER BY avg_satisfaction DESC');
$partnerSatisfactionStmt->execute([$uid]);
$partnerSatisfaction = $partnerSatisfactionStmt->fetchAll();

$partnerAggregateStmt = $db->prepare('SELECT 
    p.id,
    p.name,
    COUNT(DISTINCT e.id) AS encounter_count,
    COUNT(er.id) AS round_count,
    AVG(er.satisfaction_rating) AS avg_round_satisfaction,
    AVG(er.duration_minutes) AS avg_round_duration,
    SUM(er.duration_minutes) AS total_round_duration,
    AVG(e.physical_intensity) AS avg_physical,
    AVG(e.emotional_intensity) AS avg_emotional,
    AVG(e.overall_rating) AS avg_overall
  FROM partners p
  LEFT JOIN encounter_participants ep ON ep.partner_id = p.id
  LEFT JOIN encounters e ON e.id = ep.encounter_id AND e.user_id = p.user_id
  LEFT JOIN encounter_rounds er ON er.participant_id = ep.id
  WHERE p.user_id = ?
  GROUP BY p.id, p.name');
$partnerAggregateStmt->execute([$uid]);
$partnerAggregate = $partnerAggregateStmt->fetchAll(PDO::FETCH_ASSOC);

$byLocationStmt = $db->prepare('SELECT COALESCE(NULLIF(location_label, \'\'), location_type) AS label, COUNT(*) AS c, AVG(physical_intensity) AS pavg, AVG(emotional_intensity) AS eavg
  FROM encounters WHERE user_id = ? GROUP BY label ORDER BY c DESC');
$byLocationStmt->execute([$uid]);
$by_location = $byLocationStmt->fetchAll(PDO::FETCH_ASSOC);

$freq_by_day = $db->prepare('SELECT DATE(occurred_at) AS d, COUNT(*) AS c FROM encounters WHERE user_id = ? GROUP BY d ORDER BY d ASC');
$freq_by_day->execute([$uid]);
$freq = $freq_by_day->fetchAll(PDO::FETCH_ASSOC);

$satisfactionBySizeStmt = $db->prepare('SELECT p.penis_size_rating AS size, COUNT(*) AS rounds, AVG(er.satisfaction_rating) AS avg_satisfaction, AVG(er.duration_minutes) AS avg_duration
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN partners p ON p.id = ep.partner_id
  JOIN encounters e ON ep.encounter_id = e.id
  WHERE e.user_id = ? AND p.penis_size_rating IS NOT NULL
  GROUP BY p.penis_size_rating
  ORDER BY avg_satisfaction DESC');
$satisfactionBySizeStmt->execute([$uid]);
$satisfactionBySize = $satisfactionBySizeStmt->fetchAll(PDO::FETCH_ASSOC);

$climaxLocationStmt = $db->prepare('SELECT er.partner_climax_location AS location, COUNT(*) AS total
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN encounters e ON ep.encounter_id = e.id
  WHERE e.user_id = ? AND er.partner_climax = 1 AND er.partner_climax_location IS NOT NULL
  GROUP BY er.partner_climax_location
  ORDER BY total DESC');
$climaxLocationStmt->execute([$uid]);
$climaxLocationTotals = $climaxLocationStmt->fetchAll(PDO::FETCH_ASSOC);

$climaxStatsStmt = $db->prepare('SELECT AVG(er.participant_climax) AS participant_rate, AVG(er.partner_climax) AS partner_rate
  FROM encounter_rounds er
  JOIN encounter_participants ep ON er.participant_id = ep.id
  JOIN encounters e ON ep.encounter_id = e.id
  WHERE e.user_id = ?');
$climaxStatsStmt->execute([$uid]);
$climaxStats = $climaxStatsStmt->fetch() ?: ['participant_rate' => null, 'partner_rate' => null];

$totalEncountersStmt = $db->prepare('SELECT COUNT(*) FROM encounters WHERE user_id = ?');
$totalEncountersStmt->execute([$uid]);
$totalEncounters = (int)$totalEncountersStmt->fetchColumn();

$engagedPartnersStmt = $db->prepare('SELECT COUNT(DISTINCT ep.partner_id) FROM encounter_participants ep JOIN encounters e ON ep.encounter_id = e.id WHERE e.user_id = ?');
$engagedPartnersStmt->execute([$uid]);
$engagedPartners = (int)$engagedPartnersStmt->fetchColumn();

$avgSatisfaction = $roundSummary['avg_satisfaction'] !== null ? round((float)$roundSummary['avg_satisfaction'], 1) : null;
$avgDuration = $roundSummary['avg_duration'] !== null ? round((float)$roundSummary['avg_duration'], 1) : null;
$topScenarioKey = $scenarioStats[0]['scenario'] ?? null;
$topScenarioLabel = $topScenarioKey ? ($scenarioLabels[$topScenarioKey] ?? ucwords(str_replace('_', ' ', $topScenarioKey))) : null;
$participantClimaxRate = $climaxStats['participant_rate'] !== null ? round((float)$climaxStats['participant_rate'] * 100) : null;
$partnerClimaxRate = $climaxStats['partner_rate'] !== null ? round((float)$climaxStats['partner_rate'] * 100) : null;

$partnerScores = [];
foreach ($partnerAggregate as $row) {
  $roundCount = (int)($row['round_count'] ?? 0);
  $avgSat = $row['avg_round_satisfaction'] !== null ? (float)$row['avg_round_satisfaction'] : null;
  $avgOverall = $row['avg_overall'] !== null ? (float)$row['avg_overall'] : null;
  $avgDuration = $row['avg_round_duration'] !== null ? (float)$row['avg_round_duration'] : null;
  $totalDuration = $row['total_round_duration'] !== null ? (float)$row['total_round_duration'] : 0;

  $satComponent = $avgSat !== null ? $avgSat / 10 : 0.5;
  $overallComponent = $avgOverall !== null ? $avgOverall / 5 : 0.5;
  $volumeComponent = $roundCount > 0 ? min($roundCount, 20) / 20 : 0;
  $durationComponent = $avgDuration !== null ? min($avgDuration, 120) / 120 : 0.5;

  $score = round(($satComponent * 0.45 + $overallComponent * 0.25 + $volumeComponent * 0.2 + $durationComponent * 0.1) * 100, 1);

  $partnerScores[] = array_merge($row, [
    'score' => $score,
    'avg_round_satisfaction' => $avgSat,
    'avg_round_duration' => $avgDuration,
    'round_count' => $roundCount,
    'total_round_duration' => $totalDuration
  ]);
}

$generalStats = [];
if ($partnerScores) {
  $mostEncounters = $partnerScores[0];
  usort($partnerScores, fn($a,$b) => $b['encounter_count'] <=> $a['encounter_count']);
  $mostEncounters = $partnerScores[0];
  $generalStats[] = [
    'label' => 'Most encounters',
    'value' => $mostEncounters['name'],
    'detail' => $mostEncounters['encounter_count'] . ' encounters'
  ];
  usort($partnerScores, fn($a,$b) => $b['score'] <=> $a['score']);
  $generalStats[] = [
    'label' => 'Highest partner score',
    'value' => $partnerScores[0]['name'],
    'detail' => 'Score ' . number_format($partnerScores[0]['score'], 1)
  ];
}

if ($scenarioStats) {
  $byAvgSatisfaction = array_values(array_filter($scenarioStats, fn($row) => $row['avg_satisfaction'] !== null));
  if ($byAvgSatisfaction) {
    usort($byAvgSatisfaction, fn($a,$b) => $b['avg_satisfaction'] <=> $a['avg_satisfaction']);
    $bestScenario = $byAvgSatisfaction[0];
    $generalStats[] = [
      'label' => 'Highest satisfaction scenario',
      'value' => $scenarioLabels[$bestScenario['scenario']] ?? ucwords(str_replace('_',' ', $bestScenario['scenario'])),
      'detail' => number_format((float)$bestScenario['avg_satisfaction'], 1) . '/10 over ' . (int)$bestScenario['rounds'] . ' rounds'
    ];
  }
}

if ($climaxLocationTotals) {
  $topLocation = $climaxLocationTotals[0];
  $generalStats[] = [
    'label' => 'Top climax location',
    'value' => $climaxLocationLabels[$topLocation['location']] ?? ucwords(str_replace('_',' ', $topLocation['location'])),
    'detail' => (int)$topLocation['total'] . ' partner climaxes'
  ];
}
usort($partnerScores, fn($a,$b) => $b['score'] <=> $a['score']);

$partnerHighlights = [
  'topSatisfaction' => null,
  'needsAttention' => null,
  'longestDuration' => null
];

if ($partnerScores) {
  $partnerHighlights['topSatisfaction'] = $partnerScores[0];
  if (count($partnerScores) > 1) {
    $partnerHighlights['needsAttention'] = $partnerScores[array_key_last($partnerScores)];
  }
  $durationSorted = array_values(array_filter($partnerScores, fn($row) => $row['avg_round_duration'] !== null));
  if ($durationSorted) {
    usort($durationSorted, fn($a,$b) => $b['avg_round_duration'] <=> $a['avg_round_duration']);
    $partnerHighlights['longestDuration'] = $durationSorted[0];
  }
}
?>
<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Encounters logged</div>
      <div class="display-6 fw-semibold"><?= $totalEncounters ?></div>
      <div class="small-muted">Rounds captured: <?= (int)($roundSummary['rounds'] ?? 0) ?></div>
      <div class="small-muted">Partners engaged: <?= $engagedPartners ?></div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Average satisfaction</div>
      <div class="display-6 fw-semibold"><?= $avgSatisfaction !== null ? $avgSatisfaction : '—' ?><span class="fs-5">/10</span></div>
      <div class="small-muted">Based on <?= (int)($roundSummary['rounds'] ?? 0) ?> rounds</div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Average round duration</div>
      <div class="display-6 fw-semibold"><?= $avgDuration !== null ? $avgDuration : '—' ?><span class="fs-5"> min</span></div>
      <div class="small-muted">Top scenario: <?= $topScenarioLabel ? h($topScenarioLabel) : '—' ?></div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Climax rate</div>
      <div class="display-6 fw-semibold">
        <?= $participantClimaxRate !== null ? $participantClimaxRate . '%' : '—' ?>
        <span class="fs-6 text-muted">you</span>
      </div>
      <div class="small-muted">Partners: <?= $partnerClimaxRate !== null ? $partnerClimaxRate . '%' : '—' ?></div>
    </div>
  </div>
</div>

<?php if (array_filter($partnerHighlights)): ?>
<div class="row g-3 mb-3">
  <div class="col-12 col-lg-4">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Top partner (satisfaction)</div>
      <?php if ($partnerHighlights['topSatisfaction']): ?>
        <div class="h5 mb-1"><?= h($partnerHighlights['topSatisfaction']['name']) ?></div>
        <div class="small-muted">Avg satisfaction: <?= $partnerHighlights['topSatisfaction']['avg_round_satisfaction'] !== null ? number_format((float)$partnerHighlights['topSatisfaction']['avg_round_satisfaction'], 1) : '—' ?>/10 • Rounds <?= (int)$partnerHighlights['topSatisfaction']['round_count'] ?></div>
        <?php if ($partnerHighlights['topSatisfaction']['avg_round_duration'] !== null): ?>
          <div class="small-muted">Avg duration: <?= number_format((float)$partnerHighlights['topSatisfaction']['avg_round_duration'], 1) ?> min</div>
        <?php endif; ?>
        <div class="small-muted">Score: <?= number_format((float)$partnerHighlights['topSatisfaction']['score'], 1) ?></div>
      <?php else: ?>
        <div class="small-muted">No round data yet.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Needs attention</div>
      <?php if ($partnerHighlights['needsAttention']): ?>
        <div class="h5 mb-1"><?= h($partnerHighlights['needsAttention']['name']) ?></div>
        <div class="small-muted">Avg satisfaction: <?= $partnerHighlights['needsAttention']['avg_round_satisfaction'] !== null ? number_format((float)$partnerHighlights['needsAttention']['avg_round_satisfaction'], 1) : '—' ?>/10 • Rounds <?= (int)$partnerHighlights['needsAttention']['round_count'] ?></div>
        <?php if ($partnerHighlights['needsAttention']['avg_round_duration'] !== null): ?>
          <div class="small-muted">Avg duration: <?= number_format((float)$partnerHighlights['needsAttention']['avg_round_duration'], 1) ?> min</div>
        <?php endif; ?>
        <div class="small-muted">Score: <?= number_format((float)$partnerHighlights['needsAttention']['score'], 1) ?></div>
      <?php else: ?>
        <div class="small-muted">Not enough data yet.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card p-3 h-100">
      <div class="small-muted text-uppercase">Longest average duration</div>
      <?php if ($partnerHighlights['longestDuration']): ?>
        <div class="h5 mb-1"><?= h($partnerHighlights['longestDuration']['name']) ?></div>
        <div class="small-muted">Avg duration: <?= number_format((float)$partnerHighlights['longestDuration']['avg_round_duration'], 1) ?> min • Rounds <?= (int)$partnerHighlights['longestDuration']['round_count'] ?></div>
        <?php if ($partnerHighlights['longestDuration']['avg_round_satisfaction'] !== null): ?>
          <div class="small-muted">Avg satisfaction: <?= number_format((float)$partnerHighlights['longestDuration']['avg_round_satisfaction'], 1) ?>/10</div>
        <?php endif; ?>
        <div class="small-muted">Score: <?= number_format((float)$partnerHighlights['longestDuration']['score'], 1) ?></div>
      <?php else: ?>
        <div class="small-muted">Record more durations to populate this card.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Satisfaction by size</h2>
      <?php if (!$satisfactionBySize): ?>
        <div class="small-muted">Log rounds with partner size data to see this insight.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Size</th>
                <th class="text-end">Rounds</th>
                <th class="text-end">Avg satisfaction</th>
                <th class="text-end">Avg duration</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($satisfactionBySize as $row): ?>
                <tr>
                  <td><?= h($penisSizeLabels[$row['size']] ?? ucwords(str_replace('_',' ', (string)$row['size']))) ?></td>
                  <td class="text-end"><?= (int)$row['rounds'] ?></td>
                  <td class="text-end"><?= $row['avg_satisfaction'] !== null ? number_format((float)$row['avg_satisfaction'], 1) . '/10' : '—' ?></td>
                  <td class="text-end"><?= $row['avg_duration'] !== null ? number_format((float)$row['avg_duration'], 1) . ' min' : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Partner climax locations</h2>
      <?php if (!$climaxLocationTotals): ?>
        <div class="small-muted">Record climax locations to surface this data.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Location</th>
                <th class="text-end">Climaxes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($climaxLocationTotals as $row): ?>
                <tr>
                  <td><?= h($climaxLocationLabels[$row['location']] ?? ucwords(str_replace('_',' ', $row['location']))) ?></td>
                  <td class="text-end"><?= (int)$row['total'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($generalStats): ?>
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Highlights</h2>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="small-muted">
            <tr>
              <th>Metric</th>
              <th>Leader</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($generalStats as $row): ?>
              <tr>
                <td><?= h($row['label']) ?></td>
                <td><?= h($row['value']) ?></td>
                <td><?= h($row['detail']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Scenario breakdown</h2>
      <?php if (!$scenarioStats): ?>
        <div class="small-muted">Log rounds to see scenario trends.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Scenario</th>
                <th class="text-end">Rounds</th>
                <th class="text-end">Avg satisfaction</th>
                <th class="text-end">Avg duration</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($scenarioStats as $row): ?>
                <tr>
                  <td><?= h($scenarioLabels[$row['scenario']] ?? ucwords(str_replace('_',' ', $row['scenario']))) ?></td>
                  <td class="text-end"><?= (int)$row['rounds'] ?></td>
                  <td class="text-end"><?= $row['avg_satisfaction'] !== null ? number_format((float)$row['avg_satisfaction'], 1) : '—' ?></td>
                  <td class="text-end"><?= $row['avg_duration'] !== null ? number_format((float)$row['avg_duration'], 1) . ' min' : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Partner satisfaction</h2>
      <?php if (!$partnerScores): ?>
        <div class="small-muted">Add rounds with partners to unlock this insight.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Partner</th>
                <th class="text-end">Rounds</th>
                <th class="text-end">Avg satisfaction</th>
                <th class="text-end">Avg duration</th>
                <th class="text-end">Total duration</th>
                <th class="text-end">Score</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($partnerScores as $row): ?>
                <tr>
                  <td><?= h($row['name']) ?></td>
                  <td class="text-end"><?= (int)$row['round_count'] ?></td>
                  <td class="text-end"><?= $row['avg_round_satisfaction'] !== null ? number_format((float)$row['avg_round_satisfaction'], 1) . '/10' : '—' ?></td>
                  <td class="text-end"><?= $row['avg_round_duration'] !== null ? number_format((float)$row['avg_round_duration'], 1) . ' min' : '—' ?></td>
                  <td class="text-end"><?= (int)$row['total_round_duration'] ?> min</td>
                  <td class="text-end fw-semibold"><?= number_format((float)$row['score'], 1) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Intensity by partner</h2>
      <?php if (!$partnerScores): ?>
        <div class="small-muted">No encounters logged yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Partner</th>
                <th class="text-end">Physical</th>
                <th class="text-end">Emotional</th>
                <th class="text-end">Overall</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($partnerScores as $row): ?>
                <tr>
                  <td><?= h($row['name']) ?></td>
                  <td class="text-end"><?= $row['avg_physical'] !== null ? number_format((float)$row['avg_physical'], 1) : '—' ?></td>
                  <td class="text-end"><?= $row['avg_emotional'] !== null ? number_format((float)$row['avg_emotional'], 1) : '—' ?></td>
                  <td class="text-end"><?= $row['avg_overall'] !== null ? number_format((float)$row['avg_overall'], 1) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Top locations</h2>
      <?php if (!$by_location): ?>
        <div class="small-muted">Log encounters with locations to populate this section.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Location</th>
                <th class="text-end">Encounters</th>
                <th class="text-end">Physical avg</th>
                <th class="text-end">Emotional avg</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($by_location as $row): ?>
                <tr>
                  <td><?= h($row['label']) ?></td>
                  <td class="text-end"><?= (int)$row['c'] ?></td>
                  <td class="text-end"><?= number_format((float)$row['pavg'], 1) ?></td>
                  <td class="text-end"><?= number_format((float)$row['eavg'], 1) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Timeline frequency</h2>
      <?php if (!$freq): ?>
        <div class="small-muted">No encounters logged yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="small-muted">
              <tr>
                <th>Date</th>
                <th class="text-end">Encounters</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($freq as $row): ?>
                <tr>
                  <td><?= h(date('M j, Y', strtotime($row['d']))) ?></td>
                  <td class="text-end"><?= (int)$row['c'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</main>
<footer class="app container text-center small">
  <div>Private by design • Local-first journal • Encrypted fields</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
