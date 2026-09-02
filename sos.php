<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
ignore_user_abort(true);
set_time_limit(0);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

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
if (!defined('TWILIO_SID')) {
    define('TWILIO_SID', getenv('TWILIO_SID') ?: 'AC516843c5124f0895e1c8d347712e012f');
}
if (!defined('TWILIO_TOKEN')) {
    define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: '99b84c141939498d7c2db89b93d9e36d');
}
if (!defined('TWILIO_PHONE_NUMBER')) {
    define('TWILIO_PHONE_NUMBER', getenv('TWILIO_PHONE_NUMBER') ?: '+18777804236');
}

function buildEmergencyHtmlMessage(string $userName, float $lat = null, float $lon = null, string $address = '', string $timeStamp = ''): string {
    $latText = $lat !== null ? (string) $lat : '0';
    $lonText = $lon !== null ? (string) $lon : '0';
    $mapsUrl = buildGoogleMapsUrl($lat, $lon);
    $formattedTime = formatShareTimestamp($timeStamp);
    $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeTime = htmlspecialchars($formattedTime, ENT_QUOTES, 'UTF-8');
    $safeAddress = htmlspecialchars($address ?: 'Unknown location', ENT_QUOTES, 'UTF-8');
    $safeMapsUrl = htmlspecialchars($mapsUrl, ENT_QUOTES, 'UTF-8');

    return '<div style="font-family:Arial,sans-serif;line-height:1.75;color:#111827;background:#ffffff;padding:24px;border:1px solid #fee2e2;border-radius:12px;max-width:640px;">
        <h2 style="color:#dc2626;margin:0 0 12px;">🚨 Emergency SOS Alert</h2>
        <p style="margin:0 0 10px;"><strong>' . $safeUserName . '</strong> has triggered an SOS alert on Defenza.</p>
        <p style="margin:0 0 10px;"><strong>Emergency Message:</strong> EMERGENCY ALERT: This user has triggered an SOS alert on Defenza. Please check on them immediately.</p>
        <p style="margin:0 0 10px;"><strong>Current Time & Date:</strong> ' . $safeTime . '</p>
        <p style="margin:0 0 10px;"><strong>Current Location:</strong> <a href="' . $safeMapsUrl . '" style="color:#1d4ed8;">View on Google Maps</a></p>
        <p style="margin:0 0 10px;"><strong>Coordinates:</strong> ' . htmlspecialchars($latText . ', ' . $lonText, ENT_QUOTES, 'UTF-8') . '</p>
        <p style="margin:0 0 10px;"><strong>Address:</strong> ' . $safeAddress . '</p>
        <p style="margin:0;"><strong>Action Required:</strong> Please contact the user immediately and provide assistance.</p>
    </div>';
}

function ensureContactsTable(mysqli $db): void {
    $db->query("CREATE TABLE IF NOT EXISTS contacts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        contact_name VARCHAR(150) NOT NULL DEFAULT '',
        contact_phone VARCHAR(50) NOT NULL DEFAULT '',
        email VARCHAR(150) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_contacts_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($db, "ALTER TABLE contacts ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL");
    @mysqli_query($db, "ALTER TABLE contacts ADD COLUMN IF NOT EXISTS contact_name VARCHAR(150) NOT NULL DEFAULT ''");
    @mysqli_query($db, "ALTER TABLE contacts ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(50) NOT NULL DEFAULT ''");
}

function normalizeContactPhone(?string $phone): string {
    $phone = trim((string) ($phone ?? ''));
    if ($phone === '') {
        return '';
    }

    $phone = str_replace([" ", "\t", "\n", "\r"], '', $phone);
    $phone = preg_replace('/[^0-9+]/', '', $phone) ?? $phone;

    if ($phone === '') {
        return '';
    }

    if (strpos($phone, '+') === 0) {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        return $digits === '' ? '' : '+' . $digits;
    }

    $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }

    if (strlen($digits) > 10 && strpos($digits, '92') === 0) {
        return '+' . $digits;
    }

    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        return '+92' . substr($digits, 1);
    }

    if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
        return '+92' . substr($digits, 1);
    }

    if (strlen($digits) === 12 && substr($digits, 0, 2) === '92') {
        return '+' . $digits;
    }

    return '+' . $digits;
}

function normalizeContactEmail(?string $email): string {
    $email = trim((string) ($email ?? ''));
    if ($email === '') {
        return '';
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function getUserContactsForSos(mysqli $db, int $userId): array {
    $contacts = [];

    $stmt = $db->prepare('SELECT id, contact_name AS name, contact_phone AS phone, email FROM contacts WHERE user_id = ? ORDER BY id DESC');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $contacts[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'relationship' => 'Contact',
                'phone' => normalizeContactPhone((string) ($row['phone'] ?? '')),
                'email' => normalizeContactEmail((string) ($row['email'] ?? '')),
            ];
        }
        $stmt->close();
    }

    if (!empty($contacts)) {
        return $contacts;
    }

    $stmt = $db->prepare('SELECT id, name, relationship, phone, email FROM emergency_contacts WHERE user_id = ? ORDER BY id DESC');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $contacts[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'relationship' => (string) ($row['relationship'] ?? 'Contact'),
                'phone' => normalizeContactPhone((string) ($row['phone'] ?? '')),
                'email' => normalizeContactEmail((string) ($row['email'] ?? '')),
            ];
        }
        $stmt->close();
    }

    return $contacts;
}

function sendEmailAlerts(array $contacts, string $userName, float $lat = null, float $lon = null, string $address = '', ?string $primaryUserEmail = null, ?string $timestamp = null): array {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return ['success' => false, 'error' => 'PHPMailer class is not available.'];
    }

    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;
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

    foreach ($contacts as $contact) {
        $contactEmail = normalizeContactEmail((string) ($contact['email'] ?? ''));
        $contactName = (string) ($contact['name'] ?? 'Emergency Contact');

        if ($contactEmail === '') {
            if ($fallbackEmail !== '') {
                error_log('Emergency contact ' . $contactName . ' has no valid saved email. Falling back to primary user email: ' . $fallbackEmail);
                $contactEmail = $fallbackEmail;
            } else {
                error_log('Emergency contact ' . $contactName . ' has no valid saved email and no primary user email fallback is available.');
                continue;
            }
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
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
            $mail->setFrom($smtpUser, 'Defenza Emergency SOS');
            $mail->addAddress($contactEmail, $contactName);
            $mail->Subject = '🚨 Emergency SOS Alert from Defenza';
            $mail->isHTML(true);
            $mail->Body = buildEmergencyHtmlMessage($userName, $lat, $lon, $address, $messageTimestamp);
            $latText = $lat !== null ? (string) $lat : '0';
            $lonText = $lon !== null ? (string) $lon : '0';
            $mail->AltBody = "EMERGENCY ALERT: This user has triggered an SOS alert on Defenza. Please check on them immediately.\nSender: " . $userName . "\nCurrent Time & Date: " . $messageTimestamp . "\nLocation: https://www.google.com/maps?q=" . rawurlencode($latText . ',' . $lonText) . "\nAddress: " . $address;

            $mail->send();
            $sentCount++;
        } catch (Throwable $exception) {
            $smtpError = $mail->ErrorInfo ?: $exception->getMessage();
            error_log('PHPMailer SMTP error for ' . $contactEmail . ': ' . $smtpError);
            $errors[] = 'Email delivery failed: ' . $smtpError;
        }
    }

    if ($sentCount > 0) {
        return ['success' => true, 'message' => 'Emergency SOS Alert dispatched to all contacts successfully!', 'error' => null, 'sent_count' => $sentCount, 'errors' => $errors];
    }

    return ['success' => false, 'message' => 'Email delivery failed: ' . (!empty($errors) ? $errors[0] : 'Unable to dispatch emergency SOS email alerts.'), 'error' => !empty($errors) ? $errors[0] : 'Unable to dispatch emergency SOS email alerts.', 'sent_count' => 0, 'errors' => $errors];
}

function sendSmsAlerts(array $contacts, string $userName, float $lat = null, float $lon = null, string $address = ''): array {
    $twilioSid = TWILIO_SID;
    $twilioToken = TWILIO_TOKEN;
    $twilioFrom = TWILIO_PHONE_NUMBER;

    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'cURL is not available.'];
    }

    if ($twilioSid === 'YOUR_TWILIO_SID' || $twilioToken === 'YOUR_TWILIO_TOKEN' || $twilioFrom === 'YOUR_TWILIO_PHONE_NUMBER') {
        return ['success' => false, 'error' => 'Twilio credentials are not configured.'];
    }

    $messageBody = 'EMERGENCY ALERT: ' . $userName . ' has triggered an SOS alert on Defenza at ' . date('h:i A') . '. Location: https://maps.google.com/?q=' . rawurlencode((string) $lat) . ',' . rawurlencode((string) $lon);

    $sentCount = 0;
    $errors = [];
    $rateLimited = false;

    foreach ($contacts as $contact) {
        $phone = normalizeContactPhone((string) ($contact['phone'] ?? ''));
        if ($phone === '') {
            continue;
        }

        $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($twilioSid) . '/Messages.json');
        if (!$ch) {
            $errors[] = 'Unable to initialize cURL.';
            continue;
        }

        $payload = http_build_query([
            'From' => $twilioFrom,
            'To' => $phone,
            'Body' => $messageBody,
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $twilioSid . ':' . $twilioToken);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            $errors[] = 'Twilio request failed for ' . $phone . ': ' . $curlError;
            continue;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $sentCount++;
            continue;
        }

        $decoded = json_decode($responseBody, true);
        $message = trim((string) ($decoded['message'] ?? ''));
        if ($message === '') {
            $message = 'Twilio returned HTTP ' . $httpCode;
        }
        // detect rate limit / trial limit conditions
        $lower = strtolower($message);
        if ($httpCode === 429 || strpos($lower, 'limit') !== false || strpos($lower, 'trial') !== false || strpos($lower, 'daily') !== false) {
            $rateLimited = true;
        }
        $errors[] = 'Twilio API error for ' . $phone . ': ' . $message;
    }

    if ($sentCount > 0) {
        return ['success' => true, 'error' => null, 'warning' => false, 'sent_count' => $sentCount, 'rate_limited' => $rateLimited];
    }

    return ['success' => false, 'error' => implode('; ', $errors), 'warning' => false, 'rate_limited' => $rateLimited];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $user = getAuthenticatedUser();
    $userId = $user['id'] ?? null;

    if (!$userId) {
        throw new Exception('User not authenticated');
    }

    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $db->query("CREATE TABLE IF NOT EXISTS emergency_contacts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        name VARCHAR(150) NOT NULL,
        relationship VARCHAR(80) NOT NULL DEFAULT 'Contact',
        phone VARCHAR(50) NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_emergency_contacts_user_id (user_id),
        CONSTRAINT fk_emergency_contacts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS sos_alerts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        accuracy DECIMAL(8,2) DEFAULT NULL,
        address VARCHAR(255) NOT NULL DEFAULT '',
        status ENUM('active','resolved') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_sos_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS live_locations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        accuracy DECIMAL(8,2) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        status VARCHAR(32) DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_live_locations_user_id (user_id),
        KEY idx_live_locations_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) DEFAULT NULL");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) DEFAULT NULL");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS accuracy DECIMAL(8,2) DEFAULT NULL");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS status VARCHAR(32) DEFAULT 'active'");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    @mysqli_query($db, "ALTER TABLE live_locations ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $lat = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float) $_POST['latitude'] : null;
    $lon = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float) $_POST['longitude'] : null;
    $acc = isset($_POST['accuracy']) && is_numeric($_POST['accuracy']) ? (float) $_POST['accuracy'] : null;
    $address = trim($_POST['address'] ?? 'Unknown location');
    $locationSource = normalizeLocationSource($_POST['location_source'] ?? 'gps');
    $locationCapturedAt = normalizeLocationCapturedAt($_POST['timestamp'] ?? null);

    error_log('[Defenza Debug] PHP SOS received payload ' . json_encode(['post' => $_POST, 'lat' => $lat, 'lon' => $lon, 'acc' => $acc, 'address' => $address, 'source' => $locationSource, 'captured_at' => $locationCapturedAt]));

    ensureContactsTable($db);
    $contacts = getUserContactsForSos($db, $userId);

    $contactCount = count($contacts);
    if ($contactCount === 0) {
        throw new Exception('No emergency contacts found. Add a contact before sending an SOS alert.');
    }

    $userName = (string) ($user['full_name'] ?? 'User');
    $status = 'active';
    $eventTimestamp = (new DateTime('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
    $latestLocation = getLatestLiveLocation($userId, $db);
    $resolvedLat = $lat;
    $resolvedLon = $lon;
    $resolvedAcc = $acc;
    $resolvedAddress = trim($address) !== '' ? $address : 'Address could not be determined.';
    $resolvedGoogleMapsUrl = buildGoogleMapsUrl($resolvedLat, $resolvedLon);

    if ($resolvedLat === null || $resolvedLon === null) {
        $latestLat = isset($latestLocation['latitude']) && is_numeric($latestLocation['latitude']) ? (float) $latestLocation['latitude'] : null;
        $latestLon = isset($latestLocation['longitude']) && is_numeric($latestLocation['longitude']) ? (float) $latestLocation['longitude'] : null;
        if ($latestLat !== null && $latestLon !== null) {
            $resolvedLat = $latestLat;
            $resolvedLon = $latestLon;
            $resolvedAcc = isset($latestLocation['accuracy']) && is_numeric($latestLocation['accuracy']) ? (float) $latestLocation['accuracy'] : $resolvedAcc;
            $resolvedAddress = trim((string) ($latestLocation['address'] ?? '')) !== '' ? (string) $latestLocation['address'] : $resolvedAddress;
            $resolvedGoogleMapsUrl = buildGoogleMapsUrl($resolvedLat, $resolvedLon);
        }
    }

    if ($resolvedLat === null || $resolvedLon === null) {
        throw new Exception('Unable to verify a live location for this SOS event.');
    }

    $resolvedAddress = resolveReadableAddress($resolvedLat, $resolvedLon) ?: $resolvedAddress;
    $resolvedGoogleMapsUrl = buildGoogleMapsUrl($resolvedLat, $resolvedLon);

    error_log('[Defenza Debug] SOS browser GPS lat=' . (string) $resolvedLat . ' lon=' . (string) $resolvedLon . ' acc=' . (string) $resolvedAcc . ' address=' . $address . ' source=' . $locationSource . ' captured_at=' . ($locationCapturedAt ?? 'null'));
    error_log('[Defenza Debug] SOS verified lat=' . (string) $resolvedLat . ' lon=' . (string) $resolvedLon . ' address=' . $resolvedAddress . ' google_maps_url=' . $resolvedGoogleMapsUrl);

    // Persist the alert and live location first so logs are never lost even if notifications fail
    $alertStmt = $db->prepare('INSERT INTO sos_alerts (user_id, latitude, longitude, accuracy, address, status, created_at, updated_at, event_timestamp, google_maps_url, location_source, location_captured_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$alertStmt) {
        throw new Exception('Failed to prepare alert insert');
    }
    $alertStmt->bind_param('idddssssssss', $userId, $resolvedLat, $resolvedLon, $resolvedAcc, $resolvedAddress, $status, $eventTimestamp, $eventTimestamp, $eventTimestamp, $resolvedGoogleMapsUrl, $locationSource, $locationCapturedAt);
    $alertStmt->execute();
    $insertedId = $alertStmt->insert_id;
    $alertStmt->close();

    $locationInsertStmt = $db->prepare('INSERT INTO live_locations (user_id, latitude, longitude, accuracy, address, status, created_at, updated_at, location_source, location_captured_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), accuracy = VALUES(accuracy), address = VALUES(address), status = VALUES(status), created_at = COALESCE(created_at, VALUES(created_at)), updated_at = VALUES(updated_at), location_source = VALUES(location_source), location_captured_at = VALUES(location_captured_at)');
    if ($locationInsertStmt) {
        $locationInsertStmt->bind_param('idddssssss', $userId, $resolvedLat, $resolvedLon, $resolvedAcc, $resolvedAddress, $status, $eventTimestamp, $eventTimestamp, $locationSource, $locationCapturedAt);
        $locationInsertStmt->execute();
        $locationInsertStmt->close();
    }

    $liveLocationRow = getLatestLiveLocation($userId, $db);
    if (!empty($liveLocationRow) && isset($liveLocationRow['id'])) {
        $locationMetaStmt = $db->prepare('UPDATE live_locations SET event_timestamp = ?, google_maps_url = ? WHERE id = ?');
        if ($locationMetaStmt) {
            $locationMetaStmt->bind_param('ssi', $eventTimestamp, $resolvedGoogleMapsUrl, $liveLocationRow['id']);
            $locationMetaStmt->execute();
            $locationMetaStmt->close();
        }
    }

    // Fetch the exact saved coordinates and timestamp from database to ensure consistency
    $alertSelectStmt = $db->prepare('SELECT latitude, longitude, accuracy, address, created_at, event_timestamp, google_maps_url, location_source, location_captured_at FROM sos_alerts WHERE id = ? LIMIT 1');
    if (!$alertSelectStmt) {
        throw new Exception('Failed to prepare alert select query');
    }
    $alertSelectStmt->bind_param('i', $insertedId);
    $alertSelectStmt->execute();
    $alertResult = $alertSelectStmt->get_result();
    $alertRow = $alertResult->fetch_assoc();
    $alertSelectStmt->close();
    
    if (!$alertRow) {
        throw new Exception('Failed to retrieve saved alert from database');
    }
    
    $dbLat = (float) $resolvedLat;
    $dbLon = (float) $resolvedLon;
    $dbAddress = $resolvedAddress;
    $dbTimestamp = $eventTimestamp;
    $dbGoogleMapsUrl = $resolvedGoogleMapsUrl;

    $existingLocationStmt = $db->prepare('SELECT id FROM live_locations WHERE user_id = ? LIMIT 1');
    if ($existingLocationStmt) {
        $existingLocationStmt->bind_param('i', $userId);
        $existingLocationStmt->execute();
        $existingLocationResult = $existingLocationStmt->get_result();
        $existingLocation = $existingLocationResult ? $existingLocationResult->fetch_assoc() : null;
        $existingLocationStmt->close();

        if ($existingLocation) {
            $locationStmt = $db->prepare('UPDATE live_locations SET latitude = ?, longitude = ?, accuracy = ?, address = ?, status = ?, updated_at = ?, event_timestamp = ?, google_maps_url = ?, location_source = ?, location_captured_at = ? WHERE user_id = ?');
            if ($locationStmt) {
                $locationStmt->bind_param('dddsssssssi', $resolvedLat, $resolvedLon, $resolvedAcc, $resolvedAddress, $status, $eventTimestamp, $eventTimestamp, $resolvedGoogleMapsUrl, $locationSource, $locationCapturedAt, $userId);
                $locationStmt->execute();
                $locationStmt->close();
            }
        } else {
            $locationStmt = $db->prepare('INSERT INTO live_locations (user_id, latitude, longitude, accuracy, address, status, created_at, updated_at, event_timestamp, google_maps_url, location_source, location_captured_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if ($locationStmt) {
                $locationStmt->bind_param('idddssssssss', $userId, $resolvedLat, $resolvedLon, $resolvedAcc, $resolvedAddress, $status, $eventTimestamp, $eventTimestamp, $eventTimestamp, $resolvedGoogleMapsUrl, $locationSource, $locationCapturedAt);
                $locationStmt->execute();
                $locationStmt->close();
            }
        }
    }

    $responseData = [
        'success' => true,
        'message' => 'SOS alert received. Notifications are being delivered.',
        'alert_id' => $insertedId ? 'SOS-' . $insertedId : 'SOS-' . rand(100000, 999999),
        'contacts' => $contacts,
        'contacts_notified' => $contactCount,
        'email_sent' => false,
        'sms_sent' => false,
        'sms_rate_limited' => false,
        'debug_error' => null,
        'location' => [
            'lat' => $dbLat,
            'lon' => $dbLon,
            'accuracy' => $resolvedAcc,
            'address' => $dbAddress,
            'google_maps_url' => $dbGoogleMapsUrl,
            'location_source' => $locationSource,
            'location_captured_at' => $locationCapturedAt,
        ],
        'timestamp' => $dbTimestamp,
    ];

    $jsonResponse = json_encode($responseData);
    if ($jsonResponse !== false) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Connection: close');
        header('Content-Length: ' . strlen($jsonResponse));
        echo $jsonResponse;
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            @flush();
        }
    }

    try {
        $emailResult = sendEmailAlerts($contacts, $userName, $dbLat, $dbLon, $dbAddress, $user['email'] ?? null, $dbTimestamp);
        error_log('[Defenza Debug] PHP SOS email coordinates ' . json_encode(['lat' => $dbLat, 'lon' => $dbLon, 'address' => $dbAddress, 'emailResult' => $emailResult]));
        $smsResult = sendSmsAlerts($contacts, $userName, $dbLat, $dbLon, $dbAddress);
        error_log('[Defenza Debug] PHP SOS sms result ' . json_encode($smsResult));
    } catch (Throwable $notificationException) {
        error_log('[Defenza Debug] Background notification error: ' . $notificationException->getMessage());
    }

    return;
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_error' => $e->getMessage(),
        'message' => $e->getMessage(),
    ]);
}
