<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/footer.php';

renderHead('Contact Us — ' . APP_NAME);
renderNavbar('Contact');
?>

<div class="page-wrap page-fade">

<!-- Hero -->
<section style="padding:7rem 0 4rem;background:var(--grad-soft);text-align:center;position:relative;overflow:hidden;">
  <div style="position:absolute;top:-60px;right:-60px;width:350px;height:350px;border-radius:50%;background:rgba(168,85,247,.08);filter:blur(80px);"></div>
  <div class="section-inner">
    <span class="section-badge">Contact Us</span>
    <h1 class="section-title">We Are Here to <span class="gradient-text">Help You</span></h1>
    <p style="color:#9ca3af;font-size:1.05rem;max-width:500px;margin:0 auto;">
      Have a question or need support? Reach out to us and our team will get back to you as soon as possible.
    </p>
  </div>
</section>

<!-- Info cards + form -->
<section style="padding:3rem 0 1rem;background:#fff;">
  <div class="section-inner">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;margin-bottom:3.5rem;">
      <?php foreach ([
        ['fa-envelope','linear-gradient(135deg,#c084fc,#7e22ce)','Email','support@defenza.com','We reply within 2 hours'],
        ['fa-phone','linear-gradient(135deg,#ec4899,#f43f5e)','Phone','+92 300 1234567','Available 24/7 for emergencies'],
        ['fa-location-dot','linear-gradient(135deg,#60a5fa,#4f46e5)','Address','Karachi, Pakistan','Sindh, Pakistan'],
        ['fa-clock','linear-gradient(135deg,#34d399,#059669)','Support Hours','24/7 Available','Emergency line always open'],
      ] as [$i,$g,$t,$v,$s]): ?>
        <div class="contact-info-card">
          <div class="contact-info-icon" style="background:<?= $g ?>;"><i class="fa-solid <?= $i ?>"></i></div>
          <div class="contact-info-title"><?= $t ?></div>
          <div class="contact-info-val"><?= $v ?></div>
          <div class="contact-info-sub"><?= $s ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;">

      <!-- How Defenza Works card -->
      <div class="card-defenza no-hover" style="padding:2rem;">
        <div style="display:flex;align-items:center;gap:.875rem;margin-bottom:1.5rem;">
          <div style="width:2.5rem;height:2.5rem;border-radius:.75rem;background:var(--grad-main);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:var(--shadow-md);">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <div style="font-weight:900;color:#111827;">How Defenza Works</div>
            <div style="font-size:.75rem;color:#9ca3af;">Your safety, simplified in 4 steps</div>
          </div>
        </div>

        <div class="how-it-works-steps">
          <div class="how-it-works-step">
            <div class="how-it-works-icon-wrap">
              <div class="how-it-works-icon"><i class="fa-solid fa-user-plus"></i></div>
              <span class="how-it-works-number">01</span>
            </div>
            <div class="how-it-works-content">
              <h4>Create Your Account</h4>
              <p>Register securely using your email and basic information.</p>
            </div>
          </div>

          <div class="how-it-works-step">
            <div class="how-it-works-icon-wrap">
              <div class="how-it-works-icon"><i class="fa-solid fa-users"></i></div>
              <span class="how-it-works-number">02</span>
            </div>
            <div class="how-it-works-content">
              <h4>Add Emergency Contacts</h4>
              <p>Save trusted family members or friends who will receive emergency alerts.</p>
            </div>
          </div>

          <div class="how-it-works-step">
            <div class="how-it-works-icon-wrap">
              <div class="how-it-works-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
              <span class="how-it-works-number">03</span>
            </div>
            <div class="how-it-works-content">
              <h4>Press SOS</h4>
              <p>In an emergency, tap the SOS button to instantly send your live location.</p>
            </div>
          </div>

          <div class="how-it-works-step">
            <div class="how-it-works-icon-wrap">
              <div class="how-it-works-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
              <span class="how-it-works-number">04</span>
            </div>
            <div class="how-it-works-content">
              <h4>Get Immediate Help</h4>
              <p>Emergency contacts and nearby responders receive your alert for quick assistance.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Map + Emergency numbers -->
      <div style="display:flex;flex-direction:column;gap:1.25rem;">
        <div class="card-defenza no-hover" style="padding:1.5rem;">
          <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
            <div style="width:2.25rem;height:2.25rem;border-radius:.5rem;background:rgba(168,85,247,0.1);display:flex;align-items:center;justify-content:center;color:var(--purple-500);">
              <i class="fa-solid fa-user-shield" style="font-size:1.1rem;"></i>
            </div>
            <h4 style="font-weight:900;color:#111827;font-size:.95rem;margin:0;">Your Privacy, Our Priority</h4>
          </div>
          <p style="color:#6b7280;font-size:.85rem;line-height:1.6;margin:0 0 1rem 0;">
            Defenza uses end-to-end data encryption to secure your emergency actions. Your coordinates are tracked strictly during an active SOS trigger and are never shared with anyone else.
          </p>
          <div style="display:flex;flex-direction:column;gap:.5rem;font-size:.8rem;font-weight:600;color:#4b5563;">
            <div style="display:flex;align-items:center;gap:.5rem;">
              <span style="color:#34d399;">🔒</span> Zero third-party data tracking
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <span style="color:#34d399;">⚡</span> Instant alert relay infrastructure
            </div>
          </div>
        </div>

        <div style="background:var(--grad-main);border-radius:1rem;padding:1.5rem;color:#fff;">
          <h3 style="font-weight:900;font-size:1.05rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
            <i class="fa-solid fa-phone" style="color:rgba(249,168,212,.9);"></i>Emergency? Call Now
          </h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.625rem;">
            <?php foreach ([['Police','100'],['Women Helpline','1091'],['Ambulance','108'],['Fire Brigade','1122']] as [$l,$n]): ?>
              <a href="tel:<?= $n ?>" style="background:rgba(255,255,255,.12);border-radius:.75rem;padding:.875rem;text-align:center;display:block;text-decoration:none;transition:background .2s;" onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <div style="font-weight:900;font-size:1.5rem;color:#fff;"><?= $n ?></div>
                <div style="font-size:.72rem;color:rgba(221,214,254,.8);margin-top:.15rem;"><?= $l ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

</div>
<?php renderFooter(); ?>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body></html>
