<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php';

renderHead('Defenza — Women Safety Emergency System');
renderNavbar('Home');
?>

<div class="page-wrap page-fade">

  <!-- ── HERO ────────────────────────────────────────────────── -->
  <section class="hero-section" id="home">
    <div class="section-inner" style="width:100%;position:relative;z-index:1;">
      <div class="hero-grid">
        <!-- Left: Copy -->
        <div>
          <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            BE SAFE, BE SECURE
          </div>
          <h1 class="hero-title">
            Your Safety <span class="gradient-text">Our Priority</span>
          </h1>
          <p class="hero-desc">
            Instant SOS alerts, live location sharing and emergency support for women safety —
            designed to keep you protected anytime, anywhere.
          </p>
          <div class="hero-cta">
            <a href="<?= APP_URL ?>/pages/sos-alert.php" class="btn-defenza btn-primary btn-lg">
              <i class="fa-solid fa-triangle-exclamation"></i> Send SOS
            </a>
            <a href="<?= APP_URL ?>/pages/live-location.php" class="btn-defenza btn-secondary btn-lg">
              <i class="fa-solid fa-location-dot"></i> Live Tracking
            </a>
          </div>
          <div class="hero-stats">
            <?php foreach ([['10K+', 'Women Protected'], ['25K+', 'SOS Alerts Sent'], ['24/7', 'Support']] as [$v, $l]): ?>
              <div>
                <div class="hero-stat-value gradient-text fw-black"><?= $v ?></div>
                <div class="hero-stat-label"><?= $l ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right: Phone Mockup -->
        <div class="phone-wrap">
          <div class="phone-mockup">
            <div class="phone-statusbar">
              <span>9:41</span>
              <div class="phone-notch"></div>
              <i class="fa-solid fa-signal" style="font-size:.6rem;color:rgba(255,255,255,.6);"></i>
            </div>
            <div class="phone-app-header">
              <i class="fa-solid fa-shield-halved" style="color:var(--pink-400);font-size:.8rem;"></i>
              <span class="phone-app-name">Defenza</span>
            </div>
            <div class="phone-sos-area">
              <p class="phone-sos-hint">Tap to Send Alert</p>
              <button class="phone-sos-btn">
                <span class="phone-sos-text">SOS</span>
                <span class="phone-sos-sub">SEND ALERT</span>
              </button>
              <p style="font-size:.58rem;color:rgba(196,181,253,.7);text-align:center;padding:0 1rem;">We will notify your contacts</p>
              <div class="phone-location">
                <span class="phone-loc-dot"></span>
                <span class="phone-loc-text">Location: Islamabad, PK</span>
              </div>
            </div>
            <div class="phone-bottom-nav">
              <div class="phone-nav-icon active"><i class="fa-solid fa-shield-halved"></i></div>
              <div class="phone-nav-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div class="phone-nav-icon"><i class="fa-solid fa-phone"></i></div>
              <div class="phone-nav-icon"><i class="fa-solid fa-bell"></i></div>
            </div>
          </div>

          <!-- Floating cards -->
          <div class="phone-float-card right">
            <div class="float-icon" style="background:#dcfce7;"><i class="fa-solid fa-circle-check" style="color:#16a34a;"></i></div>
            <div>
              <div class="float-title">Alert Sent!</div>
              <div class="float-sub">3 contacts notified</div>
            </div>
          </div>
          <div class="phone-float-card left">
            <div class="float-icon" style="background:#dbeafe;"><i class="fa-solid fa-location-dot" style="color:#2563eb;"></i></div>
            <div>
              <div class="float-title">Live Tracking</div>
              <div class="float-sub">Active</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── FEATURES ─────────────────────────────────────────────── -->
  <section id="features" style="padding:5rem 0;background:#fff;">
    <div class="section-inner">
      <div class="section-title-center" style="margin-bottom:3.5rem;">
        <span class="section-badge">Features</span>
        <h2 class="section-title">Everything You Need to <span class="gradient-text">Stay Safe</span></h2>
      </div>
      <div class="features-grid">
        <?php
        $features = [
          ['fa-triangle-exclamation', 'from-red', 'Emergency SOS', 'Send instant SOS alerts to all your emergency contacts with a single tap.'],
          ['fa-location-dot', 'from-blue', 'Live Location', 'Share your real-time location with trusted contacts instantly.'],
          ['fa-phone', 'from-green', 'Emergency Contacts', 'Add and manage your emergency contacts with ease.'],
          ['fa-bell', 'from-purple', 'Safety Alerts', 'Get notified about safety tips and nearby danger zones.'],
        ];
        $gradients = ['from-red' => 'linear-gradient(135deg,#f87171,#ef4444)', 'from-blue' => 'linear-gradient(135deg,#60a5fa,#4f46e5)', 'from-green' => 'linear-gradient(135deg,#34d399,#059669)', 'from-purple' => 'linear-gradient(135deg,#c084fc,#ec4899)'];
        foreach ($features as [$icon, $grad, $title, $desc]): ?>
          <div class="feature-card">
            <div class="feature-icon" style="background:<?= $gradients[$grad] ?>;margin:0 auto 1rem;">
              <i class="fa-solid <?= $icon ?>"></i>
            </div>
            <h3 class="feature-title"><?= $title ?></h3>
            <p class="feature-desc"><?= $desc ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ── EMERGENCY BANNER ──────────────────────────────────────── -->
  <section style="padding:0 0 2.5rem;">
    <div class="section-inner">
      <div class="emergency-banner">
        <div style="display:flex;align-items:center;gap:1rem;">
          <div class="banner-icon-wrap"><i class="fa-solid fa-tower-broadcast"></i></div>
          <div class="banner-text">
            <h4>In Emergency? Don't Panic!</h4>
            <p>Press the SOS button and we will notify your trusted contacts immediately.</p>
          </div>
        </div>
        <a href="<?= APP_URL ?>/pages/sos-alert.php" class="btn-defenza btn-outline-white">
          SOS <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- ── ABOUT SNIPPET ─────────────────────────────────────────── -->
  <section style="padding:5rem 0;background:var(--grad-soft);">
    <div class="section-inner">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
        <div>
          <span class="section-badge">About Defenza</span>
          <h2 class="section-title">A Safety Platform Built <span class="gradient-text">For Women</span></h2>
          <p style="color:#9ca3af;line-height:1.7;margin-bottom:1.5rem;">
            Defenza is a smart women safety emergency system that provides instant SOS alerts, live location tracking and
            emergency support. We believe every woman deserves to feel safe.
          </p>
          <ul style="list-style:none;display:flex;flex-direction:column;gap:.75rem;margin-bottom:2rem;">
            <?php foreach (['Real-time SOS Alerts', 'Live Location Tracking', 'Emergency Contacts', '24/7 Safety Support'] as $f): ?>
              <li style="display:flex;align-items:center;gap:.75rem;font-weight:600;color:#374151;">
                <i class="fa-solid fa-circle-check" style="color:var(--purple-500);"></i><?= $f ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= APP_URL ?>/pages/about.php" class="btn-defenza btn-primary">
            Learn More <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div class="stats-grid-4" style="grid-template-columns:1fr 1fr;">
          <?php foreach ([['10K+', 'Users Protected', 'linear-gradient(135deg,#a855f7,#7e22ce)'], ['25K+', 'SOS Alerts', 'linear-gradient(135deg,#ec4899,#f43f5e)'], ['100+', 'Cities Covered', 'linear-gradient(135deg,#60a5fa,#4f46e5)'], ['24/7', 'Support', 'linear-gradient(135deg,#34d399,#059669)']] as [$v, $l, $g]): ?>
            <div class="stat-box">
              <div class="stat-box-value" style="background:<?= $g ?>;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?= $v ?></div>
              <div class="stat-box-label"><?= $l ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ── TESTIMONIALS ──────────────────────────────────────────── -->
  <section style="padding:5rem 0;background:#fff;">
    <div class="section-inner">
      <div class="section-title-center" style="margin-bottom:3rem;">
        <span class="section-badge">Testimonials</span>
        <h2 class="section-title">Trusted by <span class="gradient-text">Women Everywhere</span></h2>
      </div>
      <div class="features-grid">
        <?php foreach (
          [
            ['Fatima N.', 'Student', 'Defenza gave me confidence to travel alone. The SOS button is a lifesaver!'],
            ['Sara K.', 'Working Professional', 'The live location feature keeps my family informed when I work late nights.'],
            ['Ayesha M.', 'Teacher', 'Simple, fast and reliable. Every woman should have this app on her phone.'],
            ['Zainab R.', 'University Student', 'The community alerts and instant emergency integration make me feel so much safer walking back from evening classes.'],
          ] as [$name, $role, $text]
        ): ?>
          <div class="feature-card" style="text-align:left;">
            <div style="display:flex;gap:.2rem;margin-bottom:.75rem;">
              <?php for ($i = 0; $i < 5; $i++) echo '<i class="fa-solid fa-star" style="color:#fbbf24;font-size:.8rem;"></i>'; ?>
            </div>
            <p style="color:#6b7280;font-size:.85rem;line-height:1.6;margin-bottom:1rem;">"<?= $text ?>"</p>
            <div style="display:flex;align-items:center;gap:.75rem;">
              <div style="width:36px;height:36px;border-radius:50%;background:var(--grad-main);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.85rem;"><?= $name[0] ?></div>
              <div>
                <div style="font-weight:800;font-size:.875rem;color:#111827;"><?= $name ?></div>
                <div style="font-size:.72rem;color:#9ca3af;"><?= $role ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</div><!-- /.page-wrap -->

<?php renderFooter(); ?>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>