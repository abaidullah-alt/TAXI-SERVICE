<?php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

const BOOKINGS = __DIR__ . '/../data/bookings.json';
const MESSAGES = __DIR__ . '/../data/messages.json';

function respond(array $p, int $c = 200): void { http_response_code($c); echo json_encode($p); exit; }
function clean(string $k): string { return htmlspecialchars(trim((string)($_POST[$k] ?? '')), ENT_QUOTES, 'UTF-8'); }
function load_json(string $f): array {
    if (!is_file($f)) return [];
    $fp = @fopen($f, 'r'); if (!$fp) return [];
    flock($fp, LOCK_SH); $d = stream_get_contents($fp);
    flock($fp, LOCK_UN); fclose($fp);
    return $d ? (json_decode($d, true) ?: []) : [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Method not allowed'], 405);

$ref  = clean('ref');
$side = clean('side');
$text = clean('text');
if ($ref === '' || $text === '') respond(['ok' => false, 'error' => 'Missing message.'], 422);
if (!in_array($side, ['passenger', 'driver'], true)) respond(['ok' => false, 'error' => 'Invalid side.'], 422);

$booking = null;
foreach (load_json(BOOKINGS) as $b) { if (($b['ref'] ?? '') === $ref) { $booking = $b; break; } }
if (!$booking) respond(['ok' => false, 'error' => 'Booking not found'], 404);

// Authorisation: office staff OR the assigned driver may send as "driver"
if ($side === 'driver') {
    if (!empty($_SESSION['rline_admin'])) {
        // office staff — allowed
    } elseif (!empty($_SESSION['rline_driver'])) {
        if (($booking['driver_id'] ?? '') !== $_SESSION['rline_driver']['id']) {
            respond(['ok' => false, 'error' => 'Not your job'], 403);
        }
    } else {
        respond(['ok' => false, 'error' => 'Login required'], 403);
    }
}

// Passenger rate limit
if ($side === 'passenger') {
    $limitFile = sys_get_temp_dir() . '/rline_msg_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x') . '.txt';
    if (is_file($limitFile) && (time() - (int)file_get_contents($limitFile)) < 10) {
        respond(['ok' => false, 'error' => 'Please wait a moment before sending again.'], 429);
    }
    @file_put_contents($limitFile, (string)time());
}

// Append message
$dir = dirname(MESSAGES);
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$fp = @fopen(MESSAGES, 'c+');
if (!$fp) respond(['ok' => false, 'error' => 'Storage error.'], 500);
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$msgs = $raw ? (json_decode($raw, true) ?: []) : [];
$maxId = 0;
foreach ($msgs as $m) { $maxId = max($maxId, (int)($m['id'] ?? 0)); }
$id = $maxId + 1;
$msgs[] = ['id' => $id, 'ref' => $ref, 'side' => $side, 'text' => $text, 'time' => date('c')];
ftruncate($fp, 0); rewind($fp);
fwrite($fp, json_encode($msgs, JSON_PRETTY_PRINT));
flock($fp, LOCK_UN); fclose($fp);

respond(['ok' => true, 'id' => $id]);