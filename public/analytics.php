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

// Aggregate stats
$by_partner = $db->prepare("SELECT p.name, COUNT(*) c, AVG(e.physical_intensity) pavg, AVG(e.emotional_intensity) eavg, AVG(e.overall_rating) ravg
  FROM encounters e
  JOIN encounter_participants ep ON ep.encounter_id = e.id
  JOIN partners p ON p.id = ep.partner_id
  WHERE e.user_id=?
  GROUP BY p.name
  ORDER BY c DESC");
$by_partner->execute([$uid]);
$by_partner = $by_partner->fetchAll();

$by_location = $db->prepare("SELECT COALESCE(NULLIF(location_label,''), location_type) label, COUNT(*) c, AVG(physical_intensity) pavg, AVG(emotional_intensity) eavg
  FROM encounters WHERE user_id=? GROUP BY label ORDER BY c DESC");
$by_location->execute([$uid]);
$by_location = $by_location->fetchAll();

$freq_by_day = $db->prepare("SELECT DATE(occurred_at) d, COUNT(*) c FROM encounters WHERE user_id=? GROUP BY d ORDER BY d ASC");
$freq_by_day->execute([$uid]);
$freq = $freq_by_day->fetchAll();
?>
<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="card p-3">
      <h2 class="h6">Intensity by Partner</h2>
      <canvas id="chartPartner" height="200"></canvas>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card p-3">
      <h2 class="h6">Top Locations</h2>
      <canvas id="chartLocation" height="200"></canvas>
    </div>
  </div>
  <div class="col-12">
    <div class="card p-3">
      <h2 class="h6">Timeline Frequency</h2>
      <canvas id="chartFreq" height="200"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const byPartner = <?= json_encode($by_partner) ?>;
const byLocation = <?= json_encode($by_location) ?>;
const freq = <?= json_encode($freq) ?>;

new Chart(document.getElementById('chartPartner'), {
  type:'bar',
  data:{
    labels: byPartner.map(r=>r.name),
    datasets:[
      {label:'Physical', data: byPartner.map(r=>Number(r.pavg?.toFixed(2) || 0))},
      {label:'Emotional', data: byPartner.map(r=>Number(r.eavg?.toFixed(2) || 0))},
      {label:'Overall', data: byPartner.map(r=>Number(r.ravg?.toFixed(2) || 0))}
    ]
  },
  options:{responsive:true}
});

new Chart(document.getElementById('chartLocation'), {
  type:'bar',
  data:{
    labels: byLocation.map(r=>r.label),
    datasets:[
      {label:'Physical avg', data: byLocation.map(r=>Number((r.pavg||0).toFixed(2)))},
      {label:'Emotional avg', data: byLocation.map(r=>Number((r.eavg||0).toFixed(2)))}
    ]
  }
});

new Chart(document.getElementById('chartFreq'), {
  type:'line',
  data:{
    labels: freq.map(r=>r.d),
    datasets:[{label:'Encounters', data: freq.map(r=>r.c)}]
  }
});
</script>

</main>
<footer class="app container text-center small">
  <div>Private by design • Local-first journal • Encrypted fields</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
