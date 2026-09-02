<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

$displayName = e($user['full_name'] ?? '');
$email = e($user['email'] ?? '');
$phone = e($user['phone'] ?? '');
$contacts = e($user['emergency_contacts'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $emailValue = trim((string) ($_POST['email'] ?? ''));
        $phoneValue = trim((string) ($_POST['phone'] ?? ''));
        $contactsValue = trim((string) ($_POST['contacts'] ?? ''));

        $saved = false;
        if (($user['id'] ?? 0) > 0) {
            $saved = saveUserProfile((int) $user['id'], $fullName, $emailValue, $phoneValue, $contactsValue);
        }

        if ($saved) {
            $user = getAuthenticatedUser();
            $displayName = e($user['full_name'] ?? $fullName);
            $email = e($user['email'] ?? $emailValue);
            $phone = e($user['phone'] ?? $phoneValue);
            $contacts = e($user['emergency_contacts'] ?? $contactsValue);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'full_name' => (string) ($user['full_name'] ?? $fullName),
                'email' => (string) ($user['email'] ?? $emailValue),
                'phone' => (string) ($user['phone'] ?? $phoneValue),
                'contacts' => (string) ($user['emergency_contacts'] ?? $contactsValue),
            ]);
            exit;
        }

        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unable to save the profile.']);
        exit;
    }

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $phoneValue = trim((string) ($_POST['phone'] ?? ''));
    $contactsValue = trim((string) ($_POST['contacts'] ?? ''));

    if ($user['id'] ?? 0) {
        saveUserProfile((int) $user['id'], $fullName, $emailValue, $phoneValue, $contactsValue);
        $displayName = e($fullName);
        $email = e($emailValue);
        $phone = e($phoneValue);
        $contacts = e($contactsValue);
    }
}

dashHead('Profile');
dashOpen('Profile', 'Profile');
?>

<style>
.profile-grid { display: grid; gap: 1.25rem; grid-template-columns: 1.1fr .9fr; }
.profile-card { padding: 1.5rem; }
.profile-value { font-size: .95rem; font-weight: 700; color: #111827; margin-top: .2rem; }
.profile-label { font-size: .76rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; font-weight: 800; }
.profile-summary { display: grid; gap: .75rem; }
.profile-summary .profile-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .8rem 1rem; border-radius: 1rem; background: var(--purple-50); border: 1px solid rgba(168,85,247,.12); }
@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }
</style>

<div class="profile-grid">
  <div class="card-defenza no-hover profile-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
      <div>
        <div class="badge-pill badge-purple" style="margin-bottom:.5rem;"><i class="fa-solid fa-user"></i> Personal Profile</div>
        <h2 style="font-size:1.35rem;font-weight:900;color:#111827;">Your account details</h2>
      </div>
      <button id="editProfileBtn" type="button" class="btn-defenza btn-secondary">
        <i class="fa-solid fa-pen"></i> Edit / Update Profile
      </button>
    </div>

    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
      <div class="avatar-upload">
        <div class="avatar-upload-img" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;color:#fff;background:var(--grad-main);">
          <?= initials($user['full_name']) ?>
        </div>
      </div>
      <div>
        <div id="profileNameDisplay" style="font-weight:900;color:#111827;"><?= !empty($displayName) ? $displayName : 'Your profile' ?></div>
        <div style="font-size:.82rem;color:#9ca3af;margin-top:.25rem;">Keep your contact details updated for your safety network.</div>
      </div>
    </div>

    <form id="profileForm" method="post" style="display:grid;gap:1rem;" autocomplete="off">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input id="fullNameInput" name="full_name" type="text" class="form-control profile-field" placeholder="Enter your full name" disabled />
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input id="emailInput" name="email" type="email" class="form-control profile-field" placeholder="Enter your email address" disabled />
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input id="phoneInput" name="phone" type="tel" class="form-control profile-field" placeholder="Enter your phone number" disabled />
      </div>
      <div class="form-group">
        <label class="form-label">Emergency Contacts</label>
        <textarea id="contactsInput" name="contacts" class="form-control profile-field" rows="3" placeholder="Enter emergency contact details" disabled></textarea>
      </div>
      <div class="checkin-actions">
        <button id="saveProfileBtn" type="submit" class="btn-defenza btn-primary" disabled>
          <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>
      </div>
    </form>
  </div>

  <div class="card-defenza no-hover profile-card">
    <h3 style="font-weight:900;color:#111827;margin-bottom:1rem;">Profile Snapshot</h3>
    <div class="profile-summary">
      <div class="profile-row">
        <div>
          <div class="profile-label">Account Name</div>
          <div class="profile-value" id="snapshotName"><?= !empty($displayName) ? $displayName : 'Not provided' ?></div>
        </div>
        <i class="fa-solid fa-user-shield" style="color:var(--purple-500);"></i>
      </div>
      <div class="profile-row">
        <div>
          <div class="profile-label">Primary Email</div>
          <div class="profile-value" id="snapshotEmail"><?= !empty($email) ? $email : 'Not provided' ?></div>
        </div>
        <i class="fa-solid fa-envelope" style="color:var(--purple-500);"></i>
      </div>
      <div class="profile-row">
        <div>
          <div class="profile-label">Phone</div>
          <div class="profile-value" id="snapshotPhone"><?= !empty($phone) ? $phone : 'Not provided' ?></div>
        </div>
        <i class="fa-solid fa-phone" style="color:var(--purple-500);"></i>
      </div>
      <div class="profile-row">
        <div>
          <div class="profile-label">Support Contacts</div>
          <div class="profile-value" id="snapshotContacts"><?= !empty($contacts) ? $contacts : 'No contact details provided' ?></div>
        </div>
        <i class="fa-solid fa-users" style="color:var(--purple-500);"></i>
      </div>
    </div>
  </div>
</div>

<script>
const editProfileBtn = document.getElementById('editProfileBtn');
const saveProfileBtn = document.getElementById('saveProfileBtn');
const profileForm = document.getElementById('profileForm');
const profileInputs = Array.from(document.querySelectorAll('.profile-field'));
const profileNameDisplay = document.getElementById('profileNameDisplay');
const snapshotName = document.getElementById('snapshotName');
const snapshotEmail = document.getElementById('snapshotEmail');
const snapshotPhone = document.getElementById('snapshotPhone');
const snapshotContacts = document.getElementById('snapshotContacts');

editProfileBtn?.addEventListener('click', () => {
  profileInputs.forEach((field) => field.disabled = false);
  saveProfileBtn.disabled = false;
  document.getElementById('fullNameInput')?.focus();
});

profileForm?.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(profileForm);
  const fullName = (formData.get('full_name') || '').toString().trim();
  const email = (formData.get('email') || '').toString().trim();
  const phone = (formData.get('phone') || '').toString().trim();
  const contacts = (formData.get('contacts') || '').toString().trim();

  try {
    const response = await fetch(window.location.href, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Unable to save profile.');
    }

    profileNameDisplay.textContent = data.full_name || fullName || 'Your profile';
    snapshotName.textContent = data.full_name || fullName || 'Not provided';
    snapshotEmail.textContent = data.email || email || 'Not provided';
    snapshotPhone.textContent = data.phone || phone || 'Not provided';
    snapshotContacts.textContent = data.contacts || contacts || 'No contact details provided';

    profileInputs.forEach((field) => field.disabled = true);
    saveProfileBtn.disabled = true;

    if (typeof showToast === 'function') {
      showToast('Profile updated successfully.', 'success');
    }
  } catch (error) {
    if (typeof showToast === 'function') {
      showToast(error.message || 'Unable to save profile.', 'error');
    }
  }
});
</script>

<?php dashClose(); dashFoot(); ?>
