<?php
declare(strict_types=1);
header('Content-Type: application/json');

const BOOKINGS = __DIR__ . '/../data/bookings.json';
const TO_EMAIL  = 'support@rlinetaxis.com';

// ===== WhatsApp alert to office phone (CallMeBot — free) =====
// Setup: add the CallMeBot WhatsApp number as a contact on the OFFICE phone,
// send it "I allow callmebot to send me messages", it replies with your API key.
// Leave WA_PHONE or WA_KEY empty to disable.
const WA_PHONE = '';   // e.g. '+447700900123' (office phone with WhatsApp)
const WA_KEY   = '';    // your CallMeBot API key

function wa_send(string $text): void {
    if (WA_PHONE === '' || WA_KEY === '') return;
    $url = 'https://api.callmebot.com/whatsapp.php?phone=' . urlencode(WA_PHONE)
         . '&text=' . urlencode($text) . '&apikey=' . urlencode(WA_KEY);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6]);
        @curl_exec($ch); curl_close($ch);
    } else {
        @file_get_contents($url);
    }
}

function respond(array $p, int $c = 200): void { http_response_code($c); echo json_encode($p); exit; }
function clean(string $k): string { return htmlspecialchars(trim((string)($_POST[$k] ?? '')), ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Method not allowed'], 405);

$name = clean('name');   $phone = clean('phone');   $email = clean('email');
$pickup = clean('pickup'); $dropoff = clean('dropoff');
$date = clean('date');   $time = clean('time');
$passengers = max(1, min(8, (int)($_POST['passengers'] ?? 1)));
$vehicle = clean('vehicle'); $estimate = clean('estimate'); $notes = clean('notes');

if ($name === '' || $phone === '' || $pickup === '' || $dropoff === '' || $date === '' || $time === '') {
    respond(['ok' => false, 'error' => 'Please complete all required fields.'], 422);
}
if (!in_array($vehicle, ['saloon', 'estate', 'mpv6', 'mpv8'], true)) $vehicle = 'saloon';

$dir = dirname(BOOKINGS);
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$fp = @fopen(BOOKINGS, 'c+');
if (!$fp) respond(['ok' => false, 'error' => 'Storage error — please call 01384 886601.'], 500);

flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$all = $raw ? (json_decode($raw, true) ?: []) : [];

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
do {
    $ref = 'RL-';
    for ($i = 0; $i < 6; $i++) $ref .= $chars[random_int(0, strlen($chars) - 1)];
    $exists = false;
    foreach ($all as $b) { if (($b['ref'] ?? '') === $ref) { $exists = true; break; } }
} while ($exists);

$all[] = [
    'ref' => $ref, 'received' => date('c'), 'name' => $name, 'phone' => $phone,
    'email' => $email, 'pickup' => $pickup, 'dropoff' => $dropoff,
    'date' => $date, 'time' => $time, 'passengers' => $passengers,
    'vehicle' => $vehicle, 'estimate' => $estimate, 'notes' => $notes,
    'status' => 'pending', 'driver' => '', 'driver_id' => '',
];
ftruncate($fp, 0); rewind($fp);
fwrite($fp, json_encode($all, JSON_PRETTY_PRINT));
flock($fp, LOCK_UN); fclose($fp);

// Email the office
$host = $_SERVER['HTTP_HOST'] ?? 'rlinetaxis.com';
$body = "New booking $ref\n\n$name ($phone" . ($email ? " / $email" : '') . ")\n"
      . "From: $pickup\nTo: $dropoff\nWhen: $date $time\n"
      . "Passengers: $passengers | Vehicle: $vehicle | Estimate: $estimate\nNotes: $notes\n";
@mail(TO_EMAIL, "New booking $ref - R Line Taxis", $body,
    "From: website@$host\r\n" . ($email ? "Reply-To: $email\r\n" : ''));

// Email the passenger their reference + tracking link
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    @mail($email, "Your R Line Taxis booking $ref",
        "Thank you $name,\n\nYour booking reference is $ref.\n"
        . "Track your taxi and message your driver here:\n$scheme://$host/track.html?ref=$ref\n\n"
        . "Pickup: $pickup\nDestination: $dropoff\nDate/Time: $date $time\n\nR Line Taxis - 01384 886601",
        "From: website@$host");
}

// WhatsApp alert to the office phone
wa_send("🚕 NEW BOOKING {$ref}\n{$name} — {$phone}\n{$pickup} → {$dropoff}\n{$date} at {$time}\nPassengers: {$passengers} · " . ucfirst($vehicle) . "\nEstimate: {$estimate}");

respond(['ok' => true, 'ref' => $ref]);