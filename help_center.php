<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

$formErrors = [];
$formName = '';
$formEmail = '';
$formMessage = '';
$showSupportSuccess = isset($_GET['support_sent']) && $_GET['support_sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formName = trim((string) ($_POST['support_name'] ?? ''));
    $formEmail = trim((string) ($_POST['support_email'] ?? ''));
    $formMessage = trim((string) ($_POST['support_message'] ?? ''));

    $userId = (int) ($user['id'] ?? 0);

    if ($formName === '') {
        $formErrors[] = 'Please enter your name.';
    } elseif (strlen($formName) > 100) {
        $formErrors[] = 'Name must be 100 characters or less.';
    }

    if ($formEmail === '') {
        $formErrors[] = 'Please enter your email address.';
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Please enter a valid email address.';
    }

    if ($formMessage === '') {
        $formErrors[] = 'Please enter your message.';
    } elseif (strlen($formMessage) > 2000) {
        $formErrors[] = 'Message must be 2000 characters or less.';
    }

    if ($userId <= 0) {
        $formErrors[] = 'You must be signed in to send support information.';
    }

    if ($formErrors === []) {
        $saved = saveSupportMessage($userId, $formName, $formEmail, $formMessage);
        if ($saved) {
            try {
                $aiReply = generateAiSupportReply($formMessage, $formName);
                sendSupportConfirmationEmail($formEmail, $formName, $formMessage, $aiReply);
            } catch (Throwable $e) {
                error_log('[help_center.php] confirmation email failed: ' . $e->getMessage());
            }

            header('Location: ' . APP_URL . '/pages/help_center.php?support_sent=1');
            exit();
        }

        $formErrors[] = 'Something went wrong, try again.';
    }
}

dashHead('Help Center');
dashOpen('Help Center', 'Help Center');
?>

<style>
.help-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr 1fr; }
.help-card { padding: 1.5rem; }
.faq-item { border: 1px solid rgba(168,85,247,.12); border-radius: 1rem; background: rgba(250,245,255,.65); overflow: hidden; margin-bottom: .75rem; }
.faq-question { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .75rem; background: none; border: none; cursor: pointer; font-weight: 800; color: #111827; padding: 1rem 1.1rem; text-align: left; font-family: var(--font); }
.faq-answer { display: none; padding: 0 1.1rem 1rem; color: #6b7280; font-size: .9rem; line-height: 1.6; }
.faq-item.open .faq-answer { display: block; }
.helpline-list { display: grid; gap: .75rem; margin-top: 1rem; }
.helpline-item { padding: 1rem; border-radius: 1rem; background: linear-gradient(135deg, rgba(168,85,247,.08), rgba(244,114,182,.08)); border: 1px solid rgba(168,85,247,.12); }
.guide-step { display: flex; gap: .75rem; padding: .85rem 0; border-bottom: 1px solid rgba(168,85,247,.08); }
.guide-step:last-child { border-bottom: none; }
.guide-number { width: 2rem; height: 2rem; border-radius: 50%; background: var(--grad-main); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .8rem; flex-shrink: 0; }
@media (max-width: 900px) { .help-grid { grid-template-columns: 1fr; } }
</style>

<div style="margin-bottom:1.25rem;">
  <div class="card-defenza no-hover" style="padding:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
      <div>
        <div class="badge-pill badge-purple" style="margin-bottom:.5rem;"><i class="fa-solid fa-circle-question"></i> Help Center</div>
        <h2 style="font-size:1.45rem;font-weight:900;color:#111827;">Support resources when you need them most</h2>
        <p style="color:#6b7280;margin-top:.35rem;max-width:640px;">Browse quick answers, emergency contacts, and simple guidance for using the app confidently.</p>
      </div>
      <div class="alert-box alert-danger" style="margin-bottom:0;max-width:320px;">
        <i class="fa-solid fa-phone-volume"></i>
        <span>In immediate danger? Contact local emergency services right away.</span>
      </div>
    </div>
  </div>
</div>

<div class="help-grid">
  <div class="card-defenza no-hover help-card">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">Emergency Helpline Numbers</h3>
    <div class="helpline-list">
      <div class="helpline-item">
        <div style="font-size:.75rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Police</div>
        <div style="font-size:1.1rem;font-weight:900;color:#111827;margin-top:.2rem;">100</div>
      </div>
      <div class="helpline-item">
        <div style="font-size:.75rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Women Helpline</div>
        <div style="font-size:1.1rem;font-weight:900;color:#111827;margin-top:.2rem;">1091</div>
      </div>
      <div class="helpline-item">
        <div style="font-size:.75rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Ambulance</div>
        <div style="font-size:1.1rem;font-weight:900;color:#111827;margin-top:.2rem;">102</div>
      </div>
    </div>
  </div>

  <div class="card-defenza no-hover help-card">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">Frequently Asked Questions</h3>
    <div class="faq-item open">
      <button class="faq-question" type="button">
        <span>How do I trigger an SOS alert?</span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="faq-answer">Open the SOS page and tap the large emergency button to send your alert to your saved contacts instantly.</div>
    </div>
    <div class="faq-item">
      <button class="faq-question" type="button">
        <span>Can I share my live location?</span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="faq-answer">Yes. The live location screen lets you share your current position with trusted contacts in just a few taps.</div>
    </div>
    <div class="faq-item">
      <button class="faq-question" type="button">
        <span>How does the check-in timer work?</span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="faq-answer">Add a destination and arrival time, then start the timer. The app will keep the countdown visible until you confirm you're safe.</div>
    </div>
  </div>
</div>

<div class="help-grid" style="margin-top:1.25rem;">
  <div class="card-defenza no-hover help-card">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">User Guide</h3>
    <div class="guide-step">
      <div class="guide-number">1</div>
      <div>
        <div style="font-weight:800;color:#111827;">Open the app dashboard</div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.2rem;">Review your safety status and quick access shortcuts from the main screen.</div>
      </div>
    </div>
    <div class="guide-step">
      <div class="guide-number">2</div>
      <div>
        <div style="font-weight:800;color:#111827;">Use the safety tools</div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.2rem;">Start a check-in, view your live location, or alert contacts when needed.</div>
      </div>
    </div>
    <div class="guide-step">
      <div class="guide-number">3</div>
      <div>
        <div style="font-weight:800;color:#111827;">Keep your profile updated</div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.2rem;">Update your phone number and emergency details so help can reach you quickly.</div>
      </div>
    </div>
  </div>

  <div class="card-defenza no-hover help-card">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">Support Information</h3>
    <p style="color:#6b7280;margin-bottom:1rem;">For manual queries, contact our support team or leave a short note below.</p>
    <?php if (!empty($formErrors)): ?>
      <div class="alert-box alert-danger" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">
          <?php foreach ($formErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form id="supportForm" method="post" style="display:grid;gap:1rem;" autocomplete="off">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" name="support_name" class="form-control" placeholder="Enter your full name" required value="<?= e($formName) ?>" />
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="support_email" class="form-control" placeholder="Enter your email address" required value="<?= e($formEmail) ?>" />
      </div>
      <div class="form-group">
        <label class="form-label">How can we help?</label>
        <textarea name="support_message" class="form-control" rows="4" placeholder="Enter your message or question" required><?= e($formMessage) ?></textarea>
      </div>
      <button type="submit" class="btn-defenza btn-primary">
        <i class="fa-solid fa-paper-plane"></i> Send Inquiry
      </button>
    </form>
  </div>
</div>

<script>
<?php if ($showSupportSuccess): ?>
document.addEventListener('DOMContentLoaded', function () {
  showToast('Your request has been received. Our support team will get back to you shortly.', 'success');
  const supportForm = document.getElementById('supportForm');
  if (supportForm) {
    supportForm.reset();
  }
  if (window.history && window.history.replaceState) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
<?php endif; ?>
window.addEventListener('pageshow', function () {
  const forms = document.querySelectorAll('form');
  forms.forEach((form) => form.reset());
});

window.addEventListener('beforeunload', function () {
  const forms = document.querySelectorAll('form');
  forms.forEach((form) => form.reset());
});

document.querySelectorAll('.faq-item').forEach((item) => {
  item.querySelector('.faq-question').addEventListener('click', () => {
    document.querySelectorAll('.faq-item').forEach((entry) => {
      entry.classList.remove('open');
    });
    item.classList.add('open');
  });
});

</script>

<?php dashClose(); dashFoot(); ?>
