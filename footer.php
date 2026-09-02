<?php
// Public site footer
function renderFooter(): void {
    $year = date('Y');
?>
<footer class="site-footer" style="padding: 4rem 0 2rem;">
  <div class="footer-inner" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2.25rem 2rem; max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; align-items: start;">
    <!-- Brand -->
    <div>
      <div class="footer-brand-logo">
        <div class="footer-brand-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <span class="footer-brand-text"><?= APP_NAME ?></span>
          <span class="footer-brand-sub"><?= APP_TAGLINE ?></span>
        </div>
      </div>
      <p class="footer-desc" style="max-width: 360px; margin: 0; line-height: 1.7; color: #9ca3af; font-size: 0.92rem;">Empowering women with safety, security and real-time emergency support anytime, anywhere.</p>
    </div>

    <!-- Quick Links -->
    <div>
      <h4 class="footer-heading">Quick Links</h4>
      <div class="footer-links">
        <a href="<?= APP_URL ?>/">Home</a>
        <a href="<?= APP_URL ?>/#features">Features</a>
        <a href="<?= APP_URL ?>/pages/sos-alert.php">Emergency SOS</a>
        <a href="<?= APP_URL ?>/pages/about.php">About Us</a>
        <a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a>
      </div>
    </div>

    <!-- Important Links -->
    <div>
      <h4 class="footer-heading">Important Links</h4>
      <div class="footer-links" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <a href="javascript:void(0)" onclick="openDefenzaModal('privacy')" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9ca3af'">Privacy Policy</a>
        <a href="javascript:void(0)" onclick="openDefenzaModal('terms')" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9ca3af'">Terms &amp; Conditions</a>
        <a href="javascript:void(0)" onclick="openDefenzaModal('support')" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9ca3af'">Help &amp; Support</a>
        <a href="<?= APP_URL ?>/auth/register.php" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9ca3af'">Get Started</a>
      </div>
    </div>

    <!-- Why Defenza -->
    <div>
      <h4 class="footer-heading">Why Defenza</h4>
      <div style="display: flex; flex-direction: column; gap: 0.75rem; padding: 0.95rem 1rem; border: 1px solid rgba(255,255,255,0.08); border-radius: 0.95rem; background: linear-gradient(135deg, rgba(168,85,247,0.12), rgba(236,72,153,0.12)); backdrop-filter: blur(8px);">
        <div style="display: flex; align-items: flex-start; gap: 0.6rem; color: #f3f4f6; font-size: 0.92rem; line-height: 1.6;">
          <i class="fa-solid fa-bolt" style="color: #fbbf24; margin-top: 0.2rem;"></i>
          <span>Instant SOS and alert sharing in seconds.</span>
        </div>
        <div style="display: flex; align-items: flex-start; gap: 0.6rem; color: #f3f4f6; font-size: 0.92rem; line-height: 1.6;">
          <i class="fa-solid fa-location-dot" style="color: #fb7185; margin-top: 0.2rem;"></i>
          <span>Live location support for trusted contacts.</span>
        </div>
        <div style="display: flex; align-items: flex-start; gap: 0.6rem; color: #f3f4f6; font-size: 0.92rem; line-height: 1.6;">
          <i class="fa-solid fa-lock" style="color: #a78bfa; margin-top: 0.2rem;"></i>
          <span>Privacy-focused tools built for everyday safety.</span>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?= $year ?> <?= APP_NAME ?>. All Rights Reserved. Built with care for women's safety.</p>
  </div>
</footer>

<div id="defenzaFooterModal" style="display:none;position:fixed;inset:0;background:rgba(17,24,39,.7);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center;padding:1rem;">
  <div style="width:min(100%, 560px);max-height:85vh;overflow:auto;background:rgba(255,255,255,.96);color:#111827;border-radius:1.25rem;box-shadow:0 24px 60px rgba(0,0,0,.28);padding:2rem 2rem 1.5rem;position:relative;">
    <button type="button" onclick="closeDefenzaModal()" style="position:absolute;top:1rem;right:1rem;border:none;background:transparent;color:#6b7280;cursor:pointer;font-size:1.1rem;">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h3 id="defenzaModalTitle" style="font-size:1.2rem;font-weight:800;margin:0 0 .75rem;background:linear-gradient(135deg,#a855f7,#ec4899);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;"></h3>
    <div id="defenzaModalContent" style="font-size:.95rem;line-height:1.7;color:#4b5563;"></div>
  </div>
</div>

<script>
function openDefenzaModal(type) {
  const modal = document.getElementById('defenzaFooterModal');
  const title = document.getElementById('defenzaModalTitle');
  const content = document.getElementById('defenzaModalContent');

  const contentMap = {
    privacy: {
      title: 'Privacy Policy',
      html: '<p>At <strong>Defenza</strong>, your safety and privacy are treated as a top priority. We collect and process location information only when you trigger emergency support so trusted contacts can respond to your situation quickly.</p><p>Your private details, emergency contacts, and real-time location data are handled with care and are never shared with advertisers or unauthorized third parties.</p>'
    },
    terms: {
      title: 'Terms & Conditions',
      html: '<p><strong>Defenza</strong> is designed for personal safety protection and emergency support. Users are expected to use the SOS utility responsibly and avoid misuse or false alerts that could disrupt real emergency services.</p><p>The platform is intended to support personal protection tracking and does not assume liability beyond its intended emergency safety purpose.</p>'
    },
    support: {
      title: 'Help & Support',
      html: '<p>Need help with <strong>Defenza</strong>? Here are a few quick steps:</p><ul style="margin:.5rem 0 0 1rem;padding:0;display:grid;gap:.4rem;"><li>Add trusted emergency contacts from your dashboard.</li><li>Use the SOS button when you need immediate help.</li><li>Reach out to the support team at <a href="mailto:support@defenza.com" style="color:#a855f7;text-decoration:none;">support@defenza.com</a>.</li></ul>'
    }
  };

  if (contentMap[type]) {
    title.innerHTML = contentMap[type].title;
    content.innerHTML = contentMap[type].html;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeDefenzaModal() {
  const modal = document.getElementById('defenzaFooterModal');
  modal.style.display = 'none';
  document.body.style.overflow = '';
}

window.addEventListener('click', function (event) {
  const modal = document.getElementById('defenzaFooterModal');
  if (event.target === modal) {
    closeDefenzaModal();
  }
});
</script>
<?php } ?>
