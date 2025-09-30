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

// Fetch summary stats
$uid = current_user()['id'];
$stats = [
  'encounters' => (int)$db->query("SELECT COUNT(*) c FROM encounters WHERE user_id = $uid")->fetch()['c'],
  'partners' => (int)$db->query("SELECT COUNT(*) c FROM partners WHERE user_id = $uid")->fetch()['c'],
  'last_7' => (int)$db->query("SELECT COUNT(*) c FROM encounters WHERE user_id = $uid AND occurred_at >= NOW() - INTERVAL 7 DAY")->fetch()['c']
];

// Fetch latest 10
$stmt = $db->prepare("SELECT e.*, 
  (SELECT COUNT(*) FROM encounter_participants ep WHERE ep.encounter_id=e.id) as participant_count
  FROM encounters e WHERE e.user_id=? ORDER BY occurred_at DESC LIMIT 10");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

// For map markers
$stmt2 = $db->prepare("SELECT id, location_label, latitude, longitude FROM encounters WHERE user_id=? AND latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY occurred_at DESC LIMIT 100");
$stmt2->execute([$uid]);
$markers = $stmt2->fetchAll();
?>
<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card p-3 h-100">
      <h2 class="h5 mb-3">Summary</h2>
      <div class="d-flex flex-column gap-2">
        <div><span class="badge-legend"><span class="timeline-dot"></span> Encounters</span> <span class="float-end fw-semibold"><?= $stats['encounters'] ?></span></div>
        <div><span class="badge-legend"><img src="../assets/img/icons/heart.svg" width="16"> Partners</span> <span class="float-end fw-semibold"><?= $stats['partners'] ?></span></div>
        <div><span class="badge-legend"><img src="../assets/img/icons/map.svg" width="16"> Last 7 days</span> <span class="float-end fw-semibold"><?= $stats['last_7'] ?></span></div>
      </div>
      <hr>
      <a class="btn btn-brand w-100" href="encounters.php?action=new">Log Encounter</a>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card p-3">
      <h2 class="h5 mb-3">Map</h2>
      <div id="map" style="height:320px;"></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card p-3">
      <h2 class="h5 mb-3">Recent Timeline</h2>
      <?php if (!$recent): ?>
        <div class="small-muted">No entries yet. Start with “Log Encounter”.</div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($recent as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <span class="timeline-dot"></span>
                <strong><?= date('M j, Y g:ia', strtotime($r['occurred_at'])) ?></strong>
                <span class="small-muted">• <?= h($r['location_label'] ?: ucfirst($r['location_type'])) ?></span>
                <div class="small-muted">
                  Physical <?= (int)$r['physical_intensity'] ?>/10,
                  Emotional <?= (int)$r['emotional_intensity'] ?>/10,
                  Participants <?= (int)$r['participant_count'] ?>
                </div>
              </div>
              <div>
                <a class="btn btn-sm btn-outline-secondary" href="encounters.php?action=edit&id=<?= (int)$r['id'] ?>">Open</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const markers = <?= json_encode($markers) ?>;
const map = L.map('map', {zoomControl:true});
const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OSM'}).addTo(map);

if (markers.length) {
  const group = L.featureGroup(markers.filter(m=>m.latitude && m.longitude).map(m => {
    return L.marker([m.latitude, m.longitude]).bindPopup(`<strong>${m.location_label ?? 'Encounter'}</strong>`);
  }));
  group.addTo(map);
  map.fitBounds(group.getBounds().pad(0.2));
} else {
  map.setView([0,0], 2);
}
</script>

</main>
<footer class="app container text-center small">
  <div>Private by design • Local-first journal • Encrypted fields</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
