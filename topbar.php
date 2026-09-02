<?php
// Dashboard top bar
function renderTopbar(string $pageTitle = 'Dashboard'): void {
    $user = getAuthenticatedUser();
    $ini  = initials($user['full_name']);
    $name = e($user['full_name']);
    $mockAlertCount = 1;
?>
<header class="dash-topbar">
  <div class="topbar-left">
    <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Open menu" data-i18n-aria-label="topbar_open_menu" data-i18n="topbar_open_menu">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="topbar-title"<?php if ($pageTitle === 'Dashboard') { echo ' data-i18n="nav_dashboard"'; } ?>><?= e($pageTitle) ?></h1>
  </div>

  <div class="topbar-right">
    <!-- Search -->
    <div style="position: relative; width: 100%; max-width: 320px;">
      <div class="topbar-search" style="position: relative;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="headerSearchInput" placeholder="Search…" autocomplete="off" data-i18n-placeholder="topbar_search_placeholder" />
      </div>
      <div id="searchResultsDropdown" style="display: none; position: absolute; top: 110%; left: 0; width: 100%; background: rgba(255,255,255,0.98); border: 1px solid rgba(168,85,247,0.16); border-radius: var(--r-md); box-shadow: 0 10px 25px rgba(0,0,0,0.12); z-index: 999; max-height: 260px; overflow-y: auto; padding: 0.5rem 0;"></div>
    </div>

    <!-- Notifications -->
    <div style="position: relative; display: inline-block;">
      <button class="topbar-notif" id="notiBellBtn" title="Notifications" type="button" aria-label="Show notifications" data-i18n-title="topbar_notifications_title" data-i18n-aria-label="topbar_notifications_title">
        <i class="fa-solid fa-bell"></i>
        <?php if ($mockAlertCount > 0): ?>
          <span class="notif-dot" style="display: block; box-shadow: 0 0 10px rgba(239,68,68,0.8);"></span>
        <?php else: ?>
          <span class="notif-dot" style="display: none;"></span>
        <?php endif; ?>
      </button>

      <div id="notiDropdown" style="display: none; position: absolute; top: 130%; right: 0; width: 280px; background: #fff; border: 1px solid rgba(168,85,247,0.1); border-radius: var(--r-md); box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 1000; padding: 0.5rem 0;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(168,85,247,0.06); font-weight: 800; font-size: 0.8rem; color: #111827; display: flex; justify-content: space-between; align-items: center;">
          <span data-i18n="topbar_notifications_title">Notifications</span>
          <span style="color: var(--purple-600); font-size: 0.75rem; font-weight: 700;" data-i18n="topbar_notifications_live">Live</span>
        </div>
        <div style="max-height: 200px; overflow-y: auto;">
          <div style="padding: 0.75rem 1rem; display: flex; gap: 0.6rem; align-items: flex-start; border-bottom: 1px solid rgba(0,0,0,0.03);">
            <span style="font-size: 1rem; line-height: 1;">🚨</span>
            <div>
              <p style="margin: 0; font-size: 0.78rem; font-weight: 700; color: #111827;" data-i18n="topbar_notifications_sos_alert_title">SOS Alert active</p>
              <p style="margin: 3px 0 0; font-size: 0.7rem; color: #6b7280;" data-i18n="topbar_notifications_sos_alert_desc">Contacts notified and GPS link online.</p>
            </div>
          </div>
          <div style="padding: 0.75rem 1rem; display: flex; gap: 0.6rem; align-items: flex-start;">
            <span style="font-size: 1rem; line-height: 1;">🟢</span>
            <div>
              <p style="margin: 0; font-size: 0.78rem; font-weight: 700; color: #111827;" data-i18n="topbar_notifications_status_title">Status: Secure</p>
              <p style="margin: 3px 0 0; font-size: 0.7rem; color: #6b7280;" data-i18n="topbar_notifications_status_desc">Your GPS and live safety link are responding normally.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User -->
    <div class="topbar-user">
      <div class="topbar-avatar"><?= $ini ?></div>
      <div>
        <div class="topbar-name"><?= $name ?></div>
        <div class="topbar-status" data-i18n="topbar_safe">Safe</div>
      </div>
      <i class="fa-solid fa-chevron-down" style="font-size:.65rem;color:#c4b5fd;margin-left:.25rem;"></i>
    </div>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('headerSearchInput');
  const searchDropdown = document.getElementById('searchResultsDropdown');
  const bellBtn = document.getElementById('notiBellBtn');
  const notiDropdown = document.getElementById('notiDropdown');

  if (!searchInput || !searchDropdown || !bellBtn || !notiDropdown) {
    return;
  }

  const getAppBase = function () {
    const appMeta = document.querySelector('meta[name="app-url"]');
    if (appMeta && appMeta.getAttribute('content')) {
      try {
        const appUrl = new URL(appMeta.getAttribute('content'), window.location.origin);
        return appUrl.pathname.replace(/\/$/, '');
      } catch (err) {
        // fall back to pathname detection
      }
    }

    const path = window.location.pathname;
    const parts = path.split('/').filter(Boolean);
    const pagesIndex = parts.indexOf('pages');
    if (pagesIndex > 0) {
      return '/' + parts.slice(0, pagesIndex).join('/');
    }
    return '';
  };

  const appBase = getAppBase();

  const routes = [
    { name: '🚨 SOS Emergency Alert', paths: [appBase + '/pages/sos-alert.php'], keys: ['sos', 'alert', 'emergency', 'danger', 'help'] },
    { name: '📍 Live Tracking Map', paths: [appBase + '/pages/live-location.php'], keys: ['live', 'map', 'gps', 'location', 'track', 'tracking'] },
    { name: '👥 Trusted Emergency Contacts', paths: [appBase + '/pages/contacts.php'], keys: ['contact', 'contacts', 'phone', 'numbers', 'friends'] },
    { name: '💡 Women Safety Tips', paths: [appBase + '/pages/safety-tips.php'], keys: ['tips', 'guide', 'safety', 'manual', 'advice'] },
    { name: '✅ Safety Check-in', paths: [appBase + '/pages/safety_checkin.php', appBase + '/pages/safety-checkin.php'], keys: ['safety', 'checkin', 'check-in', 'status', 'safe'] },
    { name: '🏢 Nearby Safe Places', paths: [appBase + '/pages/nearby-places.php'], keys: ['nearby', 'safe places', 'shelter', 'hospital', 'police', 'safe zone'] },
    { name: 'ℹ️ Help Center / Support', paths: [appBase + '/pages/help_center.php', appBase + '/pages/help-center.php'], keys: ['help', 'support', 'faq', 'center', 'chat'] },
    { name: '📊 Emergency Reports', paths: [appBase + '/pages/reports.php'], keys: ['reports', 'history', 'logs', 'incident', 'data'] },
    { name: '⚙️ Account Settings', paths: [appBase + '/pages/settings.php'], keys: ['settings', 'config', 'profile', 'password', 'setup'] },
    { name: '🧾 Your Profile', paths: [appBase + '/pages/profile.php'], keys: ['profile', 'account', 'me'] }
  ];

  function getPrimaryPath(route) {
    return (route.paths && route.paths.length) ? route.paths[0] : route.path;
  }

  function navigateToRoute(route) {
    const candidates = route.paths && route.paths.length ? route.paths : [route.path];
    let index = 0;

    function tryNext() {
      if (index >= candidates.length) {
        window.location.href = candidates[0];
        return;
      }

      const candidate = candidates[index];
      index += 1;

      fetch(candidate, { method: 'HEAD' })
        .then(function (response) {
          if (response.ok) {
            window.location.href = candidate;
          } else {
            tryNext();
          }
        })
        .catch(function () {
          tryNext();
        });
    }

    tryNext();
  }

  const forms = document.querySelectorAll('form');
  forms.forEach(function (form) {
    if (form.contains(searchInput)) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
      });
    }
  });

  function renderMatches(term) {
    const value = (term || '').toLowerCase().trim();
    if (!value) {
      searchDropdown.style.display = 'none';
      return;
    }

    const matches = routes.filter(function (route) {
      return route.keys.some(function (key) {
        return key.indexOf(value) !== -1;
      }) || route.name.toLowerCase().indexOf(value) !== -1;
    });

    if (!matches.length) {
      searchDropdown.innerHTML = '<div style="padding: 0.6rem 1rem; font-size: 0.75rem; color: #9ca3af; font-weight: 500;" data-i18n="topbar_no_results">No results found...</div>';
      searchDropdown.style.display = 'block';
      return;
    }

    searchDropdown.innerHTML = matches.map(function (route) {
      return '<a href="' + getPrimaryPath(route) + '" style="display: block; padding: 0.6rem 1rem; font-size: 0.8rem; color: #374151; text-decoration: none; font-weight: 600; border-bottom: 1px solid rgba(0,0,0,0.02); transition: background 0.15s;" onmouseover="this.style.background=\'#f3e8ff\'" onmouseout="this.style.background=\'transparent\'">' + route.name + '</a>';
    }).join('');
    searchDropdown.style.display = 'block';
  }

  searchInput.addEventListener('input', function (event) {
    renderMatches(event.target.value);
  });

  // 1. Foolproof Dropdown Click Router
  searchDropdown.addEventListener('click', function (event) {
    const link = event.target.closest('a');
    if (link) {
      event.preventDefault();
      searchDropdown.style.display = 'none';
      
      // Extract target file name
      const hrefAttr = link.getAttribute('href');
      const targetFile = hrefAttr.substring(hrefAttr.lastIndexOf('/') + 1);
      
      // Direct relative replacement: Swaps current page name with the target page name
      const currentHref = window.location.href.split('?')[0];
      const newHref = currentHref.substring(0, currentHref.lastIndexOf('/') + 1) + targetFile;
      window.location.href = newHref;
    }
  });

  // 2. Foolproof Enter Key Search Router
  searchInput.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter') {
      return;
    }

    event.preventDefault();
    const query = searchInput.value.trim().toLowerCase();

    if (!query) {
      return;
    }

    // Direct filename index mapping
    const defenzaRoutes = {
      'dashboard': 'dashboard.php',
      'home': 'dashboard.php',
      'sos': 'sos-alert.php',
      'emergency': 'sos-alert.php',
      'alert': 'sos-alert.php',
      'live location': 'live-location.php',
      'location': 'live-location.php',
      'map': 'live-location.php',
      'gps': 'live-location.php',
      'nearby': 'nearby-places.php',
      'safe places': 'nearby-places.php',
      'hubs': 'nearby-places.php',
      'contact': 'contacts.php',
      'contacts': 'contacts.php',
      'numbers': 'contacts.php',
      'safety tips': 'safety-tips.php',
      'tips': 'safety-tips.php',
      'guide': 'safety-tips.php',
      'settings': 'settings.php',
      'setting': 'settings.php',
      'preferences': 'settings.php',
      'password': 'settings.php',
      'profile': 'profile.php',
      'account': 'profile.php',
      'details': 'profile.php'
    };

    let targetFile = null;
    for (const keyword in defenzaRoutes) {
      if (query.includes(keyword) || keyword.includes(query)) {
        targetFile = defenzaRoutes[keyword];
        break;
      }
    }

    if (targetFile) {
      // Strips query strings and seamlessly injects the correct filename relative to current folder
      const currentHref = window.location.href.split('?')[0];
      const newHref = currentHref.substring(0, currentHref.lastIndexOf('/') + 1) + targetFile;
      window.location.href = newHref;
    } else {
      renderMatches(query);
    }
  });

  bellBtn.addEventListener('click', function (event) {
    event.stopPropagation();
    notiDropdown.style.display = notiDropdown.style.display === 'block' ? 'none' : 'block';
    searchDropdown.style.display = 'none';
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('#headerSearchInput') && !event.target.closest('#searchResultsDropdown')) {
      searchDropdown.style.display = 'none';
    }
    if (!event.target.closest('#notiBellBtn') && !event.target.closest('#notiDropdown')) {
      notiDropdown.style.display = 'none';
    }
  });
});

</script>
<?php } ?>
