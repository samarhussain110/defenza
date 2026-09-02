<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

header('Content-Type: application/json');

if (!defined('GMAIL_SMTP_HOST')) {
    define('GMAIL_SMTP_HOST', getenv('GMAIL_SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('GMAIL_SMTP_PORT')) {
    define('GMAIL_SMTP_PORT', (int) (getenv('GMAIL_SMTP_PORT') ?: 587));
}
if (!defined('GMAIL_SMTP_USERNAME')) {
    define('GMAIL_SMTP_USERNAME', getenv('GMAIL_SMTP_USERNAME') ?: 'samarhussain799@gmail.com');
}
if (!defined('GMAIL_SMTP_PASSWORD')) {
    define('GMAIL_SMTP_PASSWORD', getenv('GMAIL_SMTP_PASSWORD') ?: 'xtufnfnctykhlhdl');
}

function normalizeContactEmailForShare(?string $email): string {
    $email = trim((string) ($email ?? ''));
    if ($email === '') {
        return '';
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function buildShareLocationHtmlMessage(string $userName, float $lat = null, float $lon = null, string $address = '', string $timeStamp = ''): string {
    $latText = $lat !== null ? number_format((float) $lat, 10, '.', '') : '0.0000000000';
    $lonText = $lon !== null ? number_format((float) $lon, 10, '.', '') : '0.0000000000';
    $mapsUrl = buildGoogleMapsUrl($lat, $lon);
    $formattedTime = formatShareTimestamp($timeStamp);
    $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeTime = htmlspecialchars($formattedTime, ENT_QUOTES, 'UTF-8');
    $safeAddress = htmlspecialchars($address ?: 'Unknown location', ENT_QUOTES, 'UTF-8');
    $safeMapsUrl = htmlspecialchars($mapsUrl, ENT_QUOTES, 'UTF-8');

    return '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827;background:#ffffff;padding:24px;border:1px solid #e5e7eb;border-radius:12px;max-width:640px;">
        <h2 style="color:#7c3aed;margin:0 0 12px;">📍 Shared Location Alert</h2>
        <p style="margin:0 0 10px;"><strong>' . $safeUserName . '</strong> has shared their live location through Defenza.</p>
        <p style="margin:0 0 10px;"><strong>Shared At:</strong> ' . $safeTime . '</p>
        <p style="margin:0 0 10px;"><strong>Current Location:</strong> <a href="' . $safeMapsUrl . '" style="color:#2563eb;">View on Google Maps</a></p>
        <p style="margin:0 0 10px;"><strong>Coordinates:</strong> ' . htmlspecialchars($latText . ', ' . $lonText, ENT_QUOTES, 'UTF-8') . '</p>
        <p style="margin:0 0 10px;"><strong>Address:</strong> ' . $safeAddress . '</p>
        <p style="margin:0;">Please review the location and contact the user if necessary.</p>
    </div>';
}

function sendShareLocationEmails(array $contacts, string $userName, float $lat = null, float $lon = null, string $address = '', ?string $primaryUserEmail = null, ?string $timestamp = null): array {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return ['success' => false, 'error' => 'PHPMailer class is not available.'];
    }

    $smtpHost = GMAIL_SMTP_HOST;
    $smtpPort = (int) GMAIL_SMTP_PORT;
    $smtpUser = trim((string) (GMAIL_SMTP_USERNAME ?: ''));
    $smtpPass = trim(str_replace(' ', '', (string) (GMAIL_SMTP_PASSWORD ?: '')));

    if ($smtpUser === '' || $smtpPass === '') {
        return ['success' => false, 'error' => 'Gmail SMTP credentials are not configured.'];
    }

    $errors = [];
    $sentCount = 0;
    $messageTimestamp = !empty($timestamp) ? (string) $timestamp : date('Y-m-d H:i:s');
    $fallbackEmail = trim((string) ($primaryUserEmail ?? ''));
    if (!filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
        $fallbackEmail = '';
    }

    // Check if debug mode is enabled via query string or environment
    $debugMode = isset($_GET['_debug']) && $_GET['_debug'] === 'smtp';

    foreach ($contacts as $contact) {
        $contactEmail = normalizeContactEmailForShare((string) ($contact['email'] ?? ''));
        $contactName = (string) ($contact['name'] ?? 'Emergency Contact');

        if ($contactEmail === '') {
            if ($fallbackEmail !== '') {
                $contactEmail = $fallbackEmail;
            } else {
                continue;
            }
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->SMTPDebug = $debugMode ? 2 : 0;
            if ($debugMode) {
                $mail->Debugoutput = function (string $str, int $level): void {
                    error_log('[Defenza SMTP] [' . $level . '] ' . trim($str));
                };
            }
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'LOGIN';
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPAutoTLS = true;
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = true;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtpUser, 'Defenza Share Location');
            $mail->addAddress($contactEmail, $contactName);
            $mail->Subject = '📍 Live location shared from Defenza';
            $mail->isHTML(true);
            $mail->Body = buildShareLocationHtmlMessage($userName, $lat, $lon, $address, $messageTimestamp);
            $mail->AltBody = 'Live location shared from Defenza. User: ' . $userName . '\nTime: ' . $messageTimestamp . '\nLocation: https://www.google.com/maps?q=' . rawurlencode(number_format((float) $lat, 10, '.', '') . ',' . number_format((float) $lon, 10, '.', '')) . '\nAddress: ' . $address;

            $mail->send();
            $sentCount++;
        } catch (Throwable $exception) {
            $smtpError = $mail->ErrorInfo ?: $exception->getMessage();
            $errors[] = 'Email delivery failed: ' . $smtpError;
        }
    }

    if ($sentCount > 0) {
        return ['success' => true, 'message' => 'Location shared and email alerts sent successfully.', 'error' => null, 'sent_count' => $sentCount, 'errors' => $errors];
    }

    return ['success' => false, 'message' => 'Email delivery failed: ' . (!empty($errors) ? $errors[0] : 'Unable to dispatch location email alerts.'), 'error' => !empty($errors) ? $errors[0] : 'Unable to dispatch location email alerts.', 'sent_count' => 0, 'errors' => $errors];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    requireAuth();
    $user = getAuthenticatedUser();
    $userId = $user['id'] ?? null;

    if (!$userId) throw new Exception('User not authenticated');

    $lat = isset($_POST['lat']) && is_numeric($_POST['lat']) ? (float) $_POST['lat'] : null;
    $lon = isset($_POST['lon']) && is_numeric($_POST['lon']) ? (float) $_POST['lon'] : null;
    $acc = isset($_POST['acc']) && is_numeric($_POST['acc']) ? (float) $_POST['acc'] : null;
    $address = trim($_POST['address'] ?? 'Live location');
    $status = in_array($_POST['status'] ?? 'active', ['active','paused'], true) ? $_POST['status'] : 'active';

    if ($lat === null || $lon === null) throw new Exception('Latitude and longitude are required');

    $eventTimestamp = (new DateTime('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
    $locationSource = normalizeLocationSource($_POST['location_source'] ?? 'gps');
    $locationCapturedAt = normalizeLocationCapturedAt($_POST['timestamp'] ?? null);
    $resolvedAddress = resolveReadableAddress($lat, $lon) ?: $address;
    $resolvedGoogleMapsUrl = buildGoogleMapsUrl($lat, $lon);

    error_log('[Defenza Debug] Share-location browser GPS lat=' . (string) $lat . ' lon=' . (string) $lon . ' acc=' . (string) $acc . ' address=' . $address . ' source=' . $locationSource . ' captured_at=' . ($locationCapturedAt ?? 'null'));
    error_log('[Defenza Debug] Share-location verified lat=' . (string) $lat . ' lon=' . (string) $lon . ' address=' . $resolvedAddress . ' google_maps_url=' . $resolvedGoogleMapsUrl);

    $db = getDB();
    if (!$db) throw new Exception('Database connection failed');

    // Save live location
    $liveId = saveLiveLocation($userId, $lat, $lon, $acc, $resolvedAddress, $status, $db, $locationSource, $locationCapturedAt);
    if ($liveId === null) throw new Exception('Failed to save live location');

    $locationMetaStmt = $db->prepare('UPDATE live_locations SET event_timestamp = ?, google_maps_url = ?, address = ?, location_source = ?, location_captured_at = ? WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1');
    if ($locationMetaStmt) {
        $locationMetaStmt->bind_param('sssssi', $eventTimestamp, $resolvedGoogleMapsUrl, $resolvedAddress, $locationSource, $locationCapturedAt, $userId);
        $locationMetaStmt->execute();
        $locationMetaStmt->close();
    }

    // Fetch the exact saved coordinates and timestamp from database to ensure consistency
    $stmt = $db->prepare('SELECT latitude, longitude, accuracy, address, created_at, event_timestamp, google_maps_url, location_source, location_captured_at FROM live_locations WHERE user_id = ? LIMIT 1');
    if (!$stmt) throw new Exception('Failed to prepare database query');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $locationRow = $result->fetch_assoc();
    $stmt->close();
    
    if (!$locationRow) throw new Exception('Failed to retrieve saved location from database');
    
    $dbLat = (float) $lat;
    $dbLon = (float) $lon;
    $dbAddress = $resolvedAddress;
    $dbTimestamp = $eventTimestamp;
    $dbGoogleMapsUrl = $resolvedGoogleMapsUrl;

    // Notify emergency contacts (record shares)
    $contacts = getUserContacts($userId, $db);
    $recipientContacts = $contacts;
    if (empty($recipientContacts)) {
        $fallbackEmail = trim((string) ($user['email'] ?? ''));
        if (filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
            $recipientContacts = [[
                'name' => $user['full_name'] ?? 'User',
                'email' => $fallbackEmail,
                'phone' => '',
            ]];
        }
    }

    $notified = 0;
    $shareRecords = [];
    $emailResult = ['success' => false, 'error' => 'No contacts available.'];

    foreach ($recipientContacts as $c) {
        $name = $c['name'] ?? '';
        $phone = $c['phone'] ?? '';
        $shareId = saveLiveShare($liveId, $userId, $name, $phone, 'sms', $db);
        $shareRecords[] = ['id' => $shareId, 'name' => $name, 'phone' => $phone];
        $notified++;
    }

    if ($notified > 0) {
        $emailResult = sendShareLocationEmails($recipientContacts, $user['full_name'] ?? 'User', $dbLat, $dbLon, $dbAddress, $user['email'] ?? null, $dbTimestamp);
    }

    echo json_encode([
        'success' => !empty($emailResult['success']),
        'message' => !empty($emailResult['success']) ? 'Location shared and email alerts sent successfully.' : ($emailResult['message'] ?? 'Unable to send location email alerts.'),
        'contacts_notified' => $notified,
        'email_sent' => !empty($emailResult['success']),
        'email_error' => $emailResult['error'] ?? null,
        'location' => ['lat' => $dbLat, 'lon' => $dbLon, 'acc' => $acc, 'address' => $dbAddress, 'status' => $status, 'google_maps_url' => $dbGoogleMapsUrl, 'location_source' => $locationSource, 'location_captured_at' => $locationCapturedAt],
        'shares' => $shareRecords,
        'live_id' => $liveId,
    ]);
    return;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
