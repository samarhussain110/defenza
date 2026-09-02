<?php
// Dashboard layout helpers (no session/DB dependency)
function dashHead(string $title): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-url" content="<?= APP_URL ?>" />
  <meta name="lang" content="<?= normalizeAppLanguage((string) ($_SESSION['user']['language'] ?? 'English')) ?>" />
  <title><?= e($title) ?> — <?= APP_NAME ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
</head>
<body>
<div class="dash-wrapper">
<?php }

function dashFoot(): void { ?>
</div><!-- /.dash-wrapper -->

<div class="toast-container" id="toastContainer"></div>

<script>window.APP_URL = '<?= APP_URL ?>';</script>
<script src="<?= APP_URL ?>/assets/js/i18n.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
<?php }

function dashOpen(string $pageTitle, string $activePage): void {
    require_once __DIR__ . '/sidebar.php';
    require_once __DIR__ . '/topbar.php';
    renderSidebar($activePage);
    echo '<div class="dash-main">';
    renderTopbar($pageTitle);
    echo '<main class="dash-content page-fade">';
}

function dashClose(): void {
    echo '</main></div>';
}
?>
