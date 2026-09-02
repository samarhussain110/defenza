<?php
// Public page <head> + opening tags
function renderHead(string $title = 'Defenza — Women Safety Emergency System'): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-url" content="<?= APP_URL ?>" />
  <meta name="lang" content="<?= normalizeAppLanguage((string) ($_SESSION['user']['language'] ?? 'English')) ?>" />
  <title><?= e($title) ?></title>
  <meta name="description" content="Instant SOS alerts, live location sharing and emergency support for women safety." />
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
</head>
<body>
<script>window.APP_URL = '<?= APP_URL ?>';</script>
<script src="<?= APP_URL ?>/assets/js/i18n.js"></script>
<?php } ?>
