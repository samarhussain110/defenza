<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();
$settings = [];

if (($user['id'] ?? 0) > 0) {
    $settings = getUserSettings((int) $user['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsValues = [
        'share_location' => isset($_POST['share_location']) ? 1 : 0,
        'anonymous_reports' => isset($_POST['anonymous_reports']) ? 1 : 0,
        'location_history' => isset($_POST['location_history']) ? 1 : 0,
        'language' => trim($_POST['language'] ?? $_POST['app_language'] ?? 'English'),
    ];

    if (($user['id'] ?? 0) > 0) {
        saveUserSettings((int) $user['id'], $settingsValues);
    }
}

$shareLocationChecked = !empty($settings['share_location']);
$locationHistoryChecked = !empty($settings['location_history']);
$selectedLanguage = trim((string) ($settings['language'] ?? 'English'));

dashHead('Settings');
dashOpen('Settings', 'Settings');
?>

<div style="max-width:860px;">
  <div class="card-defenza no-hover" style="padding:1.25rem 1.5rem;margin-bottom:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
      <div>
        <h2 style="font-size:1.3rem;font-weight:900;color:#111827;margin-bottom:.3rem;">Account Preferences</h2>
        <p style="color:#6b7280;font-size:.9rem;">Manage alerts, privacy, and app language from one place.</p>
      </div>
      <div class="badge-pill badge-purple"><i class="fa-solid fa-sliders"></i> UI Settings</div>
    </div>
  </div>

  <div class="settings-tabs">
    <button class="tab-btn active" onclick="switchTab('security')">Security</button>
  </div>

  <div class="tab-pane active" id="tab-security">
    <div class="card-defenza no-hover" style="padding:1.75rem;">
      <h3 style="font-weight:900;color:#111827;margin-bottom:1.5rem;display:flex;align-items:center;gap:.625rem;">
        <i class="fa-solid fa-lock" style="color:var(--purple-500);"></i>Change Password
      </h3>
      <form id="passwordForm" method="post" style="display:grid;gap:1rem;max-width:440px;" autocomplete="off">
        <?php foreach ([['oldPass','Current Password'],['newPass','New Password'],['conPass','Confirm New Password']] as [$id,$label]): ?>
          <div class="form-group">
            <label class="form-label"><?= $label ?></label>
            <div class="input-wrap" style="position:relative;">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" id="<?= $id ?>" name="<?= $id ?>" class="form-control has-icon" placeholder="••••••••" value="" />
              <button type="button" class="pass-toggle" onclick="togglePass('<?= $id ?>',this)"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
        <?php endforeach; ?>
        <div style="background:var(--purple-50);border-radius:.75rem;padding:.875rem;font-size:.8rem;color:var(--purple-700);">
          <strong>Password Requirements:</strong>
          <ul style="margin-top:.375rem;padding-left:1rem;color:var(--purple-600);">
            <li>At least 8 characters long</li>
          </ul>
        </div>
        <button type="submit" class="btn-defenza btn-primary" style="justify-self:start;padding:.65rem 1.75rem;">
          <i class="fa-solid fa-floppy-disk"></i> Update Password
        </button>
      </form>
    </div>
  </div>

  <div class="card-defenza no-hover" style="padding:1.75rem; margin-top:1rem;">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1.5rem;display:flex;align-items:center;gap:.625rem;">
      <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i>Danger Zone
    </h3>
    <p style="font-size:.78rem;color:#ef4444;margin-bottom:.875rem;">These actions are permanent and cannot be undone.</p>
    <button type="button" id="deleteAccountTrigger" class="btn-defenza" style="background:#dc2626; color:#fff; border:1px solid #dc2626; font-size:.83rem; font-weight:700; padding:.5rem 1.25rem; border-radius:0.5rem; cursor:pointer;">
      <i class="fa-solid fa-trash-can"></i> Delete Account
    </button>
  </div>
</div>

<div id="deleteConfirmModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-box" style="max-width:400px; background:#fff; border-radius:1rem; padding:1.75rem; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin: 0 1rem;">
    <div style="width:56px;height:56px;border-radius:50%;background:#fef2f2;display:inline-flex;align-items:center;justify-content:center;color:#dc2626;font-size:1.5rem;margin-bottom:1rem;">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <h3 style="font-size:1.2rem;font-weight:900;color:#111827;margin-bottom:.5rem;">Are you absolutely sure?</h3>
    <p style="color:#6b7280;font-size:.85rem;line-height:1.4;margin-bottom:1.5rem;">This action is permanent. Deleting your account will remove your personal data, emergency contacts, and journey history forever.</p>
    <div style="display:flex; gap:.75rem;">
      <button id="cancelDeleteBtn" type="button" class="btn-defenza" style="flex:1; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.5rem;">Cancel</button>
      <button id="confirmDeleteBtn" type="button" class="btn-defenza" style="flex:1; background:#dc2626; color:#fff; border:1px solid #dc2626; border-radius:0.5rem; padding:0.5rem;">Yes, Delete</button>
    </div>
  </div>
</div>

<script>
window.addEventListener('pageshow', function () {
  const forms = document.querySelectorAll('form');
  forms.forEach((form) => form.reset());
});

window.addEventListener('beforeunload', function () {
  const forms = document.querySelectorAll('form');
  forms.forEach((form) => form.reset());
});

function switchTab(tab) {
  // Remove active state from all tab buttons and panes
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  
  // Find the clicked tab button dynamically using its text content or selector mapping
  const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick')?.includes(`'${tab}'`));
  const activePane = document.getElementById('tab-' + tab);
  
  // Apply active class smoothly to target elements if they exist
  if (activeBtn) activeBtn.classList.add('active');
  if (activePane) activePane.classList.add('active');
  
  // Update state history cleanly without forced page reloads
  history.pushState(null, '', '?tab=' + tab);
}

async function submitSettingsForm(form, successMessage) {
  if (!form) return;
  const submitButton = form.querySelector('button[type="submit"]');
  const originalLabel = submitButton?.innerHTML || '';
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
  }

  try {
    const response = await fetch(window.location.href, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!response.ok) {
      throw new Error('Unable to save settings right now.');
    }

    showToast(successMessage, 'success');
    setTimeout(() => window.location.reload(), 600);
  } catch (error) {
    showToast(error.message || 'Unable to save settings right now.', 'error');
  } finally {
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalLabel;
    }
  }
}

document.getElementById('languageForm')?.addEventListener('submit', function (e) {
  e.preventDefault();
  const selectedLang = document.querySelector('input[name="app_language"]:checked')?.value;
  if (!selectedLang) {
    showToast('Please select a valid language.', 'error');
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.style.display = 'none';

  const hidden = document.createElement('input');
  hidden.type = 'hidden';
  hidden.name = 'language';
  hidden.value = selectedLang;
  form.appendChild(hidden);

  document.body.appendChild(form);
  submitSettingsForm(form, `Language updated successfully to ${selectedLang}!`);
  document.body.removeChild(form);
});

const passwordForm = document.getElementById('passwordForm');
passwordForm?.addEventListener('submit', async function (e) {
  e.preventDefault();

  const oldPass = document.getElementById('oldPass')?.value || '';
  const newPass = document.getElementById('newPass')?.value || '';
  const conPass = document.getElementById('conPass')?.value || '';

  if (!oldPass || !newPass || !conPass) {
    showToast('Please fill in all password fields.', 'error');
    return;
  }

  if (newPass !== conPass) {
    showToast('New passwords do not match.', 'error');
    return;
  }

  const submitButton = passwordForm.querySelector('button[type="submit"]');
  const originalLabel = submitButton?.innerHTML || '';
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 10000);

  try {
    const response = await fetch('../api/change_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ oldPass, newPass, conPass }),
      signal: controller.signal
    });

    const data = await response.json().catch(() => null);
    if (!response.ok || data?.success !== true) {
      throw new Error(data?.message || 'Unable to update password right now.');
    }

    passwordForm.reset();
    showToast(data.message || 'Password updated successfully!', 'success');
  } catch (error) {
    const message = error.name === 'AbortError'
      ? 'Request timed out. Please try again.'
      : (error.message || 'Unable to update password right now.');
    showToast(message, 'error');
  } finally {
    clearTimeout(timeoutId);
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalLabel;
    }
  }
});

const deleteModal = document.getElementById('deleteConfirmModal');
const triggerBtn = document.getElementById('deleteAccountTrigger');
const cancelBtn = document.getElementById('cancelDeleteBtn');
const confirmBtn = document.getElementById('confirmDeleteBtn');

triggerBtn?.addEventListener('click', () => {
  deleteModal.style.display = 'flex';
});

cancelBtn?.addEventListener('click', () => {
  deleteModal.style.display = 'none';
});

confirmBtn?.addEventListener('click', async () => {
  deleteModal.style.display = 'none';
  showToast('Deleting your account...', 'info');

  try {
    const response = await fetch('../api/delete_account.php', { method: 'POST' });
    const data = await response.json().catch(() => null);
    if (!response.ok || !data?.success) {
      throw new Error(data?.message || 'Unable to delete your account right now.');
    }

    showToast(data.message || 'Account deleted successfully. Logging out...', 'success');
    setTimeout(() => window.location.href = '../auth/login.php', 1500);
  } catch (error) {
    showToast(error.message || 'Unable to delete your account right now.', 'error');
  }
});
</script>

<?php dashClose(); dashFoot(); ?>
