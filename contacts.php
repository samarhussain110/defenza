<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();
$contacts      = getUserContacts();
$relationships = ['Father','Mother','Sister','Brother','Husband','Friend','Colleague','Other'];

dashHead('Emergency Contacts');
dashOpen('Emergency Contacts', 'Emergency Contacts');
?>

<div id="contactsPage" data-api-url="<?= rtrim(parse_url(APP_URL, PHP_URL_PATH), '/') ?>/api/contacts.php" style="margin-top:1rem;">
  <!-- Page Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2rem;gap:1rem;flex-wrap:wrap;">
    <div>
      <h2 style="font-size:1.5rem;font-weight:900;color:#111827;margin-bottom:.5rem;">Emergency Contacts</h2>
      <p style="color:#6b7280;font-size:.95rem;">Add trusted contacts to receive instant alerts during emergencies. There is no limit on how many people you can add.</p>
    </div>
    <button onclick="openAddContactForm()" class="btn-defenza btn-primary" style="align-self:flex-start;">
      <i class="fa-solid fa-plus"></i> Add Contact
    </button>
  </div>

  <!-- Alert -->
  <div class="alert-box alert-info" style="margin-bottom:1.5rem;">
    <i class="fa-solid fa-circle-info"></i>
    <span>These contacts will be notified instantly via SMS and phone call during emergencies. Add as many trusted contacts as you need and keep numbers accurate.</span>
  </div>

  <!-- Add/Edit Form (Initially Hidden) -->
  <div id="contactFormCard" class="voice-section" style="display:none;margin-bottom:2rem;border:1px solid rgba(99,102,241,.2);background:linear-gradient(180deg,rgba(99,102,241,.04),rgba(99,102,241,.02));">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h3 id="formTitle" style="margin:0;font-size:1.1rem;font-weight:900;">Add New Contact</h3>
      <button type="button" onclick="closeContactForm()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#6b7280;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form id="contactForm" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
      <input type="hidden" id="contactId" value="0" />
      
      <div class="form-group" style="grid-column:1/-1;">
        <label class="form-label">Full Name <span style="color:#ef4444;">*</span></label>
        <div class="input-wrap">
          <i class="fa-solid fa-user input-icon"></i>
          <input type="text" id="contactName" name="name" class="form-control has-icon" placeholder="e.g., Sarah Khan" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Relationship <span style="color:#ef4444;">*</span></label>
        <select id="contactRelationship" name="relationship" class="form-select" required>
          <option value="">Select relationship</option>
          <?php foreach ($relationships as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number <span style="color:#ef4444;">*</span></label>
        <div class="input-wrap">
          <i class="fa-solid fa-phone input-icon"></i>
          <input type="tel" id="contactPhone" name="phone" class="form-control has-icon" placeholder="+92 300 0000000" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address <span style="color:#ef4444;">*</span></label>
        <div class="input-wrap">
          <i class="fa-solid fa-envelope input-icon"></i>
          <input type="email" id="contactEmail" name="email" class="form-control has-icon" placeholder="contact@example.com" required />
        </div>
      </div>

      <div style="display:flex;gap:.75rem;grid-column:1/-1;margin-top:.5rem;">
        <button type="submit" id="submitContactBtn" class="btn-defenza btn-primary" style="flex:1;">
          <i class="fa-solid fa-check"></i> Save Contact
        </button>
        <button type="button" onclick="closeContactForm()" class="btn-defenza btn-secondary" style="flex:1;">Cancel</button>
      </div>
    </form>
  </div>

  <!-- Contacts Grid -->
  <div id="contactsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem;">
    <!-- Contacts will be loaded here by JavaScript -->
  </div>

  <!-- Empty State -->
  <div id="emptyState" style="display:none;text-align:center;padding:3rem 1rem;">
    <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(99,102,241,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;color:var(--purple-600);">
      <i class="fa-solid fa-phone"></i>
    </div>
    <h3 style="font-size:1.2rem;font-weight:900;color:#111827;margin-bottom:.5rem;">No Emergency Contacts Yet</h3>
    <p style="color:#6b7280;margin-bottom:1.5rem;max-width:350px;margin-left:auto;margin-right:auto;">Add trusted people who will be notified instantly during emergencies. The more contacts you add, the better your support network.</p>
    <button onclick="openAddContactForm()" class="btn-defenza btn-primary">
      <i class="fa-solid fa-plus"></i> Add Your First Contact
    </button>
  </div>

  <!-- Contact Summary -->
  <div style="margin-top:2rem;padding:1rem;border-radius:var(--r-md);background:var(--purple-50);border:1px solid var(--purple-100);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <i class="fa-solid fa-circle-info" style="color:var(--purple-600);font-size:1.1rem;flex-shrink:0;"></i>
    <span style="color:var(--purple-700);font-size:.95rem;">
      <strong id="contactCount">0</strong> emergency contacts saved. Add as many trusted contacts as needed for your safety network.
    </span>
  </div>
</div>

<?php dashClose(); dashFoot(); ?>

<script>
const contactsPage = document.getElementById('contactsPage');
const apiUrl = contactsPage.dataset.apiUrl;
const relationships = <?= json_encode($relationships) ?>;

async function loadContacts() {
  try {
    const response = await fetch(apiUrl, { credentials: 'same-origin' });
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.message || 'Failed to load contacts');
    }

    renderContacts(data.contacts || []);
  } catch (error) {
    showToast(error.message, 'error');
  }
}

function renderContacts(contacts) {
  const grid = document.getElementById('contactsGrid');
  const emptyState = document.getElementById('emptyState');
  const count = document.getElementById('contactCount');
  
  count.textContent = contacts.length;

  if (contacts.length === 0) {
    grid.style.display = 'none';
    emptyState.style.display = 'block';
    return;
  }

  grid.style.display = 'grid';
  emptyState.style.display = 'none';
  grid.innerHTML = contacts.map(c => `
    <div class="voice-section" style="border-left:4px solid var(--purple-500);position:relative;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;">
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
            <div style="width:2.5rem;height:2.5rem;border-radius:50%;background:var(--grad-main);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.85rem;flex-shrink:0;">
              ${escapeHtml(c.name).charAt(0).toUpperCase()}
            </div>
            <div>
              <div style="font-weight:900;color:#111827;font-size:.95rem;">${escapeHtml(c.name)}</div>
              <div style="font-size:.8rem;color:#9ca3af;">${escapeHtml(c.relationship)}</div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;color:#6b7280;margin-top:.75rem;">
            <i class="fa-solid fa-phone" style="font-size:.85rem;color:var(--purple-500);"></i>
            <span style="font-size:.9rem;font-weight:600;">${escapeHtml(c.phone)}</span>
          </div>
          ${c.email ? `<div style="display:flex;align-items:center;gap:.5rem;color:#6b7280;margin-top:.5rem;">
            <i class="fa-solid fa-envelope" style="font-size:.85rem;color:var(--purple-500);"></i>
            <span style="font-size:.9rem;font-weight:600;">${escapeHtml(c.email)}</span>
          </div>` : ''}
        </div>
        <div style="display:flex;gap:.375rem;flex-shrink:0;">
          <button class="action-btn action-edit" title="Edit" onclick="editContact(${c.id})">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="action-btn action-delete" title="Delete" onclick="deleteContact(${c.id}, '${escapeHtml(c.name)}')">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function openAddContactForm() {
  document.getElementById('contactFormCard').style.display = 'block';
  document.getElementById('formTitle').textContent = 'Add New Contact';
  document.getElementById('contactForm').reset();
  document.getElementById('contactId').value = '0';
  document.getElementById('contactEmail').value = '';
  document.getElementById('contactName').focus();
  document.querySelector('#contactsPage').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeContactForm() {
  document.getElementById('contactFormCard').style.display = 'none';
  document.getElementById('contactForm').reset();
  document.getElementById('contactId').value = '0';
  document.getElementById('contactEmail').value = '';
}

async function editContact(id) {
  try {
    const response = await fetch(apiUrl, { credentials: 'same-origin' });
    const data = await response.json();
    const contact = data.contacts.find(c => c.id === id);
    
    if (!contact) {
      showToast('Contact not found', 'error');
      return;
    }

    document.getElementById('contactId').value = id;
    document.getElementById('contactName').value = contact.name;
    document.getElementById('contactRelationship').value = contact.relationship;
    document.getElementById('contactPhone').value = contact.phone;
    document.getElementById('contactEmail').value = contact.email || '';
    document.getElementById('formTitle').textContent = 'Edit Contact: ' + contact.name;
    document.getElementById('contactFormCard').style.display = 'block';
    document.getElementById('contactName').focus();
    document.querySelector('#contactsPage').scrollIntoView({ behavior: 'smooth', block: 'start' });
  } catch (error) {
    showToast(error.message, 'error');
  }
}

async function deleteContact(id, name) {
  if (!confirm(`Delete ${name} from your emergency contacts?`)) return;

  try {
    const response = await fetch(apiUrl + '?id=' + id, {
      method: 'DELETE',
      credentials: 'same-origin'
    });
    const data = await response.json();

    if (data.success) {
      showToast('Contact deleted successfully!', 'success');
      renderContacts(data.contacts || []);
    } else {
      showToast(data.message || 'Failed to delete contact', 'error');
    }
  } catch (error) {
    showToast('Error: ' + error.message, 'error');
  }
}

document.getElementById('contactForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const id = document.getElementById('contactId').value;
  const name = document.getElementById('contactName').value.trim();
  const relationship = document.getElementById('contactRelationship').value;
  const phone = document.getElementById('contactPhone').value.trim();
  const email = document.getElementById('contactEmail').value.trim();

  if (!name || !relationship || !phone || !email) {
    showToast('Please fill in all fields', 'error');
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showToast('Please enter a valid email address for the emergency contact.', 'error');
    return;
  }

  const btn = document.getElementById('submitContactBtn');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

  try {
    const formData = new FormData();
    formData.append('id', id || 0);
    formData.append('name', name);
    formData.append('relationship', relationship);
    formData.append('phone', phone);
    formData.append('email', email);

    const response = await fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    });
    const data = await response.json();

    if (data.success) {
      showToast(data.message || 'Contact saved successfully!', 'success');
      closeContactForm();
      renderContacts(data.contacts || []);
    } else {
      showToast(data.message || 'Failed to save contact', 'error');
    }
  } catch (error) {
    showToast('Error: ' + error.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
});

// Load contacts on page load
loadContacts();
</script>
