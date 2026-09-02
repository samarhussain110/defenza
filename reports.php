<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();
$db = getDB();
$reports = [];
$incidentTypes = ['Harassment','Stalking','Physical Assault','Verbal Abuse','Suspicious Activity','Unsafe Area','Other'];

if ($db) {
    ensureAppDatabaseTables($db);
    $stmt = $db->prepare('SELECT id, incident_type, location, status, created_at FROM incident_reports WHERE user_id = ? ORDER BY created_at DESC');
    if ($stmt) {
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

dashHead('Report Incident');
dashOpen('Report an Incident', 'Reports');
?>

<div style="max-width:960px;">
  <!-- Success state (hidden by default, shown by JS) -->
  <div class="card-defenza no-hover" style="padding:3rem;text-align:center;max-width:440px;margin:0 auto;display:none;" id="reportSuccess">
    <div style="width:5rem;height:5rem;border-radius:50%;background:linear-gradient(135deg,#34d399,#059669);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;box-shadow:0 8px 24px rgba(52,211,153,.35);">
      <i class="fa-solid fa-circle-check" style="color:#fff;font-size:2rem;"></i>
    </div>
    <h2 style="font-size:1.3rem;font-weight:900;color:#111827;margin-bottom:.5rem;">Report Submitted!</h2>
    <p style="color:#9ca3af;font-size:.875rem;margin-bottom:1.5rem;">Your incident report has been submitted successfully. Our team will review it within 24 hours.</p>
    <div style="background:var(--purple-50);border-radius:.75rem;padding:.75rem;margin-bottom:1.25rem;font-size:.8rem;color:var(--purple-700);">
      Report ID: <strong id="reportId">#RPT-000000</strong>
    </div>
    <button id="submitAnotherReportBtn" type="button" class="btn-defenza btn-primary btn-full">Submit Another Report</button>
  </div>

  <!-- Form & Sidebar Section -->
  <div style="display:grid;grid-template-columns:3fr 2fr;gap:1.5rem;" id="reportFormWrap">

    <!-- Form card -->
    <div class="card-defenza no-hover" style="padding:1.75rem;">
      <div style="display:flex;align-items:center;gap:.875rem;margin-bottom:1.5rem;">
        <div style="width:2.5rem;height:2.5rem;border-radius:.75rem;background:linear-gradient(135deg,#fb923c,#ef4444);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:var(--shadow-md);">
          <i class="fa-solid fa-file-lines"></i>
        </div>
        <div>
          <div style="font-weight:900;color:#111827;">Report an Incident</div>
          <div style="font-size:.75rem;color:#9ca3af;">Fill the details of the incident</div>
        </div>
      </div>

      <form id="reportForm" style="display:grid;gap:1rem;">

        <div class="form-group">
          <label class="form-label">Incident Type</label>
          <select name="incident_type" id="incident_type" class="form-select" required>
            <option value="">Select incident type</option>
            <?php foreach ($incidentTypes as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Location</label>
          <div class="input-wrap">
            <i class="fa-solid fa-location-dot input-icon"></i>
            <input type="text" id="locationInput" name="location" class="form-control has-icon" placeholder="Enter location (e.g., Clifton, Karachi)" required />
            <button type="button" id="gpsLocatorBtn" class="icon-btn" title="Use current GPS location" aria-label="Use current GPS location" style="position:absolute;right:.45rem;top:50%;transform:translateY(-50%);width:2.1rem;height:2.1rem;background:#fff;border:1px solid var(--purple-200);color:var(--purple-600);box-shadow:var(--shadow-xs);">
              <i class="fa-solid fa-location-crosshairs"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="description" class="form-control" placeholder="Describe what happened in detail..." rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Upload Photo (Optional)</label>
          <label class="upload-zone" id="uploadZone">
            <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" onchange="updateUploadLabel(this)" />
            <div class="upload-icon" id="uploadIcon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div id="uploadText">
              <div style="font-size:.875rem;font-weight:600;color:#6b7280;">Click to upload photo</div>
              <div style="font-size:.75rem;color:#9ca3af;margin-top:.2rem;">PNG, JPG up to 5MB</div>
            </div>
          </label>
        </div>

        <div id="reportErrorMessage" style="display:none;color:#dc2626;font-size:.8rem;padding:.5rem .75rem;border:1px solid #fecaca;background:#fef2f2;border-radius:.625rem;"></div>

        <button type="submit" class="btn-defenza btn-primary btn-full" style="padding:.875rem;">
          <i class="fa-solid fa-paper-plane"></i> Submit Report
        </button>
      </form>
    </div>

    <!-- Sidebar Info & Real-Time Recent Reports -->
    <div>
      <div class="card-defenza no-hover" style="padding:2rem;text-align:center;background:var(--grad-soft);border:none;margin-bottom:1rem;">
        <div style="width:5.5rem;height:5.5rem;border-radius:50%;background:linear-gradient(135deg,#fb923c,#ef4444);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;box-shadow:0 8px 24px rgba(239,68,68,.25);">
          <i class="fa-solid fa-triangle-exclamation" style="color:#fff;font-size:2rem;"></i>
        </div>
        <h3 style="font-weight:900;color:#111827;margin-bottom:.625rem;">Report an Incident</h3>
        <p style="color:#9ca3af;font-size:.8rem;line-height:1.55;margin-bottom:1.25rem;">
          Your report helps make communities safer. All reports are confidential.
        </p>
        <?php foreach (['100% Confidential','Helps improve community safety','Reviewed within 24 hours','Support team available 24/7'] as $item): ?>
          <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#6b7280;margin-bottom:.5rem;">
            <div style="width:1.25rem;height:1.25rem;border-radius:50%;background:var(--purple-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-check" style="color:var(--purple-600);font-size:.6rem;"></i>
            </div>
            <?= $item ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Real-Time Recent Reports Card -->
      <div class="card-defenza no-hover" style="padding:1.25rem;">
        <div style="font-weight:900;font-size:.875rem;color:#111827;margin-bottom:1rem;">Recent Reports</div>
        <div id="recentReportsContainer" style="display:flex; flex-direction:column; gap:0.5rem;">
          <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $report): ?>
              <div style="display:flex;align-items:center;gap:.625rem;padding:.5rem 0;border-bottom:1px solid var(--purple-50);">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--purple-500);flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:.8rem;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($report['incident_type'] ?? 'Incident') ?></div>
                  <div style="font-size:.7rem;color:#9ca3af;"><?= e($report['created_at'] ?? '') ?></div>
                  <div style="font-size:.7rem;color:#6b7280;margin-top:.2rem;line-height:1.4;"><?= e($report['location'] ?? '') ?></div>
                </div>
                <span class="badge-pill badge-amber" style="font-size:.68rem;"><?= e(ucfirst((string) ($report['status'] ?? 'pending'))) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div id="empty-state" style="text-align:center; padding:1.5rem 0; color:#9ca3af; font-size:0.78rem;">
              <i class="fa-solid fa-folder-open" style="font-size:1.5rem; margin-bottom:0.5rem; display:block; color:var(--purple-300);"></i>
              No reports submitted yet.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function updateUploadLabel(input) {
  if (input.files && input.files[0]) {
    document.getElementById('uploadIcon').innerHTML = '<i class="fa-solid fa-circle-check" style="color:var(--purple-500);"></i>';
    document.getElementById('uploadText').innerHTML = '<div style="font-size:.875rem;font-weight:700;color:var(--purple-600);">' + input.files[0].name + '</div><div style="font-size:.72rem;color:#9ca3af;">' + (input.files[0].size/1024).toFixed(0) + ' KB</div>';
  }
}

function resetReportForm() {
  const reportForm = document.getElementById('reportForm');
  if (reportForm) {
    reportForm.reset();
  }

  const photoInput = document.getElementById('photoInput');
  if (photoInput) {
    photoInput.value = '';
  }

  const uploadIcon = document.getElementById('uploadIcon');
  const uploadText = document.getElementById('uploadText');
  if (uploadIcon) {
    uploadIcon.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i>';
  }
  if (uploadText) {
    uploadText.innerHTML = '<div style="font-size:.875rem;font-weight:600;color:#6b7280;">Click to upload photo</div><div style="font-size:.75rem;color:#9ca3af;margin-top:.2rem;">PNG, JPG up to 5MB</div>';
  }
}

function renderReports(reports) {
  const container = document.getElementById('recentReportsContainer');
  if (!container) return;

  const safeReports = Array.isArray(reports) ? reports : [];
  if (!safeReports.length) {
    container.innerHTML = `
      <div id="empty-state" style="text-align:center; padding:1.5rem 0; color:#9ca3af; font-size:0.78rem;">
        <i class="fa-solid fa-folder-open" style="font-size:1.5rem; margin-bottom:0.5rem; display:block; color:var(--purple-300);"></i>
        No reports submitted yet.
      </div>
    `;
    return;
  }

  container.innerHTML = safeReports.map((report) => {
    const status = (report.status || 'pending').toString();
    const formattedStatus = status.charAt(0).toUpperCase() + status.slice(1);
    return `
      <div style="display:flex;align-items:center;gap:.625rem;padding:.5rem 0;border-bottom:1px solid var(--purple-50);">
        <div style="width:8px;height:8px;border-radius:50%;background:var(--purple-500);flex-shrink:0;"></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.8rem;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(report.incident_type || 'Incident')}</div>
          <div style="font-size:.7rem;color:#9ca3af;">${escapeHtml(report.created_at || '')}</div>
          <div style="font-size:.7rem;color:#6b7280;margin-top:.2rem;line-height:1.4;">${escapeHtml(report.location || '')}</div>
          <div style="font-size:.68rem;color:#9ca3af;margin-top:.2rem;line-height:1.4;">${escapeHtml(report.description || '')}</div>
        </div>
        <span class="badge-pill badge-amber" style="font-size:.68rem;">${escapeHtml(formattedStatus)}</span>
      </div>
    `;
  }).join('');
}

async function loadReports() {
  try {
    const response = await fetch('../api/reports.php', { method: 'GET' });
    const data = await response.json().catch(() => null);
    if (!response.ok || !data?.success) {
      throw new Error(data?.message || 'Failed to load reports');
    }
    renderReports(data.reports || []);
  } catch (error) {
    showToast(error.message || 'Failed to load reports', 'error');
  }
}

const dropzone = document.getElementById('uploadZone');
if (dropzone) {
  dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
  dropzone.addEventListener('drop', e => {
    e.preventDefault(); dropzone.classList.remove('dragover');
    const fi = document.getElementById('photoInput');
    fi.files = e.dataTransfer.files;
    updateUploadLabel(fi);
  });
}

// GPS Logic
const gpsButton = document.getElementById('gpsLocatorBtn');
const locationInput = document.getElementById('locationInput');

if (gpsButton && locationInput) {
  gpsButton.addEventListener('click', () => {
    if (!navigator.geolocation) {
      locationInput.value = 'Geolocation unavailable';
      return;
    }

    gpsButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    navigator.geolocation.getCurrentPosition(
      position => {
        const lat = position.coords.latitude.toFixed(4);
        const lng = position.coords.longitude.toFixed(4);
        locationInput.value = `${lat}, ${lng}`;
        gpsButton.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
      },
      () => {
        locationInput.value = 'Location access denied';
        gpsButton.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
      },
      { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 }
    );
  });
}

// Handle dynamic report form submission
const reportForm = document.getElementById('reportForm');
if (reportForm) {
  reportForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const incidentTypeSelect = document.getElementById('incident_type');
    const locationInput = document.getElementById('locationInput');
    const descriptionInput = document.getElementById('description');
    const submitButton = reportForm.querySelector('button[type="submit"]');
    const formWrap = document.getElementById('reportFormWrap');
    const successPanel = document.getElementById('reportSuccess');
    const reportIdEl = document.getElementById('reportId');
    const errorMessage = document.getElementById('reportErrorMessage');

    const typeValue = incidentTypeSelect ? incidentTypeSelect.value.trim() : '';
    const locationValue = locationInput ? locationInput.value.trim() : '';
    const descValue = descriptionInput ? descriptionInput.value.trim() : '';

    if (!typeValue) {
      incidentTypeSelect?.focus();
      if (errorMessage) {
        errorMessage.textContent = 'Please select an incident type.';
        errorMessage.style.display = 'block';
      }
      return;
    }

    if (!locationValue) {
      locationInput?.focus();
      if (errorMessage) {
        errorMessage.textContent = 'Please enter a location.';
        errorMessage.style.display = 'block';
      }
      return;
    }

    if (!descValue) {
      descriptionInput?.focus();
      if (errorMessage) {
        errorMessage.textContent = 'Please describe the incident.';
        errorMessage.style.display = 'block';
      }
      return;
    }

    if (errorMessage) {
      errorMessage.textContent = '';
      errorMessage.style.display = 'none';
    }

    const formData = new FormData(reportForm);
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
    }

    try {
      const response = await fetch('../api/reports.php', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json().catch(() => null);

      if (!response.ok || !data?.success) {
        throw new Error(data?.message || 'Unable to submit your report right now.');
      }

      const report = data.report || null;
      if (reportIdEl && report?.id != null) {
        reportIdEl.textContent = `#RPT-${String(report.id).padStart(6, '0')}`;
      }

      if (formWrap) formWrap.style.display = 'none';
      if (successPanel) successPanel.style.display = 'block';

      resetReportForm();
      await loadReports();
    } catch (error) {
      if (errorMessage) {
        errorMessage.textContent = error.message || 'Unable to submit your report right now.';
        errorMessage.style.display = 'block';
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Report';
      }
    }
  });
}

const submitAnotherReportBtn = document.getElementById('submitAnotherReportBtn');
if (submitAnotherReportBtn) {
  submitAnotherReportBtn.addEventListener('click', () => {
    const successPanel = document.getElementById('reportSuccess');
    const formWrap = document.getElementById('reportFormWrap');
    if (successPanel) successPanel.style.display = 'none';
    if (formWrap) formWrap.style.display = 'grid';
    resetReportForm();
  });
}

function escapeHtml(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<?php dashClose(); dashFoot(); ?>
