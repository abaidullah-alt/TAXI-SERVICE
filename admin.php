<?php
declare(strict_types=1);
session_start();

// ⚠️⚠️ CHANGE THIS PASSWORD BEFORE GOING LIVE ⚠️⚠️
const ADMIN_PASSWORD = 'change-me-2026';
const DATA_DIR  = __DIR__ . '/data/';
const BOOKINGS  = DATA_DIR . 'bookings.json';
const ENQUIRIES = DATA_DIR . 'enquiries.json';
const DRIVERS   = DATA_DIR . 'drivers.json';
const STATUSES  = ['pending', 'confirmed', 'on_the_way', 'completed', 'cancelled'];
const VMAP      = ['saloon' => 'Saloon', 'estate' => 'Estate', 'mpv6' => 'MPV 6', 'mpv8' => 'MPV 8'];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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
function jout(array $p, int $c = 200): void { http_response_code($c); echo json_encode($p); exit; }

// Login / logout
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['password'])) {
    if (hash_equals(ADMIN_PASSWORD, (string)$_POST['password'])) {
        $_SESSION['rline_admin'] = true; header('Location: admin.php'); exit;
    }
    $error = 'Wrong password.';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
$loggedIn = !empty($_SESSION['rline_admin']);

// AJAX: stats for the new-booking beep
if ($loggedIn && ($_GET['ajax'] ?? '') === 'stats') {
    jout(['ok' => true, 'bookings' => count(load_json(BOOKINGS)), 'enquiries' => count(load_json(ENQUIRIES))]);
}

// AJAX actions
if ($loggedIn && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];
    $ref = trim((string)($_POST['ref'] ?? ''));

    if ($action === 'set_status') {
        $bookings = load_json(BOOKINGS); $found = false;
        $s = (string)($_POST['status'] ?? '');
        if (!in_array($s, STATUSES, true)) jout(['ok' => false, 'error' => 'Invalid status'], 422);
        foreach ($bookings as &$b) { if (($b['ref'] ?? '') === $ref) { $b['status'] = $s; $found = true; } }
        unset($b);
        if (!$found) jout(['ok' => false, 'error' => 'Booking not found'], 404);
        jout(['ok' => save_json(BOOKINGS, $bookings)]);
    }

    if ($action === 'assign') {
        $driverId = trim((string)($_POST['driver_id'] ?? ''));
        $dname = '';
        foreach (load_json(DRIVERS) as $d) { if (($d['id'] ?? '') === $driverId) $dname = $d['name']; }
        $bookings = load_json(BOOKINGS); $found = false;
        foreach ($bookings as &$b) {
            if (($b['ref'] ?? '') === $ref) { $b['driver_id'] = $driverId; $b['driver'] = $dname; $found = true; }
        }
        unset($b);
        if (!$found) jout(['ok' => false, 'error' => 'Booking not found'], 404);
        jout(['ok' => save_json(BOOKINGS, $bookings)]);
    }

    if ($action === 'add_driver') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 40);
        if ($name === '') jout(['ok' => false, 'error' => 'Name required'], 422);
        $drivers = load_json(DRIVERS);
        do { $pin = (string)random_int(1000, 9999); }
        while (in_array($pin, array_column($drivers, 'pin'), true));
        $drivers[] = ['id' => 'd' . substr((string)time(), -6) . random_int(10, 99), 'name' => $name, 'pin' => $pin, 'created' => date('c')];
        jout(['ok' => save_json(DRIVERS, $drivers), 'pin' => $pin]);
    }

    if ($action === 'del_driver') {
        $driverId = trim((string)($_POST['driver_id'] ?? ''));
        $drivers = array_values(array_filter(load_json(DRIVERS), fn($d) => ($d['id'] ?? '') !== $driverId));
        jout(['ok' => save_json(DRIVERS, $drivers)]);
    }

    jout(['ok' => false, 'error' => 'Unknown action'], 400);
}

$tab = in_array($_GET['tab'] ?? '', ['bookings', 'messages', 'enquiries', 'drivers']) ? ($_GET['tab'] ?? 'bookings') : 'bookings';
$bookings   = array_reverse(load_json(BOOKINGS));
$enquiries  = array_reverse(load_json(ENQUIRIES));
$drivers    = load_json(DRIVERS);
$selRef     = trim((string)($_GET['ref'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Office Panel · R Line Taxis</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#0d1b2a;color:#e8e4da;margin:0;padding:36px 20px}
.box{max-width:1200px;margin:0 auto}
h1{font-size:1.4rem;color:#c9a24b}
h1 a.logout{float:right;font-size:.85rem;color:#8b95a2;text-decoration:none}
.tabs{display:flex;gap:8px;margin:22px 0;flex-wrap:wrap}
.tabs a{padding:10px 20px;background:#13263b;color:#aeb8c4;text-decoration:none;border-radius:4px;font-size:.88rem}
.tabs a.on{background:#c9a24b;color:#0d1b2a;font-weight:bold}
table{width:100%;border-collapse:collapse;background:#13263b;font-size:.85rem}
th,td{padding:10px 12px;border:1px solid rgba(255,255,255,.1);text-align:left;vertical-align:top}
th{background:#0a1420;color:#c9a24b}
tr:nth-child(even){background:rgba(255,255,255,.03)}
td a{color:#e6cd8f}
select,input{padding:8px 10px;border-radius:3px;border:1px solid #3a4a5e;background:#0a1420;color:#fff;font-size:.85rem}
button{padding:12px 24px;background:#c9a24b;border:none;border-radius:4px;font-weight:bold;cursor:pointer}
button.danger{background:#8f2f2f;color:#fff}
.err{color:#e2b3b3;margin-top:12px}
.amsgs{height:380px;overflow-y:auto;background:#0a1420;border:1px solid rgba(255,255,255,.1);padding:14px;display:flex;flex-direction:column;margin-top:14px}
.amsg{max-width:75%;padding:10px 14px;border-radius:10px;margin-bottom:8px;font-size:.9rem}
.amsg.them{background:#13263b;border:1px solid rgba(255,255,255,.12);align-self:flex-start}
.amsg.me{background:#c9a24b;color:#0d1b2a;align-self:flex-end}
.amsg small{display:block;opacity:.65;font-size:.68rem;margin-top:4px}
.aform{display:flex;gap:10px;margin-top:12px}
.aform input{flex:1}
.hint{color:#8b95a2;font-size:.8rem;margin:10px 0}
.pin{color:#e6cd8f;letter-spacing:.25em;font-weight:bold}
@media(max-width:900px){table{font-size:.72rem}th:nth-child(5),td:nth-child(5),th:nth-child(6),td:nth-child(6){display:none}}
</style>
</head>
<body>
<div class="box">
<?php if (!$loggedIn): ?>
  <h1>R Line Taxis — Office Login</h1>
  <form method="post" style="margin-top:24px">
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Log in</button>
  </form>
  <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>

<?php else: ?>
  <h1>Office Panel <a class="logout" href="?logout=1">Log out</a></h1>
  <div class="tabs">
    <a href="?tab=bookings" class="<?= $tab === 'bookings' ? 'on' : '' ?>">Bookings (<?= count($bookings) ?>)</a>
    <a href="?tab=messages" class="<?= $tab === 'messages' ? 'on' : '' ?>">Messages</a>
    <a href="?tab=drivers" class="<?= $tab === 'drivers' ? 'on' : '' ?>">Drivers (<?= count($drivers) ?>)</a>
    <a href="?tab=enquiries" class="<?= $tab === 'enquiries' ? 'on' : '' ?>">Enquiries (<?= count($enquiries) ?>)</a>
  </div>

<?php if ($tab === 'bookings'): ?>
  <?php if (!$bookings): ?><p class="hint">No bookings yet. Keep this tab open — you'll hear a beep when one arrives.</p>
  <?php else: ?>
  <table>
    <tr><th>Ref / Received</th><th>Passenger</th><th>Journey</th><th>When</th><th>Estimate</th><th>Status</th><th>Assign Driver</th><th></th></tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
      <td><b><?= h($b['ref']) ?></b><br><small><?= h($b['received']) ?></small><?= !empty($b['notes']) ? '<br><small>📝 ' . h($b['notes']) . '</small>' : '' ?></td>
      <td><?= h($b['name']) ?><br><a href="tel:<?= h($b['phone']) ?>"><?= h($b['phone']) ?></a><?= !empty($b['email']) ? '<br>' . h($b['email']) : '' ?></td>
      <td><?= h($b['pickup']) ?> → <?= h($b['dropoff']) ?></td>
      <td><?= h($b['date']) ?> <?= h($b['time']) ?><br><?= h((string)$b['passengers']) ?> pax · <?= h(VMAP[$b['vehicle']] ?? $b['vehicle']) ?></td>
      <td><?= h($b['estimate'] ?? '') ?></td>
      <td><select onchange="setStatus('<?= h($b['ref']) ?>', this)">
        <?php foreach (STATUSES as $s): ?>
        <option value="<?= $s ?>" <?= ($b['status'] ?? 'pending') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
      </select></td>
      <td><select onchange="assign('<?= h($b['ref']) ?>', this)">
        <option value="">— unassigned —</option>
        <?php foreach ($drivers as $d): ?>
        <option value="<?= h($d['id']) ?>" <?= ($b['driver_id'] ?? '') === ($d['id'] ?? '') ? 'selected' : '' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select></td>
      <td><a href="?tab=messages&ref=<?= h($b['ref']) ?>">Chat</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

<?php elseif ($tab === 'messages'): ?>
  <p class="hint">Pick a booking to read and reply. New messages appear every 5 seconds — keep this tab open during shifts.</p>
  <?php if (!$bookings): ?><p class="hint">No bookings yet.</p>
  <?php else: ?>
  <select id="mRef">
    <option value="">— Select a booking —</option>
    <?php foreach ($bookings as $b): ?>
    <option value="<?= h($b['ref']) ?>" <?= $selRef === ($b['ref'] ?? '') ? 'selected' : '' ?>>
      <?= h($b['ref']) ?> — <?= h($b['name']) ?> (<?= h($b['date']) ?>)
    </option>
    <?php endforeach; ?>
  </select>
  <div class="amsgs" id="aMsgs"></div>
  <form class="aform" id="aForm">
    <input id="aText" placeholder="Reply as driver / office…" maxlength="500">
    <button type="submit">Send</button>
  </form>
  <script>
  let aLast = 0, aRef = <?= json_encode($selRef) ?>;
  const aMsgs = document.getElementById('aMsgs');
  function aPoll(){
    if (!aRef) return;
    fetch('api/poll.php?ref=' + encodeURIComponent(aRef) + '&after=' + aLast)
      .then(r => r.json()).then(d => {
        if (!d.ok) return;
        d.messages.forEach(m => {
          aLast = Math.max(aLast, +m.id);
          const w = document.createElement('div');
          w.className = 'amsg ' + (m.side === 'driver' ? 'me' : 'them');
          const t = document.createElement('div'); t.textContent = m.text;
          const s = document.createElement('small');
          s.textContent = new Date(m.time).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})
            + ' · ' + (m.side === 'driver' ? 'You' : 'Passenger');
          w.append(t, s); aMsgs.append(w);
        });
        aMsgs.scrollTop = aMsgs.scrollHeight;
      }).catch(() => {});
  }
  document.getElementById('mRef').addEventListener('change', e => {
    aRef = e.target.value; aLast = 0; aMsgs.innerHTML = ''; aPoll();
  });
  document.getElementById('aForm').addEventListener('submit', async e => {
    e.preventDefault();
    const i = document.getElementById('aText');
    if (!i.value.trim() || !aRef) return;
    const fd = new FormData();
    fd.append('ref', aRef); fd.append('side', 'driver'); fd.append('text', i.value);
    await fetch('api/message.php', {method: 'POST', body: fd}).catch(() => {});
    i.value = ''; aPoll();
  });
  if (aRef) aPoll();
  setInterval(aPoll, 5000);
  </script>
  <?php endif; ?>

<?php elseif ($tab === 'drivers'): ?>
  <p class="hint">Add your drivers — each gets a PIN. They log in at <b style="color:#e6cd8f">/driver.php</b> on their phone (tell them to "Add to Home Screen" for an app icon).</p>
  <form onsubmit="return addDriver(this)" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <input id="newDriverName" placeholder="Driver name" required style="flex:1;min-width:200px">
    <button type="submit">Add driver</button>
  </form>
  <?php if (!$drivers): ?>
    <p class="hint">No drivers yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Name</th><th>PIN</th><th>Added</th><th></th></tr>
    <?php foreach ($drivers as $d): ?>
    <tr>
      <td><?= h($d['name']) ?></td>
      <td><span class="pin"><?= h($d['pin']) ?></span></td>
      <td><?= h($d['created'] ?? '') ?></td>
      <td><button class="danger" onclick="delDriver('<?= h($d['id']) ?>', '<?= h($d['name']) ?>')">Remove</button></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

<?php else: ?>
  <?php if (!$enquiries): ?><p class="hint">No enquiries yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Received</th><th>Name</th><th>Phone</th><th>Email</th><th>Pickup</th><th>Destination</th><th>Date / Time</th><th>Notes</th></tr>
    <?php foreach ($enquiries as $e): ?>
    <tr>
      <td><?= h($e['received'] ?? '') ?></td><td><?= h($e['name'] ?? '') ?></td>
      <td><?= h($e['phone'] ?? '') ?></td><td><?= h($e['email'] ?? '') ?></td>
      <td><?= h($e['pickup'] ?? '') ?></td><td><?= h($e['dropoff'] ?? '') ?></td>
      <td><?= h(($e['date'] ?? '') . ' ' . ($e['time'] ?? '')) ?></td>
      <td><?= h($e['notes'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</div>

<script>
async function post(body){
  const r = await fetch('admin.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body});
  return r.json();
}
function setStatus(ref, el){
  post('action=set_status&ref=' + encodeURIComponent(ref) + '&status=' + encodeURIComponent(el.value))
    .then(d => { if (!d.ok) alert(d.error || 'Failed'); });
}
function assign(ref, el){
  post('action=assign&ref=' + encodeURIComponent(ref) + '&driver_id=' + encodeURIComponent(el.value))
    .then(d => { if (!d.ok) alert(d.error || 'Failed'); });
}
async function addDriver(form){
  const name = document.getElementById('newDriverName').value.trim();
  if (!name) return false;
  const d = await post('action=add_driver&name=' + encodeURIComponent(name));
  if (d.ok){ alert('Driver added — PIN: ' + d.pin); location.reload(); }
  else alert(d.error || 'Failed');
  return false;
}
async function delDriver(id, name){
  if (!confirm('Remove ' + name + '?')) return;
  await post('action=del_driver&driver_id=' + encodeURIComponent(id));
  location.reload();
}
<?php if ($loggedIn && $tab === 'bookings' && $bookings): ?>
// New-booking beep
let lastCount = <?= count($bookings) ?>;
function beep(){
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator(), g = ctx.createGain();
    o.connect(g); g.connect(ctx.destination);
    o.type = 'sine'; o.frequency.value = 880;
    g.gain.setValueAtTime(0.25, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.7);
    o.start(); o.stop(ctx.currentTime + 0.7);
  } catch (e) {}
}
setInterval(async () => {
  try {
    const d = await (await fetch('admin.php?ajax=stats')).json();
    if (d.ok && d.bookings > lastCount){ beep(); setTimeout(() => location.reload(), 1500); }
  } catch (e) {}
}, 20000);
<?php endif; ?>
</script>
</body>
</html>