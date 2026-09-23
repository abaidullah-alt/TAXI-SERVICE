<?php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

const DRIVERS  = __DIR__ . '/../data/drivers.json';
const BOOKINGS = __DIR__ . '/../data/bookings.json';

function respond(array $p, int $c = 200): void { http_response_code($c); echo json_encode($p); exit; }
function load_json(string $f): array {
    if (!is_file($f)) return [];
    $fp = @fopen($f, 'r'); if (!$fp) return [];
    flock($fp, LOCK_SH); $d = stream_get_contents($fp);
    flock($fp, LOCK_UN); fclose($fp);
    return $d ? (json_decode($d, true) ?: []) : [];
}
function save_json(string $f, array $data): bool {
    $fp = @fopen($f, 'c+'); if (!$fp) return false;
    flock($fp, LOCK_EX); ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN); fclose($fp);
    return true;
}
function clean(string $k): string { return htmlspecialchars(trim((string)($_POST[$k] ?? '')), ENT_QUOTES, 'UTF-8'); }

// ---- Login (with brute-force protection) ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $af = sys_get_temp_dir() . '/rline_dlogin_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x') . '.json';
    $att = is_file($af) ? (json_decode((string)file_get_contents($af), true) ?: ['n' => 0, 't' => 0]) : ['n' => 0, 't' => 0];
    if ((time() - (int)($att['t'] ?? 0)) > 600) $att = ['n' => 0, 't' => time()];
    if ((int)$att['n'] >= 5) respond(['ok' => false, 'error' => 'Too many attempts — try again in a few minutes.'], 429);

    $pin = preg_replace('/\D/', '', (string)($_POST['pin'] ?? ''));
    foreach (load_json(DRIVERS) as $d) {
        if (($d['pin'] ?? '') !== '' && hash_equals((string)$d['pin'], $pin)) {
            @unlink($af);
            $_SESSION['rline_driver'] = ['id' => $d['id'], 'name' => $d['name']];
            respond(['ok' => true, 'name' => $d['name']]);
        }
    }
    $att['n'] = (int)$att['n'] + 1; $att['t'] = time();
    @file_put_contents($af, json_encode($att));
    respond(['ok' => false, 'error' => 'Wrong PIN.'], 401);
}

// ---- Logout ----
if (($_GET['action'] ?? '') === 'logout') { unset($_SESSION['rline_driver']); respond(['ok' => true]); }

$driver = $_SESSION['rline_driver'] ?? null;
if (!$driver) respond(['ok' => false, 'error' => 'Not logged in'], 401);
$me = $driver['id'];

// ---- My jobs (GET) ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $jobs = [];
    foreach (load_json(BOOKINGS) as $b) {
        if (($b['driver_id'] ?? '') === $me) $jobs[] = $b;
    }
    $active = ['pending', 'confirmed', 'on_the_way'];
    usort($jobs, function ($a, $b) use ($active) {
        $ai = in_array($a['status'] ?? '', $active, true) ? 0 : 1;
        $bi = in_array($b['status'] ?? '', $active, true) ? 0 : 1;
        if ($ai !== $bi) return $ai <=> $bi;
        return strcmp(($a['date'] ?? '') . ($a['time'] ?? ''), ($b['date'] ?? '') . ($b['time'] ?? ''));
    });
    respond(['ok' => true, 'name' => $driver['name'], 'jobs' => array_values($jobs)]);
}

// ---- Update status ----
if (($_POST['action'] ?? '') === 'set_status') {
    $ref = clean('ref'); $status = clean('status');
    if (!in_array($status, ['confirmed', 'on_the_way', 'completed'], true)) {
        respond(['ok' => false, 'error' => 'Invalid status'], 422);
    }
    $bookings = load_json(BOOKINGS); $found = false;
    foreach ($bookings as &$b) {
        if (($b['ref'] ?? '') === $ref && ($b['driver_id'] ?? '') === $me) {
            $b['status'] = $status; $found = true;
        }
    }
    unset($b);
    if (!$found) respond(['ok' => false, 'error' => 'Not your job'], 403);
    respond(['ok' => save_json(BOOKINGS, $bookings)]);
}

respond(['ok' => false, 'error' => 'Unknown action'], 400);