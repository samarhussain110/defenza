<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

$db = getDB();
if ($db) {
  $db->query("CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    name VARCHAR(150) NOT NULL,
    relationship VARCHAR(80) NOT NULL DEFAULT 'Contact',
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_emergency_contacts_user_id (user_id),
    CONSTRAINT fk_emergency_contacts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->query("CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    accuracy DECIMAL(8,2) DEFAULT NULL,
    address VARCHAR(255) NOT NULL DEFAULT '',
    status ENUM('active','resolved') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sos_user_id (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->query("CREATE TABLE IF NOT EXISTS live_locations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    accuracy DECIMAL(8,2) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    status VARCHAR(32) DEFAULT 'active',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_live_locations_user_id (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->query("ALTER TABLE sos_alerts ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) DEFAULT NULL");
  $db->query("ALTER TABLE sos_alerts ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) DEFAULT NULL");
  $db->query("ALTER TABLE sos_alerts ADD COLUMN IF NOT EXISTS accuracy DECIMAL(8,2) DEFAULT NULL");
  $db->query("ALTER TABLE sos_alerts ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
  $db->query("ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS accuracy DECIMAL(8,2) DEFAULT NULL");
}

$contacts = [];
if ($db) {
  try {
    $stmt = $db->prepare('SELECT id, name, relationship, phone, email FROM emergency_contacts WHERE user_id = ? ORDER BY id DESC');
    if ($stmt) {
      $stmt->bind_param('i', $user['id']);
      $stmt->execute();
      $res = $stmt->get_result();
      $contacts = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
      $stmt->close();
    }
  } catch (Throwable $e) {
    $contacts = [];
  }
}
$alerts   = getUserAlerts($user['id'] ?? null, 10);

dashHead('SOS Alert');
dashOpen('SOS Alert', 'SOS Alert');
?>

<style>
@keyframes sos-pulsate-one {
  0% { transform: scale(1); opacity: 0.6; }
  100% { transform: scale(1.6); opacity: 0; }
}
@keyframes sos-pulsate-two {
  0% { transform: scale(1); opacity: 0.8; }
  100% { transform: scale(2.1); opacity: 0; }
}
@keyframes float-sos {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-4px); }
  100% { transform: translateY(0px); }
}
.sos-page-shell {
  width: 100%;
  max-width: 100%;
  margin: 0 auto;
  padding: 0.35rem 0 1rem;
}
.sos-glow-card {
  border: 1px solid rgba(239, 68, 68, 0.15) !important;
  box-shadow: 0 15px 35px rgba(239, 68, 68, 0.08) !important;
  background: radial-gradient(circle at top right, #fff, #fff9f9) !important;
}
.sos-btn-main {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f87171 0%, #ef4444 45%, #b91c1c 100%);
  border: 6px solid rgba(255,255,255,0.95);
  color: #fff;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 5;
  box-shadow: 0 18px 34px rgba(185, 28, 28, 0.26), inset 0 4px 12px rgba(255,255,255,0.28);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.sos-btn-main:hover {
  transform: scale(1.06);
  box-shadow: 0 20px 42px rgba(185, 28, 28, 0.34), inset 0 4px 14px rgba(255,255,255,0.34);
}
.sos-btn-main:active {
  transform: scale(0.95);
}
.sos-label-main {
  font-size: 1.8rem;
  font-weight: 950;
  letter-spacing: 0.05em;
  text-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.sos-label-sub {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  opacity: 0.9;
}
.sos-btn-sending .sos-label-main {
  font-size: 1rem;
  line-height: 1.1;
  letter-spacing: 0;
}
.sos-btn-sending .sos-label-sub {
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  margin-top: 0.15rem;
}
.sos-outer-rings {
  position: relative;
  width: 140px;
  height: 140px;
  margin: 0 auto 1.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.sos-ring-1, .sos-ring-2 {
  position: absolute;
  inset: -16px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.16);
  border: 1px solid rgba(248, 113, 113, 0.24);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  pointer-events: none;
  z-index: 1;
}
.sos-ring-1 {
  animation: sos-pulsate-one 2.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
}
.sos-ring-2 {
  animation: sos-pulsate-two 2.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
  animation-delay: 0.7s;
}
.hover-stat-card {
  transition: transform 0.25s, box-shadow 0.25s;
}
.hover-stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}
</style>

<div class="sos-page-shell">

  <!-- Info banner -->
  <div style="background: linear-gradient(135deg, #fef2f2 0%, #fff 100%); border-radius: var(--r-lg); padding: 1.5rem; border: 1px solid #fca5a5; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.04);">
    <div style="width:3rem;height:3rem;border-radius:var(--r-md);background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow: 0 4px 10px rgba(239, 68, 68, 0.15);">
      <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;font-size:1.2rem;animation:float-sos 2s infinite ease-in-out;"></i>
    </div>
    <div>
      <div style="font-weight:900;color:#111827;font-size:1.05rem;margin-bottom:.25rem;letter-spacing:-0.01em;">SOS Emergency Hub</div>
      <p style="font-size:.85rem;color:#4b5563;line-height:1.6;font-weight:500;">In an emergency, press the SOS button below. Your location and alert will be shared instantly with all your emergency contacts.</p>
    </div>
  </div>

  <!-- SOS button card -->
  <div class="card-defenza no-hover sos-glow-card" style="padding:2.25rem 2rem;text-align:center;margin:0 auto 1.5rem;max-width:620px;border-radius:var(--r-lg);display:flex;flex-direction:column;align-items:center;justify-content:center;" id="sosBtnWrap">
    <div class="sos-outer-rings" style="margin:0 auto 2.5rem;">
      <div class="sos-ring-1"></div>
      <div class="sos-ring-2"></div>
      <button class="sos-btn-main" id="sosBtnDemo" data-contact-count="<?= count($contacts) ?>" data-sos-url="<?= rtrim(parse_url(APP_URL, PHP_URL_PATH), '/') ?>/api/sos.php"<?= count($contacts) === 0 ? ' disabled' : '' ?>>
        <span class="sos-label-main">SOS</span>
        <span class="sos-label-sub">TAP TO ALERT</span>
      </button>
    </div>
    <?php if (count($contacts) === 0): ?>
      <div class="alert-box alert-warning" style="margin:0 auto 1.25rem;max-width:540px;border-radius:var(--r-md);background:rgba(254, 243, 199, 0.45);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(253, 230, 138, 0.6);">
        <i class="fa-solid fa-circle-exclamation" style="color:#d97706;"></i>
        <span style="font-size:.82rem;font-weight:700;color:#92400e;">No emergency contacts found. Please add at least one contact on the <a href="<?= APP_URL ?>/pages/contacts.php" style="text-decoration:underline;color:var(--purple-600);">Emergency Contacts</a> page before using SOS.</span>
      </div>
    <?php endif; ?>
    <p style="color:#6b7280;font-size:.84rem;max-width:380px;margin:0 auto;font-weight:600;line-height:1.5;">
      Pressing the SOS trigger will instantly dispatch SMS / Email notifications containing your real-time coordinates.
    </p>
  </div>

  <!-- Success state (hidden by default) -->
  <div class="card-defenza no-hover" style="padding:3rem 2rem;text-align:center;margin-bottom:1.5rem;display:none;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);border:2px solid #a7f3d0;box-shadow:0 20px 40px rgba(52,211,153,0.15);" id="sosSuccess">
    <div style="width:6rem;height:6rem;border-radius:50%;background:linear-gradient(135deg,#34d399,#059669);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 10px 25px rgba(52,211,153,.45);">
      <i class="fa-solid fa-circle-check" style="color:#fff;font-size:2.5rem;"></i>
    </div>
    <h2 style="font-size:1.6rem;font-weight:950;color:#111827;margin-bottom:.5rem;letter-spacing:-0.02em;">Alert Sent Successfully!</h2>
    <p style="color:#6b7280;font-size:.875rem;margin-bottom:2rem;max-width:420px;margin-left:auto;margin-right:auto;font-weight:500;">Your trusted emergency contacts have been safely notified, and coordinates logging is live.</p>

    <!-- Notified contacts -->
    <div id="notifiedContactsList" style="display:flex;flex-direction:column;gap:.75rem;max-width:420px;margin:0 auto 2rem;">
      <?php foreach ($contacts as $c): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(168,85,247,0.04);border-radius:var(--r-md);padding:.85rem 1.15rem;border:1px solid rgba(168,85,247,0.08);">
          <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--grad-main);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;font-weight:900;flex-shrink:0;box-shadow:0 3px 8px rgba(168,85,247,0.25);">
              <?= strtoupper(substr($c['name'], 0, 1)) ?>
            </div>
            <div style="text-align:left;">
              <div style="font-size:.85rem;font-weight:800;color:#111827;"><?= e($c['name']) ?> <span style="font-weight:600;color:#9ca3af;font-size:.75rem;">(<?= e($c['relationship']) ?>)</span></div>
              <div style="font-size:.72rem;color:#6b7280;margin-top:.1rem;font-weight:500;"><?= e($c['phone']) ?></div>
            </div>
          </div>
          <span class="badge-pill badge-green" style="font-weight:800;letter-spacing:0.03em;">Notified</span>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:1rem;justify-content:center;">
      <button id="sosDismissBtn" class="btn-defenza btn-secondary" style="padding:0.75rem 1.5rem;font-weight:700;">
        <i class="fa-solid fa-xmark"></i> Dismiss
      </button>
      <a href="<?= APP_URL ?>/pages/live-location.php" class="btn-defenza btn-primary" style="padding:0.75rem 1.5rem;font-weight:800;box-shadow:0 6px 18px rgba(168,85,247,0.3);text-decoration:none;">
        <i class="fa-solid fa-tower-broadcast"></i> Live Tracking
      </a>
    </div>
  </div>

  <!-- Info cards -->
  <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.9rem;margin-bottom:1.25rem;align-items:stretch;">
    <?php foreach ([
      ['fa-phone',         'purple', 'Contacts Notified', count($contacts) . ' contacts alerted'],
      ['fa-location-dot',  'blue',   'Location Shared',   'GPS location sent live'],
      ['fa-shield-halved', 'green',  'Help Dispatched',   'Nearest station notified'],
    ] as [$icon, $color, $title, $desc]): ?>
      <div class="card-defenza hover-stat-card" style="text-align:center;padding:1.5rem 1.25rem;border:1px solid rgba(168,85,247,0.06);box-shadow:var(--shadow-sm);">
        <div class="stat-icon <?= $color ?>" style="margin:0 auto 1rem;box-shadow:0 4px 10px rgba(0,0,0,0.05);"><i class="fa-solid <?= $icon ?>"></i></div>
        <div style="font-weight:900;font-size:.875rem;color:#111827;margin-bottom:.25rem;"><?= $title ?></div>
        <div style="font-size:.75rem;color:#6b7280;font-weight:500;"><?= $desc ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Warning -->
  <div class="alert-box alert-warning" style="margin-bottom:1.25rem;background:rgba(254, 243, 199, 0.45);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(253, 230, 138, 0.6);border-radius:var(--r-md);padding:1rem 1.25rem;">
    <i class="fa-solid fa-triangle-exclamation" style="color:#d97706;flex-shrink:0;font-size:1rem;"></i>
    <span style="color:#92400e;font-size:0.8rem;font-weight:700;line-height:1.5;">Attention: Only activate SOS in genuine threats. Fake triggers divert active rescue workflows.</span>
  </div>

  <!-- Alert history -->
  <div class="card-defenza no-hover" style="padding:0;overflow:hidden;border:1px solid rgba(168,85,247,0.12);box-shadow:var(--shadow-sm);background:rgba(255,255,255,0.92);">
    <div style="padding:1.25rem;border-bottom:1px solid rgba(168,85,247,.06);display:flex;align-items:center;justify-content:space-between;background:#fff;">
      <div style="font-weight:900;color:#111827;font-size:.9rem;letter-spacing:-0.01em;">Emergency Alert Logs</div>
      <span class="badge-pill badge-purple" style="font-weight:800;font-size:0.7rem;padding:0.3rem 0.75rem;"><?= count($alerts) ?> alerts logged</span>
    </div>
    <div style="overflow-x:auto;">
      <table class="table-defenza" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:var(--purple-50);border-bottom:1px solid rgba(168,85,247,0.06);">
            <th style="padding:1rem;font-weight:800;font-size:0.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">#</th>
            <th style="padding:1rem;font-weight:800;font-size:0.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Date &amp; Time</th>
            <th style="padding:1rem;font-weight:800;font-size:0.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Registered Location</th>
            <th style="padding:1rem;font-weight:800;font-size:0.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">System Status</th>
          </tr>
        </thead>
        <tbody id="sosAlertLogsBody">
          <?php foreach ($alerts as $i => $a): ?>
            <tr style="border-bottom:1px solid rgba(168,85,247,0.03);transition:background 0.15s;" onmouseover="this.style.background='var(--purple-50)'" onmouseout="this.style.background='transparent'">
              <td style="padding:1rem;color:#9ca3af;font-weight:600;font-size:0.8rem;"><?= $i + 1 ?></td>
              <td style="padding:1rem;white-space:nowrap;font-weight:700;font-size:0.82rem;color:#374151;"><?= date('M d, Y · H:i', strtotime($a['created_at'])) ?></td>
              <td style="padding:1rem;font-size:0.82rem;color:#6b7280;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;"><?= e($a['address']) ?></td>
              <td style="padding:1rem;"><span class="badge-pill <?= $a['status']==='resolved' ? 'badge-green' : 'badge-amber' ?>" style="font-weight:800;letter-spacing:0.02em;font-size:0.7rem;"><?= ucfirst($a['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  const sosButton = document.getElementById('sosBtnDemo');
  const sosWrap = document.getElementById('sosBtnWrap');
  const sosSuccess = document.getElementById('sosSuccess');
  const notifiedContactsList = document.getElementById('notifiedContactsList');
  const dismissButton = document.getElementById('sosDismissBtn');
  const originalSosButtonHtml = sosButton ? sosButton.innerHTML : '';
  const originalSosWrapDisplay = sosWrap ? sosWrap.style.display || 'flex' : 'flex';
  const alertLogsBody = document.getElementById('sosAlertLogsBody');
  const sosUrl = sosButton ? sosButton.getAttribute('data-sos-url') : 'api/sos.php';
  const initialContacts = <?= json_encode(array_map(function ($contact) {
    return [
      'id' => (int) ($contact['id'] ?? 0),
      'name' => (string) ($contact['name'] ?? $contact['contact_name'] ?? ''),
      'relationship' => (string) ($contact['relationship'] ?? 'Contact'),
      'phone' => (string) ($contact['phone'] ?? $contact['contact_phone'] ?? ''),
      'email' => (string) ($contact['email'] ?? '')
    ];
  }, $contacts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const initialAlerts = <?= json_encode(array_map(function ($alert) {
    return [
      'id' => (int) ($alert['id'] ?? 0),
      'created_at' => (string) ($alert['created_at'] ?? ''),
      'address' => (string) ($alert['address'] ?? ''),
      'status' => (string) ($alert['status'] ?? 'active')
    ];
  }, $alerts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const contactCount = Number(sosButton ? sosButton.getAttribute('data-contact-count') : '0');

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
    });
  }

  function renderNotifiedContacts(contacts) {
    if (!notifiedContactsList) return;
    if (!contacts || contacts.length === 0) {
      notifiedContactsList.innerHTML = '<div style="font-size:.8rem;color:#6b7280;">No contacts were notified.</div>';
      return;
    }

    notifiedContactsList.innerHTML = contacts.map(function (contact) {
      const name = contact.name || 'Emergency Contact';
      const relationship = contact.relationship || 'Contact';
      const phone = contact.phone || 'No phone provided';
      const initial = String(name).charAt(0).toUpperCase();
      return '<div style="display:flex;align-items:center;justify-content:space-between;background:rgba(168,85,247,0.04);border-radius:var(--r-md);padding:.85rem 1.15rem;border:1px solid rgba(168,85,247,0.08);">' +
        '<div style="display:flex;align-items:center;gap:.75rem;">' +
        '<div style="width:36px;height:36px;border-radius:50%;background:var(--grad-main);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;font-weight:900;flex-shrink:0;box-shadow:0 3px 8px rgba(168,85,247,0.25);">' + escapeHtml(initial) + '</div>' +
        '<div style="text-align:left;">' +
        '<div style="font-size:.85rem;font-weight:800;color:#111827;">' + escapeHtml(name) + ' <span style="font-weight:600;color:#9ca3af;font-size:.75rem;">(' + escapeHtml(relationship) + ')</span></div>' +
        '<div style="font-size:.72rem;color:#6b7280;margin-top:.1rem;font-weight:500;">' + escapeHtml(phone) + '</div>' +
        '</div></div>' +
        '<span class="badge-pill badge-green" style="font-weight:800;letter-spacing:0.03em;">Notified</span></div>';
    }).join('');
  }

  function renderAlertLogs(alerts) {
    if (!alertLogsBody) return;
    const rows = (alerts || []).slice(0, 10).map(function (alert, index) {
      const createdAt = alert.created_at ? alert.created_at : 'Just now';
      const address = alert.address || 'Location pending';
      const status = String(alert.status || 'active').toLowerCase();
      const badgeClass = status === 'resolved' ? 'badge-green' : 'badge-amber';
      const label = status === 'resolved' ? 'Resolved' : 'Active';
      return '<tr style="border-bottom:1px solid rgba(168,85,247,0.03);transition:background 0.15s;" onmouseover="this.style.background=\'var(--purple-50)\'" onmouseout="this.style.background=\'transparent\'">' +
        '<td style="padding:1rem;color:#9ca3af;font-weight:600;font-size:0.8rem;">' + (index + 1) + '</td>' +
        '<td style="padding:1rem;white-space:nowrap;font-weight:700;font-size:0.82rem;color:#374151;">' + escapeHtml(createdAt) + '</td>' +
        '<td style="padding:1rem;font-size:0.82rem;color:#6b7280;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">' + escapeHtml(address) + '</td>' +
        '<td style="padding:1rem;"><span class="badge-pill ' + badgeClass + '" style="font-weight:800;letter-spacing:0.02em;font-size:0.7rem;">' + escapeHtml(label) + '</span></td>' +
        '</tr>';
    }).join('');

    alertLogsBody.innerHTML = rows;
  }

  function showLocationNotice(message) {
    if (typeof window.showToast === 'function') {
      window.showToast(message, 'warning');
      return;
    }
    if (typeof window.alert === 'function') {
      window.alert(message);
    }
  }

  const LOCATION_FRESHNESS_LIMIT_MS = 120000;

  function getStoredLocationFallback() {
    try {
      const raw = window.localStorage ? window.localStorage.getItem('defenza_last_known_location') : null;
      if (!raw) return null;
      const cached = JSON.parse(raw);
      if (!cached || !Number.isFinite(Number(cached.lat)) || !Number.isFinite(Number(cached.lon))) {
        return null;
      }
      const cachedTimestamp = Number(cached.timestamp || Date.now());
      const locationAgeMs = Date.now() - cachedTimestamp;
      if (!Number.isFinite(locationAgeMs) || locationAgeMs > LOCATION_FRESHNESS_LIMIT_MS) {
        return null;
      }
      return { ...cached, timestamp: cachedTimestamp };
    } catch (_) {
      return null;
    }
  }

  function saveStoredLocation(location) {
    try {
      if (window.localStorage) {
        window.localStorage.setItem('defenza_last_known_location', JSON.stringify(location));
      }
    } catch (_) {}
  }

  async function resolveLocation() {
    const fallback = getStoredLocationFallback();
    if (!navigator.geolocation) {
      return fallback ? { ...fallback, warning: 'Geolocation is unavailable on this browser. Using the last known location.', source: 'cached' } : { latitude: null, longitude: null, accuracy: null, address: 'Resolving address...', warning: 'Geolocation is unavailable on this browser.', source: 'gps' };
    }

    let permissionState = null;
    if (navigator.permissions && navigator.permissions.query) {
      try {
        permissionState = await navigator.permissions.query({ name: 'geolocation' });
      } catch (_) {}
    }

    if (permissionState && permissionState.state === 'denied') {
      showLocationNotice('Location permission is disabled. We will use the last known location if available so the SOS alert can still be sent.');
      return fallback ? { ...fallback, warning: 'Location permission denied. Using the last known location.', source: 'cached' } : { latitude: null, longitude: null, accuracy: null, address: 'Resolving address...', warning: 'Location permission denied.', source: 'gps' };
    }

    return await new Promise(function (resolve) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const accuracy = Number(position?.coords?.accuracy ?? 0);
          if (!Number.isFinite(accuracy) || accuracy <= 0 || accuracy > 100) {
            if (fallback) {
              showLocationNotice('The current GPS fix is too coarse for SOS use. Using the last known location instead.');
              resolve({ ...fallback, warning: 'GPS accuracy too low. Using last known location.', source: 'cached' });
              return;
            }
            showLocationNotice('The current GPS fix is too coarse for SOS use. Please move to an open area and try again.');
            resolve({ latitude: null, longitude: null, accuracy: null, address: 'Resolving address...', warning: 'GPS accuracy too low.', source: 'gps' });
            return;
          }

          const location = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: accuracy,
            address: 'Resolving address...',
            source: 'gps',
            timestamp: position.timestamp || Date.now()
          };
          saveStoredLocation(location);
          resolve({ ...location, warning: '' });
        },
        function (error) {
          if (fallback) {
            showLocationNotice('We could not read your live location. Using the last known location to keep the SOS alert active.');
            resolve({ ...fallback, warning: 'Using last known location because live geolocation failed.', source: 'cached' });
            return;
          }

          showLocationNotice('We could not retrieve your location. The SOS alert will be sent without live coordinates.');
          resolve({ latitude: null, longitude: null, accuracy: null, address: 'Resolving address...', warning: 'Geolocation error.', source: 'gps' });
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  function resetSosState() {
    if (sosWrap) sosWrap.style.display = originalSosWrapDisplay;
    if (sosSuccess) sosSuccess.style.display = 'none';
    if (sosButton) {
      sosButton.disabled = contactCount === 0;
      sosButton.classList.remove('sos-btn-sending');
      if (originalSosButtonHtml) {
        sosButton.innerHTML = originalSosButtonHtml;
      }
    }
    sosRequestInFlight = false;
  }

  let sosRequestInFlight = false;

  if (sosButton && contactCount === 0) {
    sosButton.disabled = true;
  }

  if (sosButton) {
    if (sosButton.getAttribute('data-sos-bound') !== 'true') {
      sosButton.setAttribute('data-sos-bound', 'true');

      sosButton.addEventListener('click', async function (event) {
      event.preventDefault();
      if (sosButton.disabled || sosRequestInFlight) return;

      const originalLabelMain = sosButton.querySelector('.sos-label-main')?.textContent;
      const originalLabelSub = sosButton.querySelector('.sos-label-sub')?.textContent;
      const labelMainEl = sosButton.querySelector('.sos-label-main');
      const labelSubEl = sosButton.querySelector('.sos-label-sub');

      if (labelMainEl) labelMainEl.innerHTML = '<span class="spinner"></span> SENDING...';
      if (labelSubEl) labelSubEl.textContent = 'Please wait';
      sosButton.classList.add('sos-btn-sending');

      if (typeof window.showToast === 'function') {
        showToast('🚨 Sending SOS alert... Please wait.', 'info', 0);
      }

      const locationResult = await resolveLocation();

      let address = locationResult.address || 'Resolving address...';
      if (locationResult.latitude !== null && locationResult.longitude !== null) {
        address = 'Lat ' + Number(locationResult.latitude).toFixed(5) + ', Lon ' + Number(locationResult.longitude).toFixed(5);
      }

      console.debug('[Defenza Debug] SOS resolveLocation result', locationResult);
      const params = new URLSearchParams({
        latitude: locationResult.latitude === null ? '' : String(locationResult.latitude),
        longitude: locationResult.longitude === null ? '' : String(locationResult.longitude),
        address: 'Resolving address...',
        accuracy: locationResult.accuracy === null ? '' : String(locationResult.accuracy),
        location_source: locationResult.source || 'gps',
        timestamp: locationResult.timestamp || Date.now()
      });

      sosRequestInFlight = true;
      sosButton.disabled = true;

      try {
        console.debug('[Defenza Debug] SOS request payload', { params: Object.fromEntries(params.entries()), sosUrl });
        const response = await fetch(sosUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: params.toString()
        });
        const responseText = await response.text();
        let data = null;

        try {
          data = responseText ? JSON.parse(responseText) : null;
        } catch (_) {
          throw new Error('The server returned an unexpected response. Please refresh and try again.');
        }

        if (!data) {
          throw new Error('The server returned an unexpected response. Please refresh and try again.');
        }

        console.debug('[Defenza Debug] SOS response payload', data);
        if (!data.success) {
          throw new Error(data.message || data.debug_error || 'Unable to send SOS alert. SMS delivery failed.');
        }

        renderNotifiedContacts(data.contacts || initialContacts || []);
        const latestAlert = {
          id: data.alert_id || 'SOS',
          created_at: data.timestamp || new Date().toISOString(),
          address: data.location && data.location.address ? data.location.address : address,
          status: 'active'
        };
        const updatedAlerts = [latestAlert].concat(initialAlerts || []).slice(0, 10);
        initialAlerts.splice(0, initialAlerts.length, ...updatedAlerts);
        renderAlertLogs(initialAlerts);

        if (typeof window.showToast === 'function') {
          showToast('✅ SOS alert sent successfully. Help is on the way.', 'success', 5000);
        }

        if (sosWrap && sosSuccess) {
          sosWrap.style.display = 'none';
          sosSuccess.style.display = 'block';
        }
      } catch (error) {
        if (typeof window.showToast === 'function') {
          window.showToast(error.message || 'Unable to send SOS alert', 'error');
        } else {
          alert(error.message || 'Unable to send SOS alert');
        }
      } finally {
        sosRequestInFlight = false;
        if (sosButton && contactCount > 0) {
          sosButton.disabled = false;
        }
        if (labelMainEl) labelMainEl.textContent = originalLabelMain || 'SOS';
        if (labelSubEl) labelSubEl.textContent = originalLabelSub || 'TAP TO ALERT';
        sosButton.classList.remove('sos-btn-sending');
      }
    });
    }
  }

  if (dismissButton) {
    dismissButton.addEventListener('click', function () {
      resetSosState();
    });
  }

  renderNotifiedContacts(initialContacts);
  renderAlertLogs(initialAlerts);
})();
</script>

<?php dashClose(); dashFoot(); ?>
