<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();
$contacts = getUserContacts();
$contactCount = count($contacts);

dashHead('Live Location');
dashOpen('Live Location Tracking', 'Live Location');
?>

<style>
@keyframes radar-pulse {
  0% { transform: scale(0.9); opacity: 0.9; }
  50% { opacity: 0.4; }
  100% { transform: scale(2.4); opacity: 0; }
}
@keyframes pulse-dot {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(74, 222, 128, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
}
.live-badge-glow {
  box-shadow: 0 0 12px rgba(22, 163, 74, 0.25);
}
.map-glow-pin {
  width: 14px;
  height: 14px;
  background: var(--purple-600);
  border: 3px solid #fff;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.8);
  z-index: 5;
}
.map-radar-ring {
  width: 50px;
  height: 50px;
  border: 3px solid var(--purple-400);
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation: radar-pulse 2s infinite ease-out;
  z-index: 4;
  pointer-events: none;
}
.interactive-hover-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.interactive-hover-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  background: var(--purple-50) !important;
}
.live-map-shell {
  position: relative;
  min-height: 360px;
  overflow: hidden;
}
#liveLocationMap {
  width: 100%;
  height: 100%;
  min-height: 360px;
}
@media (max-width: 900px) {
  #liveLocationPage { grid-template-columns: 1fr !important; }
}
.map-overlay-card {
  position: absolute;
  top: 1rem;
  left: 1rem;
  z-index: 30;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(168,85,247,0.14);
  border-radius: var(--r-md);
  padding: 0.85rem 1rem;
  box-shadow: 0 18px 32px rgba(15,23,42,0.08);
}
.map-action-group {
  position: absolute;
  right: 1rem;
  top: 1rem;
  z-index: 30;
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  align-items: center;
}
.map-action-btn,
.map-action-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--r-md);
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255,255,255,0.92);
  color: #1e293b;
  font-size: 0.88rem;
  padding: 0.68rem 0.85rem;
  cursor: pointer;
  text-decoration: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.map-action-btn:hover,
.map-action-link:hover {
  transform: translateY(-1px);
  background: #fafafa;
  box-shadow: 0 10px 18px rgba(15,23,42,0.08);
}
.map-action-btn i {
  margin-right: 0.35rem;
}
.defenza-live-marker {
  position: relative;
  width: 28px;
  height: 28px;
}
.interactive-map-container {
  position: relative;
  margin-bottom: 1rem;
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
.map-control-row {
  display: flex;
  justify-content: space-between;
  gap: .75rem;
  flex-wrap: wrap;
  margin-bottom: .75rem;
}
.map-control-row .btn-defenza {
  min-width: 120px;
}
.defenza-live-marker-core {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: #8b5cf6;
  box-shadow: 0 0 0 4px rgba(167,139,250,0.25);
  position: absolute;
  top: 0;
  left: 0;
}
.defenza-live-marker-glow {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  box-shadow: 0 0 18px rgba(139,92,246,0.45);
  animation: pulse-dot 2s infinite;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<div id="liveLocationPage" data-api-url="<?= APP_URL ?>/api/live-location.php" style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

  <!-- Map section -->
  <div>
    <div class="card-defenza no-hover" style="padding:0;overflow:hidden;border:1px solid rgba(255,255,255,0.4);box-shadow:var(--shadow-md);background:rgba(255,255,255,.7);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.4rem;border-bottom:1px solid rgba(168,85,247,.08);background:rgba(255,255,255,.75);">
        <div>
          <div style="font-weight:900;color:#111827;font-size:.95rem;letter-spacing:-0.01em;">Live Location Tracking</div>
          <div style="display:flex;align-items:center;gap:.45rem;margin-top:.25rem;">
            <span style="width:8px;height:8px;border-radius:50%;background:#4ade80;display:inline-block;animation:pulse-dot 2s infinite;"></span>
            <span id="liveStatusText" style="font-size:.75rem;color:#16a34a;font-weight:700;">Active — updating every 10s</span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
          <button onclick="refreshLocation()" id="refreshBtn" style="width:38px;height:38px;border-radius:var(--r-md);background:var(--purple-50);border:1px solid var(--purple-100);cursor:pointer;color:var(--purple-600);display:flex;align-items:center;justify-content:center;transition:all .2s;box-shadow:var(--shadow-xs);">
            <i class="fa-solid fa-arrows-rotate"></i>
          </button>
          <span class="live-badge-glow" style="display:flex;align-items:center;gap:.375rem;background:#ecfdf5;color:#16a34a;padding:.45rem 1rem;border-radius:var(--r-full);font-size:.72rem;font-weight:700;border:1px solid #a7f3d0;">
            <i class="fa-solid fa-wifi" style="animation:pulse-dot 1.8s infinite;"></i> Live
          </span>
        </div>
      </div>

      <!-- Map -->
      <div class="live-map-shell">
        <div id="liveLocationMap"></div>
        <div class="map-overlay-card">
          <div style="font-size:.78rem;font-weight:800;color:#111827;display:flex;align-items:center;gap:.45rem;">
            <i class="fa-solid fa-map-location-dot" style="color:var(--purple-600);"></i>
            Live map view
          </div>
          <div id="locationLabel" style="font-size:.72rem;color:#6b7280;margin-top:.45rem;font-weight:600;">Getting your location…</div>
          <div style="font-size:.68rem;color:#6b7280;margin-top:.2rem;">High contrast tiles, smooth tracking, and smart centering.</div>
        </div>
        <div class="map-action-group">
          <button type="button" id="zoomInBtn" class="map-action-btn"><i class="fa-solid fa-plus"></i> Zoom</button>
          <button type="button" id="zoomOutBtn" class="map-action-btn"><i class="fa-solid fa-minus"></i></button>
          <button type="button" id="centerMapBtn" class="map-action-btn"><i class="fa-solid fa-location-crosshairs"></i> Center</button>
          <button type="button" id="openGoogleMapsBtn" class="map-action-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Google Maps</button>
        </div>
      </div>

      <!-- Coordinates row -->
      <div style="padding:1.25rem;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;background:rgba(255,255,255,.7);">
        <?php foreach ([
          ['Latitude',     '33.6844°N', 'latVal'],
          ['Longitude',    '73.0479°E', 'lonVal'],
          ['Accuracy',     '12 m',      'accVal'],
          ['Last Updated', 'Just now',  'timeVal'],
        ] as [$label, $value, $id]): ?>
          <div style="background:var(--purple-50);border-radius:var(--r-md);padding:.85rem;text-align:center;border:1px solid rgba(168,85,247,0.08);transition:all .2s;">
            <div style="font-size:.68rem;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.08em;"><?= $label ?></div>
            <div id="<?= $id ?>" style="font-size:.875rem;font-weight:900;color:var(--purple-700);margin-top:.35rem;"><?= $value ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar info -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Location card -->
    <div class="card-defenza no-hover" style="padding:1.5rem;border:1px solid rgba(255,255,255,0.4);box-shadow:var(--shadow-sm);background:rgba(255,255,255,.72);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
      <h3 style="font-weight:900;font-size:.9rem;color:#111827;margin-bottom:1.15rem;display:flex;align-items:center;gap:.5rem;">
        <i class="fa-solid fa-location-crosshairs" style="color:var(--purple-500);"></i>Current Address
      </h3>
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <div>
          <div style="font-size:.68rem;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Address</div>
          <div id="sidebarAddress" style="font-weight:800;color:#111827;font-size:.9rem;margin-top:.35rem;line-height:1.4;">Getting your address…</div>
          <div id="sidebarAddressDetail" style="font-size:.75rem;color:#9ca3af;margin-top:.15rem;">Waiting for GPS</div>
        </div>
        <div style="display:flex;align-items:center;gap:.625rem;background:#ecfdf5;border-radius:var(--r-md);padding:.75rem 1rem;border:1px solid #a7f3d0;box-shadow:var(--shadow-xs);">
          <span style="width:9px;height:9px;border-radius:50%;background:#4ade80;display:inline-block;animation:pulse-dot 2s infinite;flex-shrink:0;"></span>
          <span style="font-size:.8rem;font-weight:800;color:#065f46;">Location Sharing Active</span>
        </div>
        <div style="font-size:.78rem;color:#6b7280;display:flex;align-items:center;gap:.45rem;font-weight:500;">
          <i class="fa-solid fa-users" style="color:var(--purple-500);"></i>
          Sharing with <strong style="color:#111827;font-weight:800;"><?= $contactCount ?> emergency contact<?= $contactCount === 1 ? '' : 's' ?></strong>
        </div>
      </div>
    </div>

    <div class="card-defenza no-hover" style="padding:1.5rem;border:1px solid rgba(255,255,255,0.4);box-shadow:var(--shadow-sm);background:rgba(255,255,255,.72);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
        <span style="width:2.5rem;height:2.5rem;border-radius:1rem;background:var(--purple-50);display:flex;align-items:center;justify-content:center;color:var(--purple-700);font-size:1rem;">
          <i class="fa-solid fa-list-check"></i>
        </span>
        <h3 style="font-weight:900;font-size:.95rem;color:#111827;margin:0;">Live Tracking Essentials</h3>
      </div>
      <div style="display:flex;flex-direction:column;gap:1rem;font-size:.9rem;color:#374151;line-height:1.6;">
        <div style="display:flex;gap:.75rem;align-items:flex-start;">
          <span style="font-size:1.1rem;line-height:1;">📍</span>
          <div><strong style="color:#111827;font-weight:800;">Precise Pinning:</strong> Keep Location/GPS set to "High Accuracy" for exact coordinates.</div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-start;">
          <span style="font-size:1.1rem;line-height:1;">🔋</span>
          <div><strong style="color:#111827;font-weight:800;">Battery Optimization:</strong> Turn off power-saver mode for <strong>Defenza</strong> so location updates run continuously in the background.</div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-start;">
          <span style="font-size:1.1rem;line-height:1;">🔒</span>
          <div><strong style="color:#111827;font-weight:800;">End-to-End Privacy:</strong> Your live coordinates are encrypted and accessible only by your trusted emergency contacts.</div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-start;">
          <span style="font-size:1.1rem;line-height:1;">📡</span>
          <div><strong style="color:#111827;font-weight:800;">Offline Behavior:</strong> If internet signal drops, the system caches your last known location automatically.</div>
        </div>
      </div>
    </div>

    <!-- Share control actions -->
    <div style="display:flex;flex-direction:column;gap:.75rem;">
      <button onclick="shareLocation(event)" class="btn-defenza btn-primary btn-full" style="padding:1rem;font-weight:800;font-size:.875rem;box-shadow:0 6px 20px rgba(168,85,247,.35);">
        <i class="fa-solid fa-location-arrow" style="margin-right:.35rem;"></i> Share My Location
      </button>

      <!-- Pause sharing removed -->
    </div>
  </div>
</div>

<script>
const livePageMap = {
  map: null,
  userMarker: null,
  safeMarkers: [],
  defaultLocation: [24.8607, 67.0011],
  safePlaces: [
    { type: 'police', name: 'Gulshan-e-Iqbal Police Station', phone: '021-99243864', lat: 24.9180, lng: 67.0970, addr: 'Block 6 Gulshan-e-Iqbal' },
    { type: 'police', name: 'Bahadurabad Police Station', phone: '021-99230554', lat: 24.8845, lng: 67.0690, addr: 'Bahadur Yar Jang CHS' },
    { type: 'police', name: 'Clifton Police Station', phone: '021-99250552', lat: 24.8215, lng: 67.0345, addr: 'Block 5 Clifton' },
    { type: 'hospital', name: 'Liaquat National Hospital', phone: '021-111-456-456', lat: 24.8940, lng: 67.0720, addr: 'National Stadium Road' },
    { type: 'hospital', name: 'Aga Khan University Hospital', phone: '021-111-911-911', lat: 24.8922, lng: 67.0750, addr: 'National Stadium Road' },
    { type: 'helpline', name: 'Sindh Women Protection Cell', phone: '1094', lat: 24.8210, lng: 67.0340, addr: 'Protection Block, Karachi' },
    { type: 'helpline', name: 'Edhi Rescue Centre', phone: '115', lat: 24.8600, lng: 67.0011, addr: 'Sohrab Goth HQ' }
  ],

  init() {
    if (typeof L === 'undefined') return;
    const container = document.getElementById('liveLocationMap');
    if (!container) return;

    this.map = L.map('liveLocationMap', { zoomControl: false }).setView(this.defaultLocation, 12);
    const primaryTiles = L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=<?= LOCATIONIQ_API_KEY ?>', {
      attribution: '© LocationIQ © OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(this.map);

    let tileErrorCount = 0;
    let fallbackActive = false;
    primaryTiles.on('tileerror', (error) => {
      tileErrorCount++;
      console.error('[Defenza] LocationIQ tile failed to load:', error);
      if (!fallbackActive && tileErrorCount >= 3) {
        fallbackActive = true;
        console.warn('[Defenza] Switching to fallback OpenStreetMap tiles after repeated LocationIQ tile failures.');
        this.map.removeLayer(primaryTiles);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors',
          maxZoom: 19
        }).addTo(this.map);
        if (typeof showToast === 'function') {
          showToast('Map is using a backup tile source right now.', 'warning');
        }
      }
    });

    L.control.zoom({ position: 'topright' }).addTo(this.map);
    this.addSafePlaceMarkers();
    window.addEventListener('resize', () => {
      if (this.map) {
        setTimeout(() => this.map.invalidateSize(), 0);
      }
    });
    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
    this.refreshLocation();
  },

  addSafePlaceMarkers() {
    if (!this.map) return;
    this.safeMarkers.forEach(marker => this.map.removeLayer(marker));
    this.safeMarkers = [];

    this.safePlaces.forEach(place => {
      const color = place.type === 'hospital' ? '#0ea5e9' : place.type === 'helpline' ? '#ec4899' : '#2563eb';
      const iconClass = place.type === 'hospital' ? 'fa-hospital' : place.type === 'helpline' ? 'fa-phone' : 'fa-shield';

      const markerIcon = L.divIcon({
        className: 'safe-zone-pin',
        html: `<div style="background:${color}; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:0 3px 6px rgba(0,0,0,0.16); border:2px solid #fff;"><i class="fa-solid ${iconClass}" style="font-size:0.8rem;"></i></div>`,
        iconSize: [34, 34]
      });

      const marker = L.marker([place.lat, place.lng], { icon: markerIcon })
        .addTo(this.map)
        .bindPopup(`<strong>${place.name}</strong><br>${place.addr}<br><a href="tel:${place.phone}" style="color:#7c3aed;font-weight:700;">Call ${place.phone}</a>`);

      this.safeMarkers.push(marker);
    });
  },

  setUserLocation(lat, lon, acc, address) {
    if (!this.map) return;
    if (this.userMarker) {
      this.map.removeLayer(this.userMarker);
    }

    const userIcon = L.divIcon({
      className: 'live-user-marker',
      html: '<div style="width:18px;height:18px;background:#8b5cf6;border:3px solid #fff;border-radius:50%;box-shadow:0 0 12px rgba(139,92,246,0.45);"></div>',
      iconSize: [18, 18]
    });

    this.userMarker = L.marker([lat, lon], { icon: userIcon })
      .addTo(this.map)
      .bindPopup(`<strong>Your Location</strong><br>${address}`);

    this.map.setView([lat, lon], 14);
    this.lastPosition = { lat, lon, acc, address };
    this.updateUI(lat, lon, acc, address);
  },

  updateUI(lat, lon, acc, address) {
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    setText('latVal', `${Math.abs(lat).toFixed(4)}°${lat >= 0 ? 'N' : 'S'}`);
    setText('lonVal', `${Math.abs(lon).toFixed(4)}°${lon >= 0 ? 'E' : 'W'}`);
    setText('accVal', `${Math.round(acc)} m`);
    setText('timeVal', new Date().toLocaleTimeString());
    setText('locationLabel', address);
    setText('sidebarAddress', address);
    setText('sidebarAddressDetail', `${Math.abs(lat).toFixed(4)}° ${lat >= 0 ? 'N' : 'S'}, ${Math.abs(lon).toFixed(4)}° ${lon >= 0 ? 'E' : 'W'}`);
  },

  refreshLocation() {
    if (!navigator.geolocation) {
      if (typeof showToast === 'function') showToast('Geolocation unsupported in your browser.', 'error');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        this.setUserLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy || 0, 'Current GPS Coordinates');
      },
      (err) => {
        if (typeof showToast === 'function') {
          const message = err?.code === 1 ? 'Allow location access to show your position.' : 'Unable to fetch your location right now.';
          showToast(message, 'warning');
        }
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  },

  getCurrentPosition() {
    return this.lastPosition ? [this.lastPosition.lat, this.lastPosition.lon] : null;
  },

  centerMap() {
    const position = this.getCurrentPosition() || this.defaultLocation;
    if (this.map) {
      this.map.setView(position, Math.max(this.map.getZoom(), 14));
    }
  }
};

function refreshLocation() {
  livePageMap.refreshLocation();
}

function setupLiveLocationMapControls() {
  const zoomInBtn = document.getElementById('zoomInBtn');
  const zoomOutBtn = document.getElementById('zoomOutBtn');
  const centerMapBtn = document.getElementById('centerMapBtn');
  const openGoogleMapsBtn = document.getElementById('openGoogleMapsBtn');

  if (zoomInBtn) zoomInBtn.addEventListener('click', () => livePageMap.map?.zoomIn());
  if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => livePageMap.map?.zoomOut());
  if (centerMapBtn) centerMapBtn.addEventListener('click', () => livePageMap.centerMap());
  if (openGoogleMapsBtn) {
    openGoogleMapsBtn.addEventListener('click', () => {
      const pos = livePageMap.getCurrentPosition();
      if (!pos) return;
      window.open(`https://maps.google.com?q=${pos[0]},${pos[1]}`, '_blank');
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  livePageMap.init();
  setupLiveLocationMapControls();
});
</script>

<?php dashClose(); dashFoot(); ?>
