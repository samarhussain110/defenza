<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/footer.php';

renderHead('About Us — ' . APP_NAME);
renderNavbar('About Us');
?>

<div class="page-wrap page-fade">

<!-- Hero -->
<section style="padding:7rem 0 4rem;background:var(--grad-soft);position:relative;overflow:hidden;">
  <div style="position:absolute;top:-80px;right:-80px;width:400px;height:400px;border-radius:50%;background:rgba(168,85,247,.1);filter:blur(80px);"></div>
  <div class="section-inner">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
      <div>
        <span class="section-badge">About Defenza</span>
        <h1 class="section-title">About <span class="gradient-text">Defenza</span></h1>
        <p style="color:#9ca3af;line-height:1.7;font-size:1.05rem;margin-bottom:1.75rem;">
          Defenza is a smart women safety emergency system that provides instant SOS alerts, live location tracking and emergency support. We are committed to empowering every woman to feel safe.
        </p>
        <ul style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:2rem;">
          <?php foreach (['Real-time SOS Alerts','Live Location Tracking','Emergency Contacts','24/7 Safety Support'] as $f): ?>
            <li style="display:flex;align-items:center;gap:.75rem;font-weight:600;color:#374151;">
              <i class="fa-solid fa-circle-check" style="color:var(--purple-500);"></i><?= $f ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
          <a href="<?= APP_URL ?>/auth/register.php" class="btn-defenza btn-primary btn-lg">Get Started</a>
          <a href="<?= APP_URL ?>/pages/contact.php" class="btn-defenza btn-secondary btn-lg">Contact Us</a>
        </div>
      </div>
      <div>
        <div style="background:#fff;border-radius:1.5rem;padding:2rem;box-shadow:0 16px 48px rgba(126,34,206,.12);border:1px solid var(--purple-100);">
          <div style="display:flex;align-items:flex-end;justify-content:center;gap:1rem;margin-bottom:1.5rem;">
            <?php foreach ([['A','linear-gradient(180deg,#a855f7,#7e22ce)','7rem'],['S','linear-gradient(180deg,#ec4899,#f43f5e)','8rem'],['F','linear-gradient(180deg,#fb923c,#f59e0b)','6rem'],['Z','linear-gradient(180deg,#34d399,#059669)','7rem']] as [$i,$g,$h]): ?>
              <div style="display:flex;flex-direction:column;align-items:center;gap:.5rem;">
                <div style="width:3.5rem;height:<?= $h ?>;border-radius:.875rem;background:<?= $g ?>;display:flex;align-items:flex-end;justify-content:center;padding-bottom:.75rem;box-shadow:0 6px 16px rgba(0,0,0,.12);">
                  <span style="color:#fff;font-weight:900;font-size:1.1rem;"><?= $i ?></span>
                </div>
                <div style="width:3.5rem;height:3.5rem;border-radius:50%;background:<?= $g ?>;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.1);">
                  <i class="fa-solid fa-shield-halved" style="color:#fff;font-size:1.1rem;"></i>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div style="background:var(--grad-main);border-radius:.875rem;padding:1rem;text-align:center;color:#fff;">
            <div style="font-weight:900;font-size:1rem;">Empowering Women</div>
            <div style="font-size:.75rem;color:rgba(221,214,254,.8);margin-top:.2rem;">with Safety, Security &amp; Real-time Support</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section style="padding:4rem 0;background:#fff;">
  <div class="section-inner">
    <div class="stats-grid-4">
      <?php foreach ([['10K+','Users Protected','fa-users','linear-gradient(135deg,#a855f7,#7e22ce)'],['25K+','SOS Alerts Sent','fa-shield-halved','linear-gradient(135deg,#ec4899,#f43f5e)'],['100+','Cities Covered','fa-globe','linear-gradient(135deg,#60a5fa,#4f46e5)'],['24/7','Support','fa-heart','linear-gradient(135deg,#34d399,#059669)']] as [$v,$l,$i,$g]): ?>
        <div class="stat-box">
          <div style="width:3rem;height:3rem;border-radius:.75rem;background:<?= $g ?>;display:flex;align-items:center;justify-content:center;margin:0 auto .875rem;box-shadow:var(--shadow-md);">
            <i class="fa-solid <?= $i ?>" style="color:#fff;font-size:1.1rem;"></i>
          </div>
          <div class="stat-box-value" style="background:<?= $g ?>;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?= $v ?></div>
          <div class="stat-box-label"><?= $l ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Mission -->
<section style="padding:4rem 0;background:var(--grad-soft);">
  <div class="section-inner" style="text-align:center;max-width:750px;margin:0 auto;">
    <span class="section-badge">Our Mission</span>
    <h2 class="section-title">Safety Without <span class="gradient-text">Compromise</span></h2>
    <p style="color:#9ca3af;line-height:1.7;font-size:1.05rem;">
      We believe every woman deserves to feel safe wherever she goes. Defenza was built with a single mission — to provide instant, reliable emergency support that every woman can trust. From real-time SOS alerts to live GPS tracking, we are with you every step of the way.
    </p>
  </div>
</section>

<!-- Features -->
<section style="padding:4rem 0;background:#fff;">
  <div class="section-inner">
    <div class="section-title-center" style="margin-bottom:2.5rem;">
      <h2 class="section-title">Key <span class="gradient-text">Safety Features</span></h2>
    </div>
    <div class="features-grid">
      <?php foreach ([
        ['fa-shield-halved','Real-time SOS Alerts','Instant emergency alerts sent to all your contacts with a single tap.'],
        ['fa-users','Live Location Tracking','Share your live GPS location with trusted contacts in real time.'],
        ['fa-star','Emergency Contacts','Manage and quickly reach your emergency support network.'],
        ['fa-award','24/7 Safety Support','Round-the-clock assistance and support for every woman.'],
      ] as [$i,$t,$d]): ?>
        <div class="feature-card">
          <div class="feature-icon" style="background:var(--grad-main);">
            <i class="fa-solid <?= $i ?>"></i>
          </div>
          <h3 class="feature-title"><?= $t ?></h3>
          <p class="feature-desc"><?= $d ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Team -->
<section style="padding:4rem 0;background:var(--grad-soft);">
  <div class="section-inner">
    <div class="section-title-center" style="margin-bottom:2.5rem;">
      <span class="section-badge">Our Team</span>
      <h2 class="section-title">Meet the <span class="gradient-text">Defenza Team</span></h2>
    </div>
    <div style="display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;max-width:1100px;margin:0 auto;">
      <?php foreach ([['Samar Hussain','Lead UI/UX Designer','SA'],['Aneesa','Product Designer & Safety Analyst','FR'],['Sajida','Quality Assurance (QA) & Specialist','AK']] as [$n,$r,$i]): ?>
        <div class="team-card">
          <div class="team-avatar"><?= $i ?></div>
          <div class="team-name"><?= $n ?></div>
          <div class="team-role"><?= $r ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

</div>
<?php renderFooter(); ?>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
