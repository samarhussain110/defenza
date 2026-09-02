<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

dashHead('Nearby Safe Places');
dashOpen('Nearby Safe Places', 'Nearby Safe Places');
?>

<div id="nearbyPlacesPage" data-api-url="<?= APP_URL ?>/api/nearby-places.php" style="margin-top:1rem;">
  <div class="safe-places-header">
    <div class="map-location-info">
      <div>
        <div class="section-badge"><i class="fa-solid fa-map"></i> Safe Zones Locator</div>
        <h2 style="margin:.5rem 0 0;font-size:1.3rem;font-weight:900;color:#111827;">Nearby Safe Places</h2>
        <p style="margin:.5rem 0 0;color:#6b7280;font-size:.9rem;">Find the closest police stations, hospitals, and women helpline centers near your location.</p>
      </div>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:flex-end;align-items:center;">
        <button id="refreshNearbyPlaces" class="btn-defenza btn-secondary btn-sm"><i class="fa-solid fa-rotate-right"></i> Refresh Location</button>
        <button id="toggleMapView" class="btn-defenza btn-primary btn-sm"><i class="fa-solid fa-map"></i> View Map</button>
      </div>
    </div>
  </div>

  <div id="mapContainer" class="interactive-map-container" style="display: none;">
    <div id="leafletMap" style="width:100%;height:400px;border-radius:1rem;box-shadow:var(--shadow-md); z-index: 1;"></div>
    <div id="mapStatus" class="map-status-label">Loading map…</div>
    <div class="map-controls">
      <button id="centerMapBtn" class="map-control-btn" title="Center Map on Your Location"><i class="fa-solid fa-location-crosshairs"></i></button>
    </div>
  </div>

  <div class="places-stats-bar">
    <div class="stat-item">
      <div class="stat-icon-mini" style="background:linear-gradient(135deg,#2563eb,#7c3aed);">
        <i class="fa-solid fa-shield"></i>
      </div>
      <div>
        <div class="stat-label-mini">Police Stations</div>
        <div class="stat-value-mini" id="statPolice">—</div>
      </div>
    </div>
    <div class="stat-item">
      <div class="stat-icon-mini" style="background:linear-gradient(135deg,#0ea5e9,#3b82f6);">
        <i class="fa-solid fa-hospital"></i>
      </div>
      <div>
        <div class="stat-label-mini">Hospitals</div>
        <div class="stat-value-mini" id="statHospital">—</div>
      </div>
    </div>
    <div class="stat-item">
      <div class="stat-icon-mini" style="background:linear-gradient(135deg,#ec4899,#db2777);">
        <i class="fa-solid fa-phone"></i>
      </div>
      <div>
        <div class="stat-label-mini">Helpline Centers</div>
        <div class="stat-value-mini" id="statHelpline">—</div>
      </div>
    </div>
  </div>

  <div class="places-container">
    <div class="places-header">
      <h3 style="margin:0;font-weight:900;color:#111827;">Available Safe Places</h3>
      <div class="places-filter">
        <button class="filter-btn active" data-filter="all"><i class="fa-solid fa-list"></i> All</button>
        <button class="filter-btn" data-filter="police"><i class="fa-solid fa-shield"></i> Police</button>
        <button class="filter-btn" data-filter="hospital"><i class="fa-solid fa-hospital"></i> Hospital</button>
        <button class="filter-btn" data-filter="helpline"><i class="fa-solid fa-phone"></i> Helpline</button>
      </div>
    </div>

    <div id="nearbyPlacesList" class="safe-places-grid"></div>
  </div>

  <div id="emergencyCallModal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px;">
      <div class="modal-header">
        <span class="modal-title" id="emergencyCallTitle">🚨 Calling...</span>
        <button class="modal-close" onclick="closeModal('emergencyCallModal')"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" style="text-align:center;">
        <div style="font-weight:900;color:#111827;margin-bottom:.25rem;" id="emergencyCallName"></div>
        <div style="font-size:.85rem;color:#6b7280;margin-bottom:.75rem;">
          Emergency Number:<br>
          <span style="font-size:1.15rem;font-weight:900;color:var(--purple-700);" id="emergencyCallNumber"></span>
        </div>
        <div style="font-size:.75rem;color:#9ca3af;">(Actual calling is available only on mobile devices.)</div>
      </div>
      <div class="modal-footer">
        <button class="btn-defenza btn-secondary" onclick="copyEmergencyNumber()"><i class="fa-solid fa-copy"></i> Copy Number</button>
        <button class="btn-defenza btn-primary" onclick="closeModal('emergencyCallModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
  .safe-places-header {
    background: linear-gradient(135deg, #f3e8ff, #fce7f3);
    border-radius: var(--r-xl);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(168,85,247,.15);
  }
  .map-location-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    flex-wrap: wrap;
  }
  .interactive-map-container {
    position: relative;
    margin-bottom: 1.5rem;
  }
  .map-status-label {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(0,0,0,.85);
    color: #fff;
    padding: .5rem 1rem;
    border-radius: var(--r-full);
    font-size: .8rem;
    font-weight: 700;
    z-index: 99;
    display: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  }
  .map-status-label.show {
    display: block;
  }
  .map-controls {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    z-index: 99;
  }
  .map-control-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--r-md);
    background: #fff;
    border: 1px solid rgba(168,85,247,.2);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--purple-700);
    font-size: .9rem;
    transition: all var(--dur-base) ease;
    box-shadow: var(--shadow-md);
  }
  .map-control-btn:hover {
    background: var(--purple-50);
    border-color: var(--purple-300);
    transform: scale(1.05);
  }
  .places-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .stat-item {
    background: #fff;
    border: 1px solid rgba(168,85,247,.12);
    border-radius: var(--r-lg);
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .stat-icon-mini {
    width: 50px;
    height: 50px;
    border-radius: var(--r-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    flex-shrink: 0;
  }
  .stat-label-mini {
    font-size: .75rem;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
  }
  .stat-value-mini {
    font-size: 1.3rem;
    font-weight: 900;
    color: #111827;
  }
  .places-container {
    background: #fff;
    border-radius: var(--r-xl);
    border: 1px solid rgba(168,85,247,.12);
    padding: 1.5rem;
  }
  .places-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }
  .places-filter {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
  }
  .filter-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .45rem .85rem;
    border-radius: var(--r-full);
    border: 1.5px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: .8rem;
    font-weight: 600;
    color: #6b7280;
    transition: all var(--dur-base) ease;
  }
  .filter-btn:hover {
    border-color: var(--purple-300);
    color: var(--purple-700);
  }
  .filter-btn.active {
    background: var(--grad-main);
    color: #fff;
    border-color: transparent;
  }
  .safe-places-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
  }
  .safe-place-card {
    border: 1px solid rgba(168,85,247,0.12);
    border-radius: 1rem;
    padding: 1.25rem;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .safe-place-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(168,85,247,0.08);
  }
  .place-type-badge {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0.25rem 0.5rem;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
  }
  .badge-police { background: rgba(37,99,235,0.1); color: #2563eb; }
  .badge-hospital { background: rgba(14,165,233,0.1); color: #0ea5e9; }
  .badge-helpline { background: rgba(236,72,153,0.1); color: #ec4899; }

  @media (max-width: 768px) {
    .map-location-info {
      flex-direction: column;
      align-items: flex-start;
    }
    .safe-places-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
let map, userMarker;
let mapMarkers = [];
const defaultLocation = [24.8607, 67.0011];
const nearbyPlacesApiUrl = document.getElementById('nearbyPlacesPage').dataset.apiUrl;
let activePlacesList = [];
let currentFilteredPlaces = [];
let activeEmergencyPlace = null;

document.addEventListener('DOMContentLoaded', () => {
  initMap();

  document.getElementById('refreshNearbyPlaces').addEventListener('click', updateUserLocation);
  document.getElementById('centerMapBtn').addEventListener('click', () => {
    if (userMarker) {
      map.setView(userMarker.getLatLng(), 15);
    }
  });

  const toggleMapBtn = document.getElementById('toggleMapView');
  const mapContainer = document.getElementById('mapContainer');
  toggleMapBtn.addEventListener('click', () => {
    if (mapContainer.style.display === 'none') {
      mapContainer.style.display = 'block';
      toggleMapBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Map';
      setTimeout(() => map.invalidateSize(), 150);
    } else {
      mapContainer.style.display = 'none';
      toggleMapBtn.innerHTML = '<i class="fa-solid fa-map"></i> View Map';
    }
  });

  setupFilters();
});

function initMap() {
  map = L.map('leafletMap', { zoomControl: false }).setView(defaultLocation, 12);
  const primaryTiles = L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=<?= LOCATIONIQ_API_KEY ?>', {
    attribution: '© LocationIQ © OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);

  let tileErrorCount = 0;
  let fallbackActive = false;
  primaryTiles.on('tileerror', (error) => {
    tileErrorCount++;
    console.error('[Defenza] LocationIQ tile failed to load:', error);
    if (!fallbackActive && tileErrorCount >= 3) {
      fallbackActive = true;
      console.warn('[Defenza] Switching to fallback OpenStreetMap tiles after repeated LocationIQ tile failures.');
      map.removeLayer(primaryTiles);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(map);
      if (typeof showToast === 'function') {
        showToast('Map is using a backup tile source right now.', 'warning');
      }
    }
  });

  L.control.zoom({ position: 'topright' }).addTo(map);
  updateUserLocation();
}

function updateUserLocation() {
  const statusLabel = document.getElementById('mapStatus');
  statusLabel.innerText = 'Accessing live location GPS...';
  statusLabel.classList.add('show');

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const liveCoords = [pos.coords.latitude, pos.coords.longitude];
        setMarkerAndCenter(liveCoords, 'Your Live Location');
        statusLabel.innerText = 'GPS Connected • Live Coordinates';
        setTimeout(() => statusLabel.classList.remove('show'), 2000);
        fetchNearbyPlaces(liveCoords[0], liveCoords[1]);
      },
      () => {
        setMarkerAndCenter(defaultLocation, 'Karachi Centre (Fallback)');
        statusLabel.innerText = 'GPS Access Denied. Showing Karachi Centre.';
        setTimeout(() => statusLabel.classList.remove('show'), 3000);
        fetchNearbyPlaces(defaultLocation[0], defaultLocation[1]);
      },
      { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 }
    );
  } else {
    setMarkerAndCenter(defaultLocation, 'Karachi Centre');
    fetchNearbyPlaces(defaultLocation[0], defaultLocation[1]);
  }
}

function setMarkerAndCenter(coords, label) {
  if (userMarker) map.removeLayer(userMarker);

  const pulseIcon = L.divIcon({
    className: 'live-marker',
    html: `<div style="width:16px;height:16px;background:#8b5cf6;border:3px solid #fff;border-radius:50%;box-shadow:0 0 10px #a78bfa;animation: pulse 1.5s infinite;"></div>`,
    iconSize: [16, 16]
  });

  userMarker = L.marker(coords, { icon: pulseIcon }).addTo(map).bindPopup(`<b>${label}</b>`).openPopup();
  map.setView(coords, 14);
}

async function fetchNearbyPlaces(userLat, userLng) {
  try {
    const response = await fetch(`${nearbyPlacesApiUrl}?lat=${encodeURIComponent(userLat)}&lng=${encodeURIComponent(userLng)}`);
    if (!response.ok) {
      throw new Error('Nearby places request failed');
    }

    const data = await response.json();
    const places = Array.isArray(data && data.places) ? data.places : [];

    activePlacesList = places.map(place => ({
      ...place,
      type: place.category || place.type || 'helpline',
      lat: place.latitude ?? place.lat,
      lng: place.longitude ?? place.lng,
      addr: place.address || place.addr || 'Address unavailable',
      name: place.name || 'Safe Place',
      phone: place.phone || '',
      distance: place.distance ?? '—'
    })).sort((a, b) => {
      const distanceA = Number(a.distance) || 9999;
      const distanceB = Number(b.distance) || 9999;
      return distanceA - distanceB;
    });

    if (!activePlacesList.length) {
      showToast('Unable to load nearby places right now.', 'error');
      renderView('all');
      return;
    }

    renderView('all');
  } catch (error) {
    console.error('[Defenza] Failed to load nearby places:', error);
    activePlacesList = [];
    showToast('Unable to load nearby places right now.', 'error');
    renderView('all');
  }
}

function renderView(filterType) {
  const listContainer = document.getElementById('nearbyPlacesList');
  listContainer.innerHTML = '';

  mapMarkers.forEach(marker => map.removeLayer(marker));
  mapMarkers = [];

  let filtered = activePlacesList;
  if (filterType !== 'all') {
    filtered = activePlacesList.filter(p => p.type === filterType);
  }
  currentFilteredPlaces = filtered;

  document.getElementById('statPolice').innerText = activePlacesList.filter(p => p.type === 'police').length;
  document.getElementById('statHospital').innerText = activePlacesList.filter(p => p.type === 'hospital').length;
  document.getElementById('statHelpline').innerText = activePlacesList.filter(p => p.type === 'helpline').length;

  if (!filtered.length) {
    listContainer.innerHTML = `
      <div style="grid-column:1 / -1; padding:2rem; border:1px dashed #e5e7eb; border-radius:1rem; background:#f9fafb; color:#6b7280; text-align:center;">
        <i class="fa-solid fa-location-dot" style="font-size:1.2rem; margin-bottom:.5rem; display:block;"></i>
        No nearby safe places available right now.
      </div>
    `;
    return;
  }

  filtered.forEach(place => {
    let themeColor = '#2563eb';
    let iconClass = 'fa-shield';
    if (place.type === 'hospital') { themeColor = '#0ea5e9'; iconClass = 'fa-hospital'; }
    if (place.type === 'helpline') { themeColor = '#ec4899'; iconClass = 'fa-phone'; }

    const markerIcon = L.divIcon({
      className: 'safe-zone-pin',
      html: `<div style="background:${themeColor}; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:0 3px 6px rgba(0,0,0,0.16); border: 2px solid #fff;"><i class="fa-solid ${iconClass}" style="font-size:0.8rem;"></i></div>`,
      iconSize: [36, 36]
    });

    const m = L.marker([place.lat, place.lng], { icon: markerIcon })
      .addTo(map)
      .bindPopup(`<b>${place.name}</b><br>${place.addr}<br><a href="tel:${place.phone}" style="color:var(--purple-600); font-weight:700;">Call: ${place.phone}</a>`);
    m.placeId = place.id;
    mapMarkers.push(m);

    const card = document.createElement('div');
    card.className = 'safe-place-card';
    card.innerHTML = `
      <div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span class="place-type-badge badge-${place.type}">
            <i class="fa-solid ${iconClass}"></i> ${place.type}
          </span>
          <span style="font-size:0.75rem; color:#9ca3af; font-weight:700;"><i class="fa-solid fa-location-arrow"></i> ${place.distance} km away</span>
        </div>
        <h4 style="font-size:1.05rem; font-weight:900; color:#111827; margin:0.25rem 0;">${place.name}</h4>
        <p style="font-size:0.8rem; color:#6b7280; margin:0 0 1rem 0;"><i class="fa-solid fa-map-pin"></i> ${place.addr}</p>
      </div>
      <div style="display:flex; gap:0.5rem; width:100%;">
        <button type="button" class="call-emergency-btn btn-defenza btn-secondary" style="flex:1; padding:0.45rem; font-size:0.8rem; text-align:center; display:inline-flex; align-items:center; justify-content:center; gap:0.35rem;">
          <i class="fa-solid fa-phone"></i> Call Emergency
        </button>
        <button type="button" class="view-on-map-btn btn-defenza btn-primary" style="flex:1; padding:0.45rem; font-size:0.8rem; display:inline-flex; align-items:center; justify-content:center; gap:0.35rem;">
          <i class="fa-solid fa-magnifying-glass-location"></i> View on Map
        </button>
      </div>
    `;

    const callBtn = card.querySelector('.call-emergency-btn');
    const mapBtn = card.querySelector('.view-on-map-btn');
    callBtn.addEventListener('click', () => openEmergencyCallModal(place));
    mapBtn.addEventListener('click', () => zoomToFacility(place.id));

    listContainer.appendChild(card);
  });
}

function getPlaceTypeLabel(type) {
  if (type === 'police') return 'Police Station';
  if (type === 'hospital') return 'Hospital';
  return 'Women Helpline Center';
}

function openEmergencyCallModal(place) {
  activeEmergencyPlace = place;
  document.getElementById('emergencyCallTitle').textContent = `🚨 Calling ${place.category_label || getPlaceTypeLabel(place.type || place.category)}...`;
  document.getElementById('emergencyCallName').textContent = place.name;
  document.getElementById('emergencyCallNumber').textContent = place.phone || 'Unavailable';
  openModal('emergencyCallModal');
}

function copyEmergencyNumber() {
  const number = (activeEmergencyPlace && activeEmergencyPlace.phone) || document.getElementById('emergencyCallNumber').textContent || '';
  if (!number) {
    if (typeof showToast === 'function') showToast('No phone number available.', 'warning');
    return;
  }

  navigator.clipboard.writeText(number).then(() => {
    if (typeof showToast === 'function') showToast('Number copied to clipboard.', 'success');
  }).catch(() => {
    if (typeof showToast === 'function') showToast('Could not copy number.', 'error');
  });
}

function zoomToFacility(placeId) {
  const place = activePlacesList.find(p => p.id === placeId) || currentFilteredPlaces.find(p => p.id === placeId);
  if (!place) return;

  const mapContainer = document.getElementById('mapContainer');
  const toggleMapBtn = document.getElementById('toggleMapView');
  const wasHidden = mapContainer && mapContainer.style.display === 'none';

  if (wasHidden) {
    mapContainer.style.display = 'block';
    if (toggleMapBtn) {
      toggleMapBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Map';
    }
  }

  if (map && typeof map.invalidateSize === 'function') {
    map.invalidateSize();
  }

  map.setView([place.lat, place.lng], 16);
  const marker = mapMarkers.find(m => m.placeId === placeId);
  if (marker) marker.openPopup();

  requestAnimationFrame(() => {
    const topbarOffset = 90;
    if (!mapContainer) return;

    window.scrollTo({
      top: mapContainer.getBoundingClientRect().top + window.scrollY - topbarOffset,
      behavior: 'smooth'
    });
  });
}

function setupFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderView(btn.getAttribute('data-filter'));
    });
  });
}
</script>

<?php dashClose(); dashFoot(); ?>
