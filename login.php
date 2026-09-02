<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$success = '';
$error = '';
if (isset($_GET['registered']) && $_GET['registered']) {
    $success = 'Account created successfully. Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
            $db = getDB();
            if ($db) ensureAppDatabaseTables($db);
        $loginFailed = false;
        if ($db) {
            try {
                $stmt = $db->prepare('SELECT id, password FROM users WHERE email = ? LIMIT 1');
                if (!$stmt) {
                    throw new RuntimeException('Database query failed.');
                }

                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->bind_result($userId, $userPasswordHash);
                $user = null;
                if ($stmt->fetch()) {
                    $user = [
                        'id' => $userId,
                        'password' => $userPasswordHash,
                    ];
                }
                $stmt->close();

                if (is_array($user) && !empty($user['password']) && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];

                    if (!empty($_POST['remember'])) {
                        rememberUser($user['id'], $db);
                    } else {
                        clearRememberUser($user['id'], $db);
                    }

                    session_write_close();
                    header('Location: ' . APP_URL . '/pages/dashboard.php', true, 302);
                    exit;
                }

                $loginFailed = true;
            } catch (Throwable $exception) {
              // Log exception for debugging (will appear in PHP/Apache error log)
              error_log('[login.php] Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
              $loginFailed = true;
            }
        }

        if (!$db) {
            $error = 'Unable to connect to the database. Please try again later.';
        } elseif ($loginFailed) {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

renderHead('Login — ' . APP_NAME);
?>

<div class="auth-wrapper">
  <!-- Form side (left on desktop) -->
  <div class="auth-right" style="background:#f4f1fb;">
    <div class="auth-card">
      <!-- Logo -->
      <div class="auth-logo">
        <div class="auth-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <span style="font-weight:900;color:var(--purple-800);font-size:1rem;display:block;line-height:1;"><?= APP_NAME ?></span>
          <span style="font-size:.63rem;color:var(--purple-400);font-weight:600;"><?= APP_TAGLINE ?></span>
        </div>
      </div>

      <h1 class="auth-title">Welcome Back!</h1>
      <p class="auth-subtitle">Login to access your safety dashboard.</p>

      <?php if ($success): ?>
        <div class="alert-box alert-success" style="margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-check" style="flex-shrink:0;"></i>
          <span><?= e($success) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert-box alert-danger" style="margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-xmark" style="flex-shrink:0;"></i>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form" style="display:grid;gap:1rem;" novalidate>
        <!-- Email -->
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control has-icon"
                   placeholder="Enter your email address" value="" />
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <label class="form-label">Password</label>
            <a href="#" onclick="showToast('Password reset is a demo feature.','info');return false;"
               style="font-size:.78rem;color:var(--purple-700);font-weight:600;">Forgot Password?</a>
          </div>
          <div class="input-wrap" style="position:relative;">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" name="password" id="loginPass" class="form-control has-icon"
                   placeholder="Enter your password" value="" />
            <button type="button" class="pass-toggle" onclick="togglePass('loginPass',this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Remember -->
        <div style="display:flex;align-items:center;gap:.5rem;">
          <input type="checkbox" name="remember" id="remember" class="form-check-input" checked />
          <label for="remember" class="form-check-label">Remember Me</label>
        </div>

        <button type="submit" class="btn-defenza btn-primary btn-full" style="padding:.875rem;margin-top:.25rem;">
          <i class="fa-solid fa-right-to-bracket"></i> Login to Dashboard
        </button>
      </form>

      <p style="text-align:center;font-size:.875rem;color:#9ca3af;margin-top:1.375rem;">
        Don't have an account?
        <a href="<?= APP_URL ?>/auth/register.php" style="color:var(--purple-700);font-weight:700;">Create Account</a>
      </p>
    </div>
  </div>

  <!-- Illustration side (right / hidden on mobile) -->
  <div class="auth-left">
    <a href="<?= APP_URL ?>/" style="display:flex;align-items:center;gap:.625rem;text-decoration:none;position:relative;z-index:1;align-self:flex-start;">
      <div class="auth-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <span style="font-weight:900;color:#fff;font-size:1.2rem;"><?= APP_NAME ?></span>
    </a>

    <div class="auth-illustration">
      <!-- Animated shield -->
      <div style="position:relative;width:14rem;height:14rem;display:flex;align-items:center;justify-content:center;margin-bottom:1.75rem;">
        <div style="position:absolute;inset:0;border-radius:50%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);"></div>
        <div style="position:absolute;inset:1.5rem;border-radius:50%;border:1px dashed rgba(255,255,255,.2);animation:spin-slow 14s linear infinite;"></div>
        <div style="position:absolute;inset:2.75rem;border-radius:50%;border:1px dashed rgba(249,168,212,.25);animation:spin-rev 20s linear infinite;"></div>
        <div class="auth-shield-wrap" style="position:relative;z-index:2;margin-bottom:0;">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div style="position:absolute;top:-4px;right:-4px;width:2.75rem;height:2.75rem;border-radius:.875rem;background:rgba(236,72,153,.85);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.25);animation:float-r 2.5s ease-in-out infinite;">
          <i class="fa-solid fa-bell" style="color:#fff;font-size:.95rem;"></i>
        </div>
        <div style="position:absolute;bottom:-4px;left:-4px;width:2.75rem;height:2.75rem;border-radius:.875rem;background:rgba(126,34,206,.85);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.25);animation:float-l 3s ease-in-out infinite;">
          <i class="fa-solid fa-location-dot" style="color:#fff;font-size:.95rem;"></i>
        </div>
      </div>

      <h2 style="font-weight:900;color:#fff;font-size:1.45rem;margin-bottom:.5rem;text-align:center;">Welcome Back!</h2>
      <p style="color:rgba(221,214,254,.8);font-size:.85rem;line-height:1.65;text-align:center;max-width:268px;margin-bottom:2rem;">
        Stay protected with real-time SOS alerts, live tracking, and emergency support.
      </p>

      <div class="auth-stat-row">
        <?php foreach ([['10K+','Users'],['25K+','SOS Sent'],['24/7','Support']] as [$v,$l]): ?>
          <div class="auth-stat-box">
            <div class="auth-stat-val"><?= $v ?></div>
            <div class="auth-stat-lbl"><?= $l ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <p style="color:rgba(196,181,253,.45);font-size:.75rem;text-align:center;position:relative;z-index:1;">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All Rights Reserved.</p>
  </div>
</div>

<style>
@keyframes spin-slow { from{transform:rotate(0)} to{transform:rotate(360deg)} }
@keyframes spin-rev  { from{transform:rotate(0)} to{transform:rotate(-360deg)} }
</style>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
