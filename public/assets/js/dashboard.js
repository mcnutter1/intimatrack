document.addEventListener('DOMContentLoaded', () => {
  const mapElement = document.getElementById('map');
  if (!mapElement || typeof L === 'undefined') return;

  let markers = [];
  const dataEl = document.getElementById('dashboard-map-data');
  if (dataEl) {
    try {
      markers = JSON.parse(dataEl.textContent || '[]');
    } catch (err) {
      console.warn('Unable to parse map markers payload', err);
    }
  }

  const map = L.map(mapElement, { zoomControl: true });
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const validMarkers = (markers || []).filter(m => m && m.latitude && m.longitude);
  if (validMarkers.length) {
    const group = L.featureGroup(
      validMarkers.map(m => L.marker([m.latitude, m.longitude]).bindPopup(`<strong>${m.location_label ?? 'Encounter'}</strong>`))
    );
    group.addTo(map);
    map.fitBounds(group.getBounds().pad(0.2));
  } else {
    map.setView([20, 0], 2);
  }
});
