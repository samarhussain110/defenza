<?php
// Dashboard sidebar
function renderSidebar(string $activePage = ''): void {
    $user     = getAuthenticatedUser();
    $navItems = [
        ['icon' => 'fa-gauge-high',         'label' => 'Dashboard',          'href' => '/pages/dashboard.php'],
        ['icon' => 'fa-circle-exclamation', 'label' => 'SOS Alert',          'href' => '/pages/sos-alert.php'],
        ['icon' => 'fa-location-dot',       'label' => 'Live Location',      'href' => '/pages/live-location.php'],
        ['icon' => 'fa-route',              'label' => 'Safety Check-In',    'href' => '/pages/safety_checkin.php'],
        ['icon' => 'fa-map-location-dot',   'label' => 'Nearby Safe Places', 'href' => '/pages/nearby-places.php'],
        ['icon' => 'fa-circle-question',   'label' => 'Help Center',        'href' => '/pages/help_center.php'],
        ['icon' => 'fa-user',               'label' => 'Profile',            'href' => '/pages/profile.php'],
        ['icon' => 'fa-users',              'label' => 'Emergency Contacts', 'href' => '/pages/contacts.php'],
        ['icon' => 'fa-file-lines',         'label' => 'Reports',            'href' => '/pages/reports.php'],
        ['icon' => 'fa-shield-check',       'label' => 'Safety Tips',        'href' => '/pages/safety-tips.php'],
        ['icon' => 'fa-gear',               'label' => 'Settings',           'href' => '/pages/settings.php'],
    ];
?>
<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <!-- Header / Logo -->
  <div class="sidebar-header">
    <a href="<?= APP_URL ?>/" class="sidebar-logo" style="text-decoration:none;">
      <div class="sidebar-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div>
        <span class="sidebar-logo-text" data-i18n="app_name"><?= APP_NAME ?></span>
        <span class="sidebar-logo-sub" data-i18n="app_tagline"><?= APP_TAGLINE ?></span>
      </div>
    </a>
    <button style="background:none;border:none;cursor:pointer;color:rgba(233,213,255,.6);padding:.35rem;border-radius:.5rem;display:flex;align-items:center;transition:all .2s;" onclick="closeSidebar()" id="sidebarCloseBtn" title="Close" data-i18n-title="sidebar_close" data-i18n="sidebar_close">
      <i class="fa-solid fa-xmark" style="font-size:1.05rem;"></i>
    </button>
  </div>

  <!-- User info strip -->
  <div style="padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:.75rem;">
    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.85rem;flex-shrink:0;box-shadow:0 2px 8px rgba(244,114,182,.3);">
      <?= initials($user['full_name']) ?>
    </div>
    <div style="min-width:0;">
      <div style="font-size:.84rem;font-weight:800;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($user['full_name']) ?></div>
      <div style="font-size:.67rem;color:rgba(196,181,253,.7);display:flex;align-items:center;gap:.3rem;margin-top:.1rem;">
        <span style="width:5px;height:5px;border-radius:50%;background:#4ade80;display:inline-block;"></span><span data-i18n="sidebar_status_active">Safe &amp; Active</span>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">
    <div class="sidebar-label" data-i18n="sidebar_main_menu">Main Menu</div>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= APP_URL . $item['href'] ?>"
         class="sidebar-nav-item <?= ($activePage === $item['label']) ? 'active' : '' ?>">
        <i class="fa-solid <?= $item['icon'] ?>"></i>
        <span data-i18n="nav_<?= strtolower(str_replace([' ', '-', '/', '(', ')'], '_', $item['label'])) ?>"><?= $item['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Footer -->
  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/auth/logout.php" class="sidebar-logout" data-i18n="sidebar_logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      Logout
    </a>
  </div>
</aside>
<?php } ?>
