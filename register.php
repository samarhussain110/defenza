<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($name === '' || $email === '' || $phone === '' || $emergencyPhone === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        if ($db) {
          // Ensure required tables exist before operating
          ensureAppDatabaseTables($db);
            try {
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                if (!$stmt) {
                    throw new RuntimeException('Database query failed.');
                }

                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->bind_result($existingUserId);
                $hasExistingUser = $stmt->fetch();
                $stmt->close();

                if ($hasExistingUser) {
                    $error = 'This email is already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $db->prepare('INSERT INTO users (full_name, email, phone, password, emergency_phone, bio) VALUES (?, ?, ?, ?, ?, ?)');
                    if (!$insert) {
                        throw new RuntimeException('Unable to prepare registration query.');
                    }

                    $bio = '';
                    $insert->bind_param('ssssss', $name, $email, $phone, $hash, $emergencyPhone, $bio);
                    if (!$insert->execute()) {
                        throw new RuntimeException('Unable to create your account.');
                    }

                    $userId = (int) $db->insert_id;
                    $insert->close();

                    if ($userId <= 0) {
                        throw new RuntimeException('Unable to create your account.');
                    }

                    $settings = $db->prepare('INSERT INTO user_settings (user_id) VALUES (?)');
                    if (!$settings) {
                        throw new RuntimeException('Unable to save user settings.');
                    }

                    $settings->bind_param('i', $userId);
                    if (!$settings->execute()) {
                        throw new RuntimeException('Unable to save user settings.');
                    }
                    $settings->close();

                    header('Location: ' . APP_URL . '/auth/login.php?registered=1', true, 302);
                    exit;
                }
            } catch (Throwable $exception) {
                $error = 'Unable to register at this time. Please try again.';
            }
        }

        if (!$db && !$error) {
            $error = 'Unable to connect to the database. Please try again later.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

renderHead('Register — ' . APP_NAME);
?>

<div class="auth-wrapper">
  <!-- Illustration side (left) -->
  <div class="auth-left">
    <a href="<?= APP_URL ?>/" style="display:flex;align-items:center;gap:.625rem;text-decoration:none;position:relative;z-index:1;">
      <div class="auth-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <span style="font-weight:900;color:#fff;font-size:1.2rem;"><?= APP_NAME ?></span>
    </a>

    <div class="auth-illustration">
      <div class="auth-shield-wrap">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <h2 style="font-weight:900;color:#fff;font-size:1.45rem;margin-bottom:.75rem;text-align:center;">Join Defenza</h2>
      <p style="color:rgba(221,214,254,.8);font-size:.85rem;line-height:1.65;text-align:center;max-width:280px;margin-bottom:1.75rem;">
        Stay protected with instant SOS alerts, live location sharing, and emergency contacts — all in one place.
      </p>
      <div class="auth-feature-list">
        <?php foreach (['Real-time SOS Alerts','Live Location Tracking','Emergency Contacts','24/7 Safety Support'] as $f): ?>
          <div class="auth-feature-item">
            <i class="fa-solid fa-circle-check"></i><?= $f ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <p style="color:rgba(196,181,253,.45);font-size:.75rem;text-align:center;position:relative;z-index:1;">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All Rights Reserved.</p>
  </div>

  <!-- Form side (right) -->
  <div class="auth-right">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="auth-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <span style="font-weight:900;color:var(--purple-800);font-size:1rem;display:block;line-height:1;"><?= APP_NAME ?></span>
          <span style="font-size:.63rem;color:var(--purple-400);font-weight:600;"><?= APP_TAGLINE ?></span>
        </div>
      </div>

      <h1 class="auth-title">Create Your Account</h1>
      <p class="auth-subtitle">Join Defenza and stay protected always.</p>

      <?php if ($error): ?>
        <div class="alert-box alert-danger" style="margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-xmark" style="flex-shrink:0;"></i>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form" style="display:grid;gap:.875rem;" novalidate>
        <!-- Full Name -->
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user input-icon"></i>
            <input type="text" name="full_name" class="form-control has-icon"
                   placeholder="Enter your full name" required />
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control has-icon"
                   placeholder="Enter your email address" required />
          </div>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="input-wrap">
            <i class="fa-solid fa-phone input-icon"></i>
            <input type="tel" name="phone" class="form-control has-icon"
                   placeholder="Enter your phone number" required />
          </div>
        </div>

        <!-- Password row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.875rem;">
          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap" style="position:relative;">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" name="password" id="regPass" class="form-control has-icon"
                     placeholder="Password" required />
              <button type="button" class="pass-toggle" onclick="togglePass('regPass',this)">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-wrap" style="position:relative;">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" name="confirm_password" id="regConfirm" class="form-control has-icon"
                     placeholder="Confirm" required />
              <button type="button" class="pass-toggle" onclick="togglePass('regConfirm',this)">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Emergency Contact -->
        <div class="form-group">
          <label class="form-label">Emergency Contact Number</label>
          <div class="input-wrap">
            <i class="fa-solid fa-circle-exclamation input-icon"></i>
            <input type="tel" name="emergency_phone" class="form-control has-icon"
                   placeholder="Enter emergency contact number" required />
          </div>
        </div>

        <button type="submit" class="btn-defenza btn-primary btn-full" style="margin-top:.25rem;padding:.875rem;">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
      </form>

      <p style="text-align:center;font-size:.875rem;color:#9ca3af;margin-top:1.375rem;">
        Already have an account?
        <a href="<?= APP_URL ?>/auth/login.php" style="color:var(--purple-700);font-weight:700;">Login</a>
      </p>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
