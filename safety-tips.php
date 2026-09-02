<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

dashHead('Safety Tips');
dashOpen('Safety Tips', 'Safety Tips');

$tips = [
  ['fa-eye',              'linear-gradient(135deg,#c084fc,#7e22ce)', 'Stay Alert & Aware',         'Stay aware of your surroundings at all times. Trust your instincts — if something feels wrong, act on it immediately.'],
  ['fa-location-arrow',   'linear-gradient(135deg,#60a5fa,#4f46e5)', 'Share Your Location',        'Share your real-time location with trusted friends or family, especially when traveling to unfamiliar places.'],
  ['fa-triangle-exclamation','linear-gradient(135deg,#fb923c,#ef4444)','Avoid Isolated Places',    'Try to avoid walking alone in isolated or poorly lit areas, especially during late hours.'],
  ['fa-phone',            'linear-gradient(135deg,#34d399,#059669)', 'Keep Emergency Numbers',      'Always keep emergency contact numbers saved — Police (100), Women Helpline (1091), Ambulance (108).'],
  ['fa-users',            'linear-gradient(135deg,#f472b6,#f43f5e)', 'Travel in Groups',           'When possible, travel with trusted friends or colleagues, especially during nighttime hours.'],
  ['fa-moon',             'linear-gradient(135deg,#818cf8,#a855f7)', 'Nighttime Safety',           'Stay in well-lit areas at night. Inform someone about your whereabouts and expected return time.'],
  ['fa-wifi',             'linear-gradient(135deg,#38bdf8,#3b82f6)', 'Digital Safety',             'Be cautious about sharing personal information online. Use strong passwords and secure connections.'],
  ['fa-heart',            'linear-gradient(135deg,#fb7185,#ec4899)', 'Trust Your Instincts',       'Your gut feeling is your best safety tool. If a situation feels unsafe, remove yourself immediately.'],
];

$quickTips = [
  'Stay alert and aware of your surroundings',
  'Share your live location with trusted contacts',
  'Avoid isolated or unsafe places',
  'Keep emergency numbers saved',
  'Travel in groups when possible',
  'Trust your instincts and react quickly',
];
?>

<!-- Header card -->
<div style="background:var(--grad-main);border-radius:1rem;padding:1.5rem 2rem;margin-bottom:1.75rem;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
  <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.05);pointer-events:none;"></div>
  <div style="color:#fff;">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
      <i class="fa-solid fa-shield-check" style="color:rgba(249,168,212,.9);"></i>
      <span style="font-size:.8rem;font-weight:700;color:rgba(249,168,212,.9);">Safety First</span>
    </div>
    <h2 style="font-size:1.3rem;font-weight:900;margin-bottom:.25rem;">Safety Tips for Women</h2>
    <p style="color:rgba(221,214,254,.8);font-size:.83rem;max-width:500px;line-height:1.55;">
      Follow these essential safety tips to protect yourself in everyday situations. Knowledge is your first line of defense.
    </p>
  </div>
  <div style="width:4rem;height:4rem;border-radius:.875rem;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <i class="fa-solid fa-lightbulb" style="color:#fbbf24;font-size:1.75rem;"></i>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
  <!-- Tips grid -->
  <div>
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">Essential Safety Tips</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <?php foreach ($tips as [$icon,$grad,$title,$desc]): ?>
        <div class="tip-card">
          <div class="tip-icon" style="background:<?= $grad ?>;box-shadow:0 4px 12px rgba(0,0,0,.15);">
            <i class="fa-solid <?= $icon ?>"></i>
          </div>
          <div>
            <div class="tip-title"><?= $title ?></div>
            <div class="tip-desc"><?= $desc ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Sidebar -->
  <div style="display:flex;flex-direction:column;gap:1rem;">
    <!-- Quick reminders -->
    <div class="card-defenza no-hover" style="padding:1.25rem;">
      <h3 style="font-weight:900;font-size:.9rem;color:#111827;margin-bottom:1rem;">Quick Reminders</h3>
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($quickTips as $i => $tip): ?>
          <div style="display:flex;align-items:flex-start;gap:.625rem;">
            <div style="width:1.375rem;height:1.375rem;border-radius:50%;background:var(--grad-main);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;box-shadow:0 2px 6px rgba(168,85,247,.3);">
              <span style="color:#fff;font-size:.58rem;font-weight:900;"><?= $i+1 ?></span>
            </div>
            <p style="font-size:.825rem;color:#6b7280;line-height:1.5;"><?= $tip ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Emergency numbers -->
    <div style="background:var(--grad-main);border-radius:1rem;padding:1.25rem;color:#fff;">
      <h4 style="font-weight:900;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
        <i class="fa-solid fa-phone" style="color:rgba(249,168,212,.9);"></i>Emergency Numbers
      </h4>
      <?php foreach ([['Police','100'],['Women Helpline','1091'],['Ambulance','108'],['Fire Brigade','1122']] as [$l,$n]): ?>
        <div class="quick-number-row">
          <span class="quick-number-label"><?= $l ?></span>
          <span class="quick-number-val"><?= $n ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php dashClose(); dashFoot(); ?>
