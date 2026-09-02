<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dash_layout.php';

requireAuth();
$user = getAuthenticatedUser();

$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'pending', 'resolved'], true)
    ? (string) $_GET['filter']
    : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
    $newStatus = isset($_POST['new_status']) && in_array($_POST['new_status'], ['pending', 'resolved'], true)
        ? (string) $_POST['new_status']
        : '';

    if ($messageId > 0 && $newStatus !== '') {
        updateSupportMessageStatus($messageId, $newStatus);
    }

    header('Location: ' . APP_URL . '/pages/admin_support.php?filter=' . rawurlencode($filter));
    exit();
}

$messages = getSupportMessages($filter);

dashHead('Support Messages');
dashOpen('Support Messages', 'Support Messages');
?>

<style>
.support-filter-row { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
.support-filter-link { text-decoration:none; padding:.6rem .95rem; border-radius:999px; font-weight:700; color:#6b7280; background:#f9fafb; border:1px solid rgba(168,85,247,.12); }
.support-filter-link.active { color:#7c3aed; background:rgba(168,85,247,.12); border-color:rgba(168,85,247,.2); }
.support-message-card { padding:1rem 1.1rem; border:1px solid rgba(168,85,247,.12); border-radius:1rem; background:rgba(250,245,255,.65); display:grid; gap:.7rem; margin-bottom:.9rem; }
.support-message-meta { display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:center; }
.support-message-text { color:#4b5563; line-height:1.6; white-space:pre-wrap; }
@media (max-width: 900px) { .support-message-meta { align-items:flex-start; flex-direction:column; } }
</style>

<div class="card-defenza no-hover" style="padding:1.5rem;margin-bottom:1.25rem;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
    <div>
      <div class="badge-pill badge-purple" style="margin-bottom:.5rem;"><i class="fa-solid fa-headset"></i> Support Messages</div>
      <h2 style="font-size:1.35rem;font-weight:900;color:#111827;">Review and manage inbound support requests</h2>
      <p style="color:#6b7280;margin-top:.35rem;max-width:640px;">Track messages, update their status, and keep follow-up work organized.</p>
    </div>
  </div>
</div>

<div class="support-filter-row">
  <a class="support-filter-link <?= $filter === 'all' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/admin_support.php?filter=all">All</a>
  <a class="support-filter-link <?= $filter === 'pending' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/admin_support.php?filter=pending">Pending</a>
  <a class="support-filter-link <?= $filter === 'resolved' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/admin_support.php?filter=resolved">Resolved</a>
</div>

<?php if (empty($messages)): ?>
  <div class="card-defenza no-hover" style="padding:1.25rem;">
    <div class="alert-box alert-success" style="margin-bottom:0;">
      <i class="fa-solid fa-circle-info"></i>
      <span>No support messages found for this filter.</span>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($messages as $message): ?>
    <?php
      $isResolved = ($message['status'] ?? 'pending') === 'resolved';
      $badgeStyle = $isResolved
          ? 'background:#dcfce7;color:#166534;'
          : 'background:#fef3c7;color:#92400e;';
    ?>
    <div class="card-defenza no-hover support-message-card">
      <div class="support-message-meta">
        <div>
          <div style="font-weight:900;color:#111827;"><?= e((string) ($message['name'] ?? '')) ?></div>
          <div style="font-size:.86rem;color:#6b7280;margin-top:.2rem;"><?= e((string) ($message['email'] ?? '')) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
          <span class="badge-pill" style="<?= $badgeStyle ?>"><?= $isResolved ? 'Resolved' : 'Pending' ?></span>
          <span style="font-size:.8rem;color:#9ca3af;"><?= e((string) ($message['created_at'] ?? '')) ?></span>
        </div>
      </div>

      <div class="support-message-text"><?= e((string) ($message['message'] ?? '')) ?></div>

      <div style="display:flex;justify-content:flex-end;align-items:center;">
        <form method="post" style="display:inline-flex;">
          <input type="hidden" name="message_id" value="<?= e((string) ($message['id'] ?? '')) ?>" />
          <input type="hidden" name="new_status" value="<?= $isResolved ? 'pending' : 'resolved' ?>" />
          <button type="submit" class="btn-defenza btn-primary" style="padding:.6rem .95rem;font-size:.82rem;">
            <i class="fa-solid <?= $isResolved ? 'fa-rotate-left' : 'fa-check' ?>"></i>
            <?= $isResolved ? 'Mark as Pending' : 'Mark as Resolved' ?>
          </button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php dashClose(); dashFoot(); ?>
