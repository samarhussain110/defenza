<?php
// Public top navbar (no session dependency)
function renderNavbar(string $activePage = ''): void {
    $links = [
        '/'                    => 'Home',
        '/#features'           => 'Features',
        '/pages/sos-alert.php' => 'Emergency',
        '/pages/about.php'     => 'About Us',
        '/pages/contact.php'   => 'Contact',
    ];
?>
<header class="public-navbar">
  <div class="navbar-inner">
    <!-- Logo -->
    <a href="<?= APP_URL ?>/" class="navbar-logo">
      <div class="navbar-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div>
        <span class="navbar-logo-text"><?= APP_NAME ?></span>
        <span class="navbar-logo-sub"><?= APP_TAGLINE ?></span>
      </div>
    </a>

    <!-- Desktop nav -->
    <nav class="navbar-nav">
      <?php foreach ($links as $href => $label): ?>
        <a href="<?= APP_URL . $href ?>"
           class="<?= ($activePage === $label) ? 'active' : '' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Actions -->
    <div class="navbar-actions">
      <a href="<?= APP_URL ?>/auth/login.php"    class="btn-defenza btn-ghost btn-sm">Login</a>
      <a href="<?= APP_URL ?>/auth/register.php" class="btn-defenza btn-primary btn-sm">Get Started</a>
    </div>

    <!-- Mobile toggler -->
    <button class="navbar-toggler" onclick="toggleMobileMenu()" aria-label="Toggle menu">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  <!-- Mobile menu -->
  <div class="mobile-menu" id="mobileMenu">
    <?php foreach ($links as $href => $label): ?>
      <a href="<?= APP_URL . $href ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <div class="mobile-cta">
      <a href="<?= APP_URL ?>/auth/login.php"    class="btn-defenza btn-secondary btn-sm" style="flex:1;justify-content:center;">Login</a>
      <a href="<?= APP_URL ?>/auth/register.php" class="btn-defenza btn-primary btn-sm"   style="flex:1;justify-content:center;">Get Started</a>
    </div>
  </div>
</header>
<?php } ?>
