<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Driver Portal · R Line Taxis</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%230d1b2a'/%3E%3Ctext x='16' y='23' font-family='Georgia' font-size='20' fill='%23c9a24b' text-anchor='middle'%3ER%3C/text%3E%3C/svg%3E">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--navy:#0d1b2a;--navy2:#13263b;--gold:#c9a24b;--gold2:#e6cd8f;--paper:#faf7f2;--ink:#1d2530;--muted:#5c6674;--line:#e8e2d6;--serif:'Playfair Display',serif;--sans:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--sans);background:var(--paper);color:var(--ink);line-height:1.6;min-height:100vh}
h1,h2,h3{font-family:var(--serif)}
.btn{display:inline-block;padding:11px 22px;border-radius:2px;text-decoration:none;font-weight:600;letter-spacing:.06em;font-size:.85rem;text-transform:uppercase;transition:.25s;cursor:pointer;border:none;font-family:var(--sans)}
.btn-gold{background:var(--gold);color:var(--navy)}
.btn-ghost{border:1px solid var(--gold);color:var(--gold);background:transparent}
.btn-ghost.on{background:var(--gold);color:var(--navy)}
header{background:var(--navy);padding:14px 18px;position:sticky;top:0;z-index:50}
.hbar{max-width:640px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
.logo{font-family:var(--serif);font-size:1.1rem;color:#fff;text-decoration:none}
.logo em{color:var(--gold);font-style:normal}
main{max-width:640px;margin:0 auto;padding:20px 16px 60px}
.screen{display:none}
.screen.on{display:block}
.gate{text-align:center;padding:40px 0}
.gate h1{font-size:1.6rem;margin-bottom:6px}
.gate p{color:var(--muted);font-size:.9rem;margin-bottom:24px}
.pinbox{width:230px;padding:14px;font-size:1.6rem;text-align:center;letter-spacing:.5em;border:1px solid var(--line);border-radius:2px;font-family:var(--sans)}
.pinbox:focus{outline:none;border-color:var(--gold)}
.err{color:#b23b3b;font-size:.85rem;margin-top:12px;min-height:1.2em}
.hello{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.hello h2{font-size:1.3rem}
.hello small{color:var(--muted)}
.job{background:#fff;border:1px solid var(--line);border-left:3px solid var(--gold);padding:16px 18px;margin-bottom:14px}
.job-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.job-top b{font-family:var(--serif)}
.badge{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;padding:4px 10px;border-radius:2px;background:#eee;color:var(--muted)}
.badge.pending{background:#f4ecd8;color:#8a6d1f}
.badge.confirmed{background:#dce8f4;color:#2c5d8f}
.badge.on_the_way{background:#d8f0dc;color:#2f7a3c}
.badge.completed{background:#e4e4e4;color:#666}
.badge.cancelled{background:#f4dcdc;color:#8f2f2f}
.route{font-size:.95rem;margin-bottom:4px}
.route .arrow{color:var(--gold);margin:0 6px}
.job-meta{color:var(--muted);font-size:.82rem;margin-bottom:12px}
.job-actions{display:flex;gap:10px}
.job-actions .btn{flex:1;text-align:center;display:flex;align-items:center;justify-content:center}
.detail-card{background:#fff;border:1px solid var(--line);border-top:3px solid var(--gold);padding:22px;margin-bottom:16px}
.kv{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem}
.kv span:first-child{color:var(--muted)}
.status-row{display:flex;gap:8px;margin-top:16px}
.status-row .btn{flex:1;text-align:center;padding:10px 6px;font-size:.72rem}
.chat{background:#fff;border:1px solid var(--line)}
.chat-head{padding:14px 18px;border-bottom:1px solid var(--line)}
.chat-head b{font-family:var(--serif)}
.chat-head span{display:block;color:var(--muted);font-size:.75rem}
.msgs{display:flex;flex-direction:column;height:300px;overflow-y:auto;padding:14px;background:var(--paper)}
.msg{max-width:80%;padding:11px 15px;border-radius:12px;margin-bottom:8px;font-size:.92rem}
.msg.them{background:#fff;border:1px solid var(--line);border-bottom-left-radius:2px;align-self:flex-start}
.msg.me{background:var(--gold);color:var(--navy);border-bottom-right-radius:2px;align-self:flex-end}
.msg small{display:block;opacity:.6;font-size:.66rem;margin-top:4px}
.chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--line)}
.chat-form input{flex:1;padding:11px 13px;border:1px solid var(--line);border-radius:2px;font-family:var(--sans);background:#fff;color:var(--ink)}
.chat-form input:focus{outline:none;border-color:var(--gold)}
.back{background:none;border:none;color:var(--muted);font-family:var(--sans);cursor:pointer;font-size:.85rem;margin-bottom:12px}
.empty{text-align:center;color:var(--muted);padding:50px 0}
</style>
</head>
<body>
<header><div class="hbar">
  <a class="logo" href="index.html">R <em>LINE</em> · Driver</a>
  <button class="btn btn-ghost" id="logoutBtn" style="display:none">Log out</button>
</div></header>
<main>

  <div class="screen on" id="scrLogin">
    <div class="gate">
      <h1>Driver Login</h1>
      <p>Enter your 4-digit PIN</p>
      <form id="loginForm">
        <input class="pinbox" id="pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" required>
        <br><br>
        <button class="btn btn-gold" type="submit">Sign In</button>
      </form>
      <p class="err" id="loginErr"></p>
    </div>
  </div>

  <div class="screen" id="scrList">
    <div class="hello"><div><h2 id="helloName">Driver</h2><small>Your assigned jobs — refreshes automatically</small></div></div>
    <div id="jobList"></div>
  </div>

  <div class="screen" id="scrDetail">
    <button class="back" id="backBtn">← Back to jobs</button>
    <div class="detail-card">
      <div class="job-top"><b id="dRef"></b><span class="badge" id="dBadge"></span></div>
      <div class="route" id="dRoute"></div>
      <div class="kv"><span>Date & time</span><span id="dWhen"></span></div>
      <div class="kv"><span>Passenger</span><span id="dPax"></span></div>
      <div class="kv"><span>Vehicle</span><span id="dVeh"></span></div>
      <div class="kv"><span>Estimate</span><span id="dEst"></span></div>
      <div class="kv"><span>Notes</span><span id="dNotes"></span></div>
      <div class="status-row">
        <button class="btn btn-ghost" data-status="confirmed">Confirm</button>
        <button class="btn btn-ghost" data-status="on_the_way">On the way</button>
        <button class="btn btn-ghost" data-status="completed">Completed</button>
      </div>
      <div style="margin-top:14px"><a class="btn btn-gold" id="dCall" href="#">📞 Call passenger</a></div>
    </div>
    <div class="chat">
      <div class="chat-head"><b>Chat with passenger</b><span>Messages appear live</span></div>
      <div class="msgs" id="dMsgs"></div>
      <form class="chat-form" id="dForm">
        <input id="dText" placeholder="Type a message…" maxlength="500" autocomplete="off">
        <button class="btn btn-gold" type="submit">Send</button>
      </form>
    </div>
  </div>

</main>
<script>
const q = id => document.getElementById(id);
const VLBL = {saloon:'Saloon', estate:'Estate', mpv6:'MPV 6', mpv8:'MPV 8'};
let me = null, jobs = [], cur = null, lastId = 0, jobsTimer = null, chatTimer = null;

function show(scr){ document.querySelectorAll('.screen').forEach(s => s.classList.remove('on')); q(scr).classList.add('on'); }
function esc(s){ const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

q('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('action', 'login'); fd.append('pin', q('pin').value);
  try {
    const d = await (await fetch('api/driver.php', {method:'POST', body:fd})).json();
    if (d.ok){ q('loginErr').textContent = ''; q('pin').value = ''; start(); }
    else q('loginErr').textContent = d.error || 'Wrong PIN.';
  } catch { q('loginErr').textContent = 'Connection error.'; }
});

q('logoutBtn').addEventListener('click', async () => {
  await fetch('api/driver.php?action=logout').catch(()=>{});
  me = null; clearInterval(jobsTimer); clearInterval(chatTimer);
  q('logoutBtn').style.display = 'none'; show('scrLogin');
});

async function start(){
  try {
    const d = await (await fetch('api/driver.php')).json();
    if (!d.ok){ show('scrLogin'); return; }
    me = d.name;
    q('helloName').textContent = me;
    q('logoutBtn').style.display = 'inline-block';
    show('scrList');
    await loadJobs();
    clearInterval(jobsTimer);
    jobsTimer = setInterval(loadJobs, 15000);
  } catch { show('scrLogin'); }
}

async function loadJobs(){
  try {
    const d = await (await fetch('api/driver.php')).json();
    if (!d.ok){ if (d.error === 'Not logged in') show('scrLogin'); return; }
    jobs = d.jobs;
    const w = q('jobList'); w.innerHTML = '';
    if (!jobs.length){
      w.innerHTML = '<div class="empty">No jobs assigned yet.<br>The office will assign bookings to you.</div>';
      return;
    }
    jobs.forEach(j => {
      const el = document.createElement('div');
      el.className = 'job';
      el.innerHTML =
        '<div class="job-top"><b>' + esc(j.ref) + '</b><span class="badge ' + esc(j.status) + '">' +
          esc(j.status.replace(/_/g,' ')) + '</span></div>' +
        '<div class="route">' + esc(j.pickup) + '<span class="arrow">→</span>' + esc(j.dropoff) + '</div>' +
        '<div class="job-meta">' + esc(j.date) + ' · ' + esc(j.time) + ' · ' + esc(j.name) + ' · ' +
          esc(String(j.passengers)) + ' pax · ' + esc(VLBL[j.vehicle] || j.vehicle) + '</div>' +
        '<div class="job-actions">' +
          '<a class="btn btn-ghost" href="tel:' + esc(j.phone) + '">Call</a>' +
          '<button class="btn btn-gold">Open</button>' +
        '</div>';
      el.querySelector('button').addEventListener('click', () => openJob(j.ref));
      w.appendChild(el);
    });
  } catch {}
}

function openJob(ref){
  const j = jobs.find(x => x.ref === ref); if (!j) return;
  cur = j; lastId = 0; q('dMsgs').innerHTML = '';
  q('dRef').textContent = j.ref;
  q('dBadge').textContent = j.status.replace(/_/g,' ');
  q('dBadge').className = 'badge ' + j.status;
  q('dRoute').innerHTML = esc(j.pickup) + '<span class="arrow">→</span>' + esc(j.dropoff);
  q('dWhen').textContent = j.date + ' · ' + j.time;
  q('dPax').textContent = j.name + ' (' + j.passengers + ')';
  q('dVeh').textContent = VLBL[j.vehicle] || j.vehicle;
  q('dEst').textContent = j.estimate || '—';
  q('dNotes').textContent = j.notes || '—';
  q('dCall').href = 'tel:' + j.phone;
  syncStatusBtns(j.status);
  show('scrDetail');
  clearInterval(chatTimer);
  chatTimer = setInterval(pollChat, 4000);
  pollChat();
}
q('backBtn').addEventListener('click', () => { clearInterval(chatTimer); show('scrList'); loadJobs(); });

function syncStatusBtns(s){
  document.querySelectorAll('.status-row .btn').forEach(b => b.classList.toggle('on', b.dataset.status === s));
}
document.querySelectorAll('.status-row .btn').forEach(b => b.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('action','set_status'); fd.append('ref', cur.ref); fd.append('status', b.dataset.status);
  try {
    const d = await (await fetch('api/driver.php', {method:'POST', body:fd})).json();
    if (d.ok){
      cur.status = b.dataset.status;
      q('dBadge').textContent = cur.status.replace(/_/g,' ');
      q('dBadge').className = 'badge ' + cur.status;
      syncStatusBtns(cur.status);
    } else alert(d.error || 'Failed');
  } catch { alert('Connection error'); }
}));

async function pollChat(){
  if (!cur) return;
  try {
    const d = await (await fetch('api/poll.php?ref=' + encodeURIComponent(cur.ref) + '&after=' + lastId)).json();
    if (!d.ok) return;
    d.messages.forEach(m => {
      lastId = Math.max(lastId, +m.id);
      const w = document.createElement('div');
      w.className = 'msg ' + (m.side === 'driver' ? 'me' : 'them');
      const t = document.createElement('div'); t.textContent = m.text;
      const s = document.createElement('small');
      s.textContent = new Date(m.time).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})
        + ' · ' + (m.side === 'driver' ? 'You' : 'Passenger');
      w.append(t, s); q('dMsgs').appendChild(w);
    });
    q('dMsgs').scrollTop = q('dMsgs').scrollHeight;
  } catch {}
}
q('dForm').addEventListener('submit', async e => {
  e.preventDefault();
  const i = q('dText'); if (!i.value.trim() || !cur) return;
  const fd = new FormData();
  fd.append('ref', cur.ref); fd.append('side', 'driver'); fd.append('text', i.value);
  await fetch('api/message.php', {method:'POST', body:fd}).catch(()=>{});
  i.value = ''; pollChat();
});

start();
</script>
</body>
</html>