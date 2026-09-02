<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();
$userId = (int) ($user['id'] ?? 0);

$recentAlerts = [];
$totalSOS = 0;
$totalContacts = 0;
$monthAlerts = 0;
$activeEmergency = false;
$activeSosAlert = null;
$safetyStatusLabel = 'All Clear / Safe';
$safetyStatusColor = '#10b981';
$heroBadgeText = 'All Clear / Safe';
$heroBadgeColor = '#4ade80';

if ($userId) {
  $dashboardStats = getDashboardStats($userId);
  $recentAlerts = $dashboardStats['recentAlerts'] ?? [];
  $totalSOS = (int) ($dashboardStats['totalSOS'] ?? 0);
  $totalContacts = (int) ($dashboardStats['totalContacts'] ?? 0);
  $monthAlerts = (int) ($dashboardStats['monthAlerts'] ?? 0);
  $activeEmergency = (bool) ($dashboardStats['activeEmergency'] ?? false);
  $activeSosAlert = $dashboardStats['activeSosAlert'] ?? null;
}

if ($activeEmergency) {
  $safetyStatusLabel = 'Danger / Emergency Active';
  $safetyStatusColor = '#ef4444';
  $heroBadgeText = 'Emergency Active';
  $heroBadgeColor = '#ef4444';
}

$totalReports = 2;

dashHead('Dashboard');
dashOpen('Dashboard', 'Dashboard');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
@keyframes dashboard-float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-6px); }
}
@keyframes pulse-signal {
  0% { transform: scale(1); opacity: .85; }
  50% { transform: scale(1.12); opacity: 1; }
  100% { transform: scale(1.3); opacity: 0; }
}
@keyframes hero-glow {
  0%, 100% { transform: translate3d(0,0,0) scale(1); opacity: .55; }
  50% { transform: translate3d(10px,-8px,0) scale(1.06); opacity: .82; }
}
.dashboard-hero {
  background: linear-gradient(135deg, #26125e 0%, #4338ca 38%, #312e81 100%);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: var(--r-lg);
  padding: 1.5rem 1.75rem;
  margin-bottom: 1.5rem;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  box-shadow: 0 24px 60px rgba(76, 29, 149, .25);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}
.dashboard-hero > div {
  min-width: 220px;
}
.dashboard-hero::before,
.dashboard-hero::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.dashboard-hero::before {
  width: 220px; height: 220px; top: -70px; right: -70px; background: rgba(255,255,255,.12); animation: hero-glow 5s ease-in-out infinite;
}
.dashboard-hero::after {
  width: 150px; height: 150px; bottom: -45px; left: -45px; background: rgba(192,132,252,.18);
}
.hero-badge {
  display: flex; align-items: center; gap: .55rem;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.22);
  border-radius: var(--r-full);
  padding: .6rem 1rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  box-shadow: 0 8px 24px rgba(17,24,39,.12);
}
.hero-badge::before {
  content: '';
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--badge-color, #4ade80); box-shadow: 0 0 0 0 rgba(74, 222, 128, .6);
  animation: pulse-signal 1.7s infinite;
}
.stat-card {
  transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s ease, border-color .25s ease;
  border: 1px solid rgba(255,255,255,.75);
}
.stat-card:hover {
  transform: translateY(-6px) scale(1.01);
  box-shadow: 0 18px 40px rgba(15,23,42,.12);
  border-color: rgba(168,85,247,.2);
}
.stat-card:hover .stat-icon {
  transform: scale(1.08) rotate(-6deg);
  box-shadow: 0 12px 24px rgba(167,139,250,.25);
}
.stat-icon {
  position: relative;
  border-radius: 50%;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.45), 0 10px 20px rgba(15,23,42,.12);
}
.stat-icon::before {
  content: '';
  position: absolute;
  inset: 6px;
  border-radius: inherit;
  border: 1px solid rgba(255,255,255,.35);
}
.sos-shell {
  padding: 1rem 1rem 1.05rem;
  min-height: auto;
  height: auto;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  text-align: center;
  gap: .7rem;
  background: linear-gradient(145deg, #ffffff 0%, #f8faff 100%);
  border: 1px solid rgba(248,113,113,.08);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
  position: relative;
  overflow: hidden;
}
.sos-shell::before {
  content: '';
  position: absolute; inset: -20% auto auto -10%; width: 180px; height: 180px; border-radius: 50%; background: radial-gradient(circle, rgba(251,113,133,.13), transparent 70%);
}
.sos-shell .sos-intro {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .35rem;
}
.sos-shell .sos-outer-rings {
  margin-bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .75rem;
}
.sos-shell .sos-ring {
  border-color: rgba(248,113,113,.2);
  backdrop-filter: blur(2px);
}
.sos-shell .sos-btn-main {
  box-shadow: 0 18px 40px rgba(239,68,68,.28), 0 0 0 10px rgba(239,68,68,.08);
}
.sos-feature-list {
  width: 100%;
  display: grid;
  gap: .5rem;
  list-style: none;
  padding: 0;
  margin: 0;
  text-align: left;
}
.sos-list-item {
  display:flex; align-items:flex-start; gap:.55rem; font-size:.72rem; color:#64748b; padding:.6rem .7rem; border:1px solid rgba(148,163,184,.12); background: rgba(255,255,255,.78); border-radius: var(--r-md); backdrop-filter: blur(8px);
  margin: 0;
}
.map-shell {
  padding:0; overflow:hidden; border:1px solid rgba(148,163,184,.16); background: linear-gradient(135deg, rgba(255,255,255,.94), rgba(243,247,255,.94)); box-shadow: var(--shadow-sm);
}
.map-surface {
  position: relative; height: 165px; overflow: hidden;
  background: linear-gradient(135deg, #eef8ff 0%, #effdf7 48%, #f5f3ff 100%);
}
.map-surface::before {
  content: '';
  position: absolute; inset: 0;
  background-image: linear-gradient(rgba(99,102,241,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,.08) 1px, transparent 1px);
  background-size: 42px 42px;
}
.activity-row {
  display:flex; align-items:center; gap:.75rem; padding:.65rem .75rem; border-radius:var(--r-md); transition: background .16s ease, transform .16s ease, border-color .16s ease; border:1px solid transparent;
}
.activity-row:hover {
  background: linear-gradient(90deg, rgba(168,85,247,.08) 0%, rgba(255,255,255,0) 100%);
  transform: translateX(2px);
  border-color: rgba(168,85,247,.12);
}
</style>

<!-- Welcome banner -->
<div class="dashboard-hero">
  <div style="position:relative;z-index:1;max-width:640px;">
    <div style="display:inline-flex;align-items:center;gap:.5rem;padding:.35rem .7rem;border:1px solid rgba(255,255,255,.2);border-radius:var(--r-full);background:rgba(255,255,255,.12);margin-bottom:.7rem;backdrop-filter:blur(8px);">
      <i class="fa-solid fa-shield-heart" style="color:#c4b5fd;font-size:.8rem;"></i>
      <span style="color:#f5f3ff;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Live protection active</span>
    </div>
    <h2 style="font-size:1.25rem;font-weight:900;color:#fff;margin-bottom:.35rem;letter-spacing:-.02em;">
      Welcome back, <?= !empty($user['full_name']) ? e($user['full_name']) : 'User' ?>!
    </h2>
    <p style="color:rgba(237,233,254,.86);font-size:.84rem;line-height:1.6;max-width:560px;">Your safety status is active, the network is healthy, and every layer of your protection is ready to respond.</p>
  </div>
  <div class="hero-badge" style="--badge-color: <?= e($heroBadgeColor) ?>; position:relative;z-index:1;">
    <span style="color:#fff;font-size:.8rem;font-weight:800;"><?= e($heroBadgeText) ?></span>
  </div>
</div>

<!-- Stat cards -->
<div class="stats-grid-4" style="margin-bottom:1.5rem;">
  <div class="card-defenza stat-card stagger-child">
    <div class="stat-icon pink" style="background:linear-gradient(135deg,#ec4899,#f43f5e);"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div>
      <div class="stat-label">Total SOS Alerts</div>
      <div class="stat-value"><?= $totalSOS ?></div>
      <div class="stat-trend"><i class="fa-solid fa-arrow-trend-up"></i> +<?= $monthAlerts ?> this month</div>
    </div>
  </div>
  <div class="card-defenza stat-card stagger-child">
    <div class="stat-icon purple" style="background:linear-gradient(135deg,#a855f7,#7e22ce);"><i class="fa-solid fa-users"></i></div>
    <div>
      <div class="stat-label">Emergency Contacts</div>
      <div class="stat-value"><?= $totalContacts ?></div>
      <div class="stat-trend"><i class="fa-solid fa-circle-check"></i> All verified</div>
    </div>
  </div>
  <div class="card-defenza stat-card stagger-child">
    <div class="stat-icon blue" style="background:linear-gradient(135deg,#60a5fa,#4f46e5);"><i class="fa-solid fa-bell"></i></div>
    <div>
      <div class="stat-label">Alerts This Month</div>
      <div class="stat-value"><?= $monthAlerts ?></div>
      <div class="stat-trend"><i class="fa-solid fa-arrow-trend-down"></i> -2 vs last month</div>
    </div>
  </div>
  <div class="card-defenza stat-card stagger-child">
    <div class="stat-icon green" style="background:linear-gradient(135deg,#34d399,#059669);"><i class="fa-solid fa-shield-halved"></i></div>
    <div>
      <div class="stat-label">Safety Status</div>
      <div class="stat-value" style="font-size:1.15rem;color:<?= $safetyStatusColor ?>;"><?= e($safetyStatusLabel) ?></div>
      <div class="stat-trend"><i class="fa-solid fa-circle-check"></i> <?= $activeEmergency ? 'Emergency response active' : 'All clear' ?></div>
    </div>
  </div>
</div>

<!-- Main grid -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;margin-bottom:1.5rem;">

  <!-- SOS quick button -->
  <div class="card-defenza no-hover sos-shell">
    <div class="sos-intro">
      <div style="display:flex;align-items:center;justify-content:center;gap:.6rem;">
        <i class="fa-solid fa-bolt" style="color:#ef4444;font-size:.85rem;"></i>
        <h3 style="font-weight:900;color:#111827;font-size:.95rem;margin:0;">Quick SOS</h3>
      </div>
      <p style="font-size:.74rem;color:#64748b;margin:0;line-height:1.45;">Tap in case of emergency and trigger your protection network instantly.</p>
    </div>

    <div class="sos-outer-rings">
      <div class="sos-ring"></div>
      <div class="sos-ring"></div>
      <div class="sos-ring"></div>
      <a href="<?= APP_URL ?>/pages/sos-alert.php" style="text-decoration:none;">
        <button class="sos-btn-main">
          <span class="sos-label-main">SOS</span>
          <span class="sos-label-sub">TAP TO SEND</span>
        </button>
      </a>
    </div>

    <ul class="sos-feature-list">
      <?php foreach ([
        'Alerts sent to your emergency contacts.',
        'Your live location will be shared.',
        'Help notified immediately.',
      ] as $t): ?>
        <li class="sos-list-item">
          <i class="fa-solid fa-circle-check" style="color:var(--purple-400);margin-top:.15rem;flex-shrink:0;"></i><?= $t ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Right column -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">
    <!-- Map card -->
    <div class="card-defenza no-hover map-shell">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid rgba(168,85,247,.08);background:rgba(255,255,255,.45);backdrop-filter:blur(8px);">
        <div>
          <div style="font-weight:900;color:#111827;font-size:.875rem;">Current Location</div>
          <div style="display:flex;align-items:center;gap:.375rem;margin-top:.2rem;">
            <span style="width:7px;height:7px;border-radius:50%;background:#4ade80;display:inline-block;animation:pulse-dot 2s infinite;"></span>
            <span id="loc-status-text" style="font-size:.72rem;color:#16a34a;font-weight:700;">Fetching Location...</span>
          </div>
        </div>
        <a href="<?= APP_URL ?>/pages/live-location.php" style="font-size:.75rem;color:var(--purple-600);font-weight:700;display:flex;align-items:center;gap:.35rem;text-decoration:none;">
          View Map <i class="fa-solid fa-arrow-right" style="font-size:.65rem;"></i>
        </a>
      </div>
      <div class="map-surface">
        <div id="dashboardMiniMap" style="width:100%;height:100%;"></div>
      </div>
      <div style="padding:.875rem 1.25rem;display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.55);">
        <div>
          <div id="loc-city-name" style="font-weight:800;color:#111827;font-size:.875rem;">Locating...</div>
          <div id="loc-coords-detail" style="font-size:.72rem;color:#9ca3af;margin-top:.15rem;">Requesting device coordinates...</div>
        </div>
        <span style="background:#ecfdf5;color:#065f46;font-size:.7rem;font-weight:700;padding:.3rem .75rem;border-radius:var(--r-full);border:1px solid #a7f3d0;">Live GPS</span>
      </div>
    </div>

    <!-- Active emergency -->
    <?php if ($activeEmergency && $activeSosAlert): ?>
      <div id="activeSosCard" class="card-defenza no-hover" style="padding:1.25rem;border:1px solid rgba(248,113,113,.16);background:linear-gradient(135deg,#fef2f2,#fff);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem;gap:.75rem;">
          <div style="font-weight:900;color:#111827;font-size:.875rem;">Live SOS Event</div>
          <span class="badge-pill badge-amber" style="font-weight:800;font-size:0.7rem;">Active</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:.6rem;font-size:.78rem;color:#4b5563;">
          <div><strong style="color:#111827;">User:</strong> <?= e($activeSosAlert['full_name'] ?? 'User') ?></div>
          <div><strong style="color:#111827;">Email:</strong> <?= e($activeSosAlert['email'] ?? 'Not provided') ?></div>
          <div><strong style="color:#111827;">Location:</strong> <?= e($activeSosAlert['address'] ?: 'Location pending') ?></div>
          <div><strong style="color:#111827;">Coordinates:</strong> <?= e(($activeSosAlert['latitude'] !== null ? number_format((float) $activeSosAlert['latitude'], 6, '.', '') : '—') . ', ' . ($activeSosAlert['longitude'] !== null ? number_format((float) $activeSosAlert['longitude'], 6, '.', '') : '—')) ?></div>
          <div><a href="https://www.google.com/maps?q=<?= rawurlencode((string) ($activeSosAlert['latitude'] ?? '')) ?>,<?= rawurlencode((string) ($activeSosAlert['longitude'] ?? '')) ?>" target="_blank" rel="noreferrer" style="color:var(--purple-600);font-weight:800;text-decoration:none;">Open in Google Maps</a></div>
          <button type="button" id="resolveSosBtn" data-sos-id="<?= (int) $activeSosAlert['id'] ?>" class="btn-defenza btn-primary" style="margin-top:.75rem;">
            <i class="fa-solid fa-circle-check"></i> Mark as Safe / Resolve This Alert
          </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Recent activity -->
    <div class="card-defenza no-hover" style="padding:0;overflow:hidden;border:1px solid rgba(148,163,184,.16);background:rgba(255,255,255,.8);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid rgba(168,85,247,.08);">
        <div style="font-weight:900;color:#111827;font-size:.875rem;">Recent SOS Activity</div>
        <a href="<?= APP_URL ?>/pages/sos-alert.php" style="font-size:.75rem;color:var(--purple-600);font-weight:700;text-decoration:none;">View All</a>
      </div>
      <div style="padding:.45rem .45rem .6rem;">
        <?php if (empty($recentAlerts)): ?>
          <div class="activity-row" style="border:1px dashed rgba(148,163,184,.25);background:rgba(248,250,252,.7);">
            <div style="width:34px;height:34px;border-radius:var(--r-sm);background:linear-gradient(135deg,#e2e8f0,#f8fafc);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-bell-slash" style="color:#94a3b8;font-size:.8rem;"></i>
            </div>
            <div style="font-size:.78rem;color:#64748b;">No recent emergency alerts logged.</div>
          </div>
        <?php else: ?>
          <?php foreach ($recentAlerts as $alert):
            $isResolved = ($alert['status'] ?? 'active') === 'resolved';
            $statusBg   = $isResolved ? '#d1fae5' : '#fef3c7';
            $statusCol  = $isResolved ? '#065f46' : '#92400e';
          ?>
            <div class="activity-row" onmouseover="this.style.background='linear-gradient(90deg, rgba(168,85,247,.08) 0%, rgba(255,255,255,0) 100%)'" onmouseout="this.style.background='transparent'">
              <div style="width:34px;height:34px;border-radius:var(--r-sm);background:linear-gradient(135deg,#fee2e2,#ffe4e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 14px rgba(248,113,113,.12);">
                <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;font-size:.8rem;"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:.83rem;font-weight:700;color:#111827;">SOS Alert — <?= !empty($alert['address']) ? e($alert['address']) : 'Location not recorded' ?></div>
                <div style="font-size:.71rem;color:#9ca3af;margin-top:.1rem;display:flex;align-items:center;gap:.3rem;">
                  <i class="fa-regular fa-clock" style="font-size:.65rem;"></i>
                  <?= date('M d, Y · H:i', strtotime($alert['created_at'])) ?>
                </div>
              </div>
              <span class="badge-pill" style="background:<?= $statusBg ?>;color:<?= $statusCol ?>;"><?= ucfirst($alert['status'] ?? 'active') ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Quick links -->
<div>
  <div style="font-weight:900;color:#111827;font-size:.9rem;margin-bottom:1rem;">Quick Access</div>
  <div class="quick-links-grid" style="display:grid;gap:1rem;">
    <?php foreach ([
      ['fa-triangle-exclamation', 'SOS Alert',      '/pages/sos-alert.php',     'linear-gradient(135deg,#f87171,#ef4444)'],
      ['fa-location-dot',         'Live Location',   '/pages/live-location.php', 'linear-gradient(135deg,#60a5fa,#4f46e5)'],
      ['fa-users',                'Contacts',        '/pages/contacts.php',      'linear-gradient(135deg,#c084fc,#7e22ce)'],
      ['fa-lightbulb',            'Safety Tips',     '/pages/safety-tips.php',   'linear-gradient(135deg,#34d399,#059669)'],
    ] as [$icon, $label, $href, $grad]): ?>
      <a href="<?= APP_URL . $href ?>" style="text-decoration:none;">
        <div class="card-defenza quick-link-card stagger-child">
          <div class="quick-link-icon" style="background:<?= $grad ?>;"><i class="fa-solid <?= $icon ?>"></i></div>
          <span class="quick-link-label"><?= $label ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const statusText = document.getElementById('loc-status-text');
  const cityName = document.getElementById('loc-city-name');
  const coordsDetail = document.getElementById('loc-coords-detail');
  const liveLocationApi = '<?= APP_URL ?>/api/live-location.php';
  const resolveSosBtn = document.getElementById('resolveSosBtn');
  const activeSosCard = document.getElementById('activeSosCard');

  if (resolveSosBtn) {
    resolveSosBtn.addEventListener('click', function () {
      const sosId = resolveSosBtn.getAttribute('data-sos-id');
      if (!sosId) {
        return;
      }

      const originalText = resolveSosBtn.innerHTML;
      resolveSosBtn.disabled = true;
      resolveSosBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resolving...';

      const params = new URLSearchParams();
      params.append('sos_id', sosId);

      fetch('<?= APP_URL ?>/api/resolve_sos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: params.toString(),
        credentials: 'same-origin'
      }).then(function (response) {
        return response.json().catch(function () {
          return null;
        });
      }).then(function (data) {
        if (data && data.success) {
          if (activeSosCard) {
            activeSosCard.remove();
          }
          if (typeof showToast === 'function') {
            showToast('Alert marked as resolved.', 'success');
          }
        } else {
          resolveSosBtn.disabled = false;
          resolveSosBtn.innerHTML = originalText;
          if (typeof showToast === 'function') {
            showToast((data && data.message) ? data.message : 'Unable to resolve alert right now.', 'error');
          }
        }
      }).catch(function () {
        resolveSosBtn.disabled = false;
        resolveSosBtn.innerHTML = originalText;
        if (typeof showToast === 'function') {
          showToast('Unable to resolve alert right now.', 'error');
        }
      });
    });
  }

  if (!navigator.geolocation) {
    statusText.innerText = 'Location Access Disabled';
    statusText.style.color = '#4b5563';
    cityName.innerText = 'Location unavailable';
    coordsDetail.innerText = 'Your browser does not support live location.';
    return;
  }

  function syncLocation(lat, lon, accuracy, address, status) {
    const params = new URLSearchParams();
    params.append('latitude', lat);
    params.append('longitude', lon);
    params.append('accuracy', accuracy);
    params.append('address', address || 'Live location');
    params.append('status', status || 'active');
    params.append('lat', lat);
    params.append('lon', lon);
    params.append('acc', accuracy);

    return fetch(liveLocationApi, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().catch(function () {
        return null;
      });
    }).then(function (data) {
      const resolvedAddress = data && data.location && typeof data.location.address === 'string' && data.location.address.trim() !== ''
        ? data.location.address
        : (data && typeof data.address === 'string' && data.address.trim() !== '' ? data.address : address || 'Location unavailable');
      if (cityName) {
        cityName.innerText = resolvedAddress;
      }
      return data;
    }).catch(function () {
      if (cityName) {
        cityName.innerText = address || 'Location unavailable';
      }
    });
  }

  navigator.geolocation.getCurrentPosition(function (position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;
    const accuracy = Math.max(1, Math.round(position.coords.accuracy || 0));
    const latText = lat.toFixed(6);
    const lonText = lon.toFixed(6);

    statusText.innerText = 'Location Active';
    statusText.style.color = '#16a34a';
    coordsDetail.innerText = 'Lat: ' + latText + '° · Lon: ' + lonText + '° · Accuracy: ' + accuracy + 'm';

    syncLocation(parseFloat(lat), parseFloat(lon), accuracy, 'Live location', 'active');

    const miniMapContainer = document.getElementById('dashboardMiniMap');
    if (miniMapContainer && typeof L !== 'undefined') {
      const miniMap = L.map('dashboardMiniMap', {
        zoomControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        touchZoom: false,
        boxZoom: false,
        keyboard: false
      }).setView([lat, lon], 15);

      let tileErrorCount = 0;
      let fallbackActive = false;
      const primaryTiles = L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=<?= LOCATIONIQ_API_KEY ?>', {
        attribution: '© LocationIQ © OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(miniMap);

      primaryTiles.on('tileerror', function () {
        tileErrorCount++;
        console.error('[Defenza] Dashboard mini-map LocationIQ tile failed to load.');
        if (!fallbackActive && tileErrorCount >= 3) {
          fallbackActive = true;
          console.warn('[Defenza] Dashboard mini-map switching to fallback OpenStreetMap tiles.');
          miniMap.removeLayer(primaryTiles);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
          }).addTo(miniMap);
        }
      });

      const userIcon = L.divIcon({
        className: 'dashboard-mini-marker',
        html: '<div style="width:16px;height:16px;background:#8b5cf6;border:3px solid #fff;border-radius:50%;box-shadow:0 0 12px rgba(139,92,246,0.45);"></div>',
        iconSize: [16, 16]
      });
      L.marker([lat, lon], { icon: userIcon }).addTo(miniMap);
      setTimeout(function () {
        miniMap.invalidateSize();
      }, 150);
    }
  }, function () {
    statusText.innerText = 'Location Access Disabled';
    statusText.style.color = '#4b5563';
    cityName.innerText = 'Location unavailable';
    coordsDetail.innerText = 'Enable browser location to get live tracking coordinates.';
  }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
});
</script>

<?php dashClose(); dashFoot(); ?>
