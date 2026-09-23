<?php
declare(strict_types=1);
header('Content-Type: application/json');

const TO_EMAIL = 'support@rlinetaxis.com';
const DATA_FILE = __DIR__ . '/../data/enquiries.json';
const MIN_SECONDS_BETWEEN = 30;

function respond(array $payload, int $code): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => 'Method not allowed'], 405);
}

// Honeypot: real users never see/fill the hidden "website" field
if (!empty($_POST['website'] ?? '')) {
    respond(['ok' => true]);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Rate limit: max 1 submission per IP per 30 seconds
$limitFile = sys_get_temp_dir() . '/rline_' . md5($ip) . '.txt';
if (is_file($limitFile) && (time() - (int)file_get_contents($limitFile)) < MIN_SECONDS_BETWEEN) {
    respond(['ok' => false, 'error' => 'Please wait a moment before submitting again.'], 429);
}

function clean(string $key): string {
    return htmlspecialchars(trim((string)($_POST[$key] ?? '')), ENT_QUOTES, 'UTF-8');
}

$name    = clean('name');
$phone   = clean('phone');
$email   = clean('email');
$pickup  = clean('pickup');
$dropoff = clean('dropoff');
$date    = clean('date');
$time    = clean('time');
$notes   = clean('notes');

if ($name === '' || $phone === '' || $pickup === '' || $dropoff === '') {
    respond(['ok' => false, 'error' => 'Please fill in all required fields.'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}

$entry = [
    'id'      => date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
    'received'=> date('c'),
    'ip'      => $ip,
    'name'    => $name, 'phone' => $phone, 'email' => $email,
    'pickup'  => $pickup, 'dropoff' => $dropoff,
    'date'    => $date, 'time' => $time, 'notes' => $notes,
    'status'  => 'new',
];

$dir = dirname(DATA_FILE);
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$fp = @fopen(DATA_FILE, 'c+');
if (!$fp) { respond(['ok' => false, 'error' => 'Storage error — please call us instead.'], 500); }
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$all = $raw ? (json_decode($raw, true) ?: []) : [];
$all[] = $entry;
ftruncate($fp, 0); rewind($fp);
fwrite($fp, json_encode($all, JSON_PRETTY_PRINT));
flock($fp, LOCK_UN); fclose($fp);
file_put_contents($limitFile, (string)time());

if (TO_EMAIL !== '') {
    $subject = "New enquiry from {$name} - R Line Taxis";
    $body = "New website enquiry:\n\n"
          . "Name: {$name}\nPhone: {$phone}\nEmail: {$email}\n"
          . "Pickup: {$pickup}\nDrop-off: {$dropoff}\n"
          . "Date: {$date}  Time: {$time}\nNotes: {$notes}\n";
    @mail(TO_EMAIL, $subject, $body,
        "From: website@" . ($_SERVER['HTTP_HOST'] ?? 'rlinetaxis.com') . "\r\nReply-To: {$email}");
}

respond(['ok' => true, 'message' => 'Thank you — we received your enquiry and will be in touch shortly.']);