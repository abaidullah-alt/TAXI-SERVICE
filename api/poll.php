<?php
declare(strict_types=1);
header('Content-Type: application/json');

const BOOKINGS = __DIR__ . '/../data/bookings.json';
const MESSAGES = __DIR__ . '/../data/messages.json';

function respond(array $p, int $c = 200): void { http_response_code($c); echo json_encode($p); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(['ok' => false, 'error' => 'Method not allowed'], 405);

$ref = strtoupper(trim((string)($_GET['ref'] ?? '')));
$after = (int)($_GET['after'] ?? 0);
if ($ref === '') respond(['ok' => false, 'error' => 'Missing reference'], 422);

function read_json(string $file): array {
    if (!is_file($file)) return [];
    $fp = @fopen($file, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN); fclose($fp);
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

$booking = null;
foreach (read_json(BOOKINGS) as $b) {
    if (($b['ref'] ?? '') === $ref) { $booking = $b; break; }
}
if (!$booking) respond(['ok' => false, 'error' => 'Booking not found'], 404);

$out = [
    'ref' => $booking['ref'], 'status' => $booking['status'] ?? 'pending',
    'driver' => $booking['driver'] ?? '', 'pickup' => $booking['pickup'] ?? '',
    'dropoff' => $booking['dropoff'] ?? '', 'date' => $booking['date'] ?? '',
    'time' => $booking['time'] ?? '', 'vehicle' => $booking['vehicle'] ?? 'saloon',
    'passengers' => $booking['passengers'] ?? 1, 'estimate' => $booking['estimate'] ?? '',
];

$messages = [];
foreach (read_json(MESSAGES) as $m) {
    if (($m['ref'] ?? '') === $ref && (int)($m['id'] ?? 0) > $after) $messages[] = $m;
}
usort($messages, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);

respond(['ok' => true, 'booking' => $out, 'messages' => $messages]);