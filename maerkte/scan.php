<?php
// scan.php
declare(strict_types=1);

/**
 * Single-file scanner:
 * - Stores in gescannte-maerkte/<market>/<market>.json
 * - Stores images as gescannte-maerkte/<market>/<code>.webp
 */

if (php_sapi_name() !== 'cli' && (isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    header('Content-Type: application/json; charset=utf-8');

    $action = (string)($_REQUEST['action'] ?? '');

    // Sanitize market
    $marketRaw = (string)($_REQUEST['market'] ?? '');
    $market = preg_replace('/[^a-zA-Z0-9_\-]/', '', $marketRaw);
    if ($market === '') {
        echo json_encode(['ok' => false, 'error' => 'invalid_market']);
        exit;
    }

    $baseDir = __DIR__ . '/gescannte-maerkte';
    $marketDir = $baseDir . '/' . $market;
    if (!is_dir($marketDir)) {
        @mkdir($marketDir, 0775, true);
    }

    $jsonFile = $marketDir . '/' . $market . '.json';

    $load_list = function () use ($jsonFile): array {
        if (!is_file($jsonFile)) return [];
        $c = @file_get_contents($jsonFile);
        $d = json_decode($c ?: '[]', true);
        return is_array($d) ? $d : [];
    };

    $atomic_write = function (string $path, array $data): bool {
        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) return false;
        return rename($tmp, $path);
    };

    if ($action === 'update_list') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data) || !isset($data['list']) || !is_array($data['list'])) {
            echo json_encode(['ok' => false, 'error' => 'invalid_payload']); exit;
        }
        $out = [];
        foreach ($data['list'] as $item) {
            if (!is_array($item)) continue;
            $code = preg_replace('/\D+/', '', (string)($item['code'] ?? ''));
            if ($code === '') continue;
            $ts = (string)($item['ts'] ?? gmdate('c'));
            $out[] = ['code' => $code, 'ts' => $ts];
        }
        if (!$atomic_write($jsonFile, $out)) {
            echo json_encode(['ok' => false, 'error' => 'write_failed']); exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'get_list') {
        echo json_encode(['ok' => true, 'list' => $load_list()]);
        exit;
    }

    if ($action === 'save_scan') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) { echo json_encode(['ok'=>false,'error'=>'invalid_payload']); exit; }

        $code = preg_replace('/\D+/', '', (string)($data['code'] ?? ''));
        $ts = (string)($data['ts'] ?? gmdate('c'));
        if ($code === '') { echo json_encode(['ok'=>false,'error'=>'invalid_code']); exit; }

        $list = $load_list();

        // Server-side noise filter:
        // If the same code was stored in the last 10 seconds, ignore.
        $now = time();
        for ($i = count($list)-1; $i >= 0; $i--) {
            $it = $list[$i];
            if (!is_array($it)) continue;
            if (($it['code'] ?? '') !== $code) continue;
            $t = strtotime((string)($it['ts'] ?? ''));
            if ($t !== false && ($now - $t) < 10) {
                echo json_encode(['ok'=>true,'ignored'=>true]);
                exit;
            }
            break;
        }

        // Also skip if last entry is same
        $last = end($list);
        if (!is_array($last) || (($last['code'] ?? '') !== $code)) {
            $list[] = ['code' => $code, 'ts' => $ts];
            if (!$atomic_write($jsonFile, $list)) {
                echo json_encode(['ok' => false, 'error' => 'write_failed']); exit;
            }
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'save_image') {
        $code = preg_replace('/\D+/', '', (string)($_POST['code'] ?? ''));
        if ($code === '') { echo json_encode(['ok'=>false,'error'=>'invalid_code']); exit; }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok'=>false,'error'=>'no_image']); exit;
        }

        $tmp = $_FILES['image']['tmp_name'];
        $target = $marketDir . '/' . $code . '.webp';

        if (!move_uploaded_file($tmp, $target)) {
            echo json_encode(['ok'=>false,'error'=>'move_failed']); exit;
        }
        @chmod($target, 0644);
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'unknown_action']);
    exit;
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Markt-Scanner</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background:#f5f7fb; font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; }
    .container { max-width:720px; margin:18px auto; }
    .step { margin-bottom:18px; }
    .video-wrap { background:#000; border-radius:12px; min-height:240px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    video#video { width:100%; height:auto; display:block; }
    .badge-market { font-size:1.05rem; padding:.6rem .8rem; }
    .list-item { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border-radius:12px; border:1px solid rgba(0,0,0,0.07); background:#fff; margin-bottom:10px; }
    .drag-handle { cursor:grab; font-size:20px; user-select:none; }
    .code-text { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; font-weight:800; font-size:1.15rem; }
    .ts-text { font-size:.8rem; color:#6b7280; }
    .sticky-top-custom { position:sticky; top:0; z-index:1030; background:#fff; border-bottom:1px solid rgba(0,0,0,0.06); padding:12px 0; }
  </style>
</head>
<body>
<header class="sticky-top-custom">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="/" class="btn btn-outline-primary btn-sm">Home</a>
    <div class="fw-bold">Markt-Scanner</div>
    <div style="width:48px"></div>
  </div>
</header>

<main class="container mt-3">

  <!-- STEP A -->
  <section id="stepA" class="step">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-3">Schritt A</h4>
        <input id="marketInput" class="form-control form-control-lg mb-3" placeholder="Markt-Name" />
        <button id="createMarketBtn" class="btn btn-primary btn-lg w-100">Markt erstellen</button>
      </div>
    </div>
  </section>

  <!-- STEP B -->
  <section id="stepB" class="step" style="display:none">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-3">Schritt B</h4>
        <div class="mb-3">
          <span id="marketBadge" class="badge bg-secondary badge-market">—</span>
        </div>

        <button id="scanToggleBtn" class="btn btn-success btn-lg w-100 mb-3">Start scannen</button>
        <button id="proceedToCBtn" class="btn btn-outline-primary btn-lg w-100" disabled>Weiter</button>

        <div class="video-wrap mt-3">
          <video id="video" autoplay playsinline muted></video>
        </div>
      </div>
    </div>
  </section>

  <!-- STEP C -->
  <section id="stepC" class="step" style="display:none">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-3">Schritt C</h4>
        <div id="list" class="mb-3" style="min-height:120px"></div>
        <button id="saveBtn" class="btn btn-primary btn-lg w-100">Speichern</button>
      </div>
    </div>
  </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
'use strict';

let market = null;
let detector = null;
let stream = null;
let scanning = false;

// Stabilitätsfilter
let candidateCode = '';
let candidateCount = 0;
let lastCommittedCode = '';
let lastCommitAt = 0;
const COMMIT_LOCK_MS = 1200;      // nach erfolgreichem Commit kurz "ruhig"
const STABLE_HITS = 3;            // wie oft gleich hintereinander
const VALID_LENS = new Set([8,12,13,14]);

const video = document.getElementById('video');

const stepA = document.getElementById('stepA');
const stepB = document.getElementById('stepB');
const stepC = document.getElementById('stepC');

const marketInput = document.getElementById('marketInput');
const createMarketBtn = document.getElementById('createMarketBtn');
const marketBadge = document.getElementById('marketBadge');

const scanToggleBtn = document.getElementById('scanToggleBtn');
const proceedToCBtn = document.getElementById('proceedToCBtn');
const listEl = document.getElementById('list');
const saveBtn = document.getElementById('saveBtn');

let sortable = new Sortable(listEl, { handle: '.drag-handle', animation: 150 });

createMarketBtn.addEventListener('click', async () => {
  const val = (marketInput.value || '').trim();
  if (!val) return;

  market = val.replace(/[^a-zA-Z0-9_\-]/g, '').toLowerCase();
  if (!market) return;

  marketBadge.textContent = market;

  // Create folder / empty json
  await updateListOnServer([]);

  stepA.style.display = 'none';
  stepB.style.display = '';
  stepC.style.display = 'none';
  proceedToCBtn.disabled = false;
});

scanToggleBtn.addEventListener('click', async () => {
  if (!market) return;

  if (!scanning) {
    // START
    try {
      if ('BarcodeDetector' in window) {
        detector = new BarcodeDetector({formats: ['ean_13','ean_8','upc_a','upc_e','code_128','qr_code']});
      } else {
        detector = null;
      }

      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false
      });

      video.srcObject = stream;
      await video.play();

      scanning = true;
      scanToggleBtn.textContent = 'Stop scannen';
      scanToggleBtn.classList.remove('btn-success');
      scanToggleBtn.classList.add('btn-danger');

      // Reset filters
      candidateCode = '';
      candidateCount = 0;

      tick();
    } catch (e) {
      // still silent in UI: no system outputs
      scanning = false;
    }
  } else {
    // STOP: capture BEFORE stopping stream
    scanning = false;

    // capture photo for the last committed (stable) code
    const codeForPhoto = lastCommittedCode || candidateCode;
    try { await captureAndUpload(codeForPhoto); } catch(e) {}

    // now stop the stream
    if (stream) {
      try { stream.getTracks().forEach(t => t.stop()); } catch(e) {}
      stream = null;
      video.srcObject = null;
    }

    scanToggleBtn.textContent = 'Start scannen';
    scanToggleBtn.classList.remove('btn-danger');
    scanToggleBtn.classList.add('btn-success');
  }
});

proceedToCBtn.addEventListener('click', async () => {
  stepA.style.display = 'none';
  stepB.style.display = 'none';
  stepC.style.display = '';
  await loadListAndRender();
});

saveBtn.addEventListener('click', async () => {
  const items = Array.from(listEl.querySelectorAll('[data-code]')).map(el => ({
    code: el.getAttribute('data-code') || '',
    ts: el.getAttribute('data-ts') || new Date().toISOString()
  })).filter(x => x.code);

  await updateListOnServer(items);

  // Speichern beendet das Scannen
  scanning = false;
  scanToggleBtn.disabled = true;
  proceedToCBtn.disabled = true;
  saveBtn.disabled = true;

  if (stream) {
    try { stream.getTracks().forEach(t => t.stop()); } catch(e) {}
    stream = null;
    video.srcObject = null;
  }
});

// --- Detection loop with stability + checksum + lockout ---
async function tick() {
  if (!scanning) return;

  // If detector not available, keep loop alive (no saving)
  if (!detector) {
    requestAnimationFrame(tick);
    return;
  }

  try {
    const now = Date.now();
    if (now - lastCommitAt < COMMIT_LOCK_MS) {
      requestAnimationFrame(tick);
      return;
    }

    const barcodes = await detector.detect(video);
    if (barcodes && barcodes.length) {
      const raw = String(barcodes[0].rawValue || '');
      const code = raw.replace(/\D/g, '');

      if (isPlausibleAndValid(code)) {
        if (code === candidateCode) candidateCount++;
        else { candidateCode = code; candidateCount = 1; }

        if (candidateCount >= STABLE_HITS && code !== lastCommittedCode) {
          // Commit
          await saveScanToServer(code);
          lastCommittedCode = code;
          lastCommitAt = Date.now();

          // reset candidate so it needs to be "seen again"
          candidateCode = '';
          candidateCount = 0;

          await loadListAndRender();
        }
      } else {
        // reset if nonsense
        candidateCode = '';
        candidateCount = 0;
      }
    }
  } catch (e) {
    // ignore
  }

  if (scanning) requestAnimationFrame(tick);
}

// --- Validation helpers ---
function isPlausibleAndValid(code) {
  if (!code) return false;
  if (!VALID_LENS.has(code.length)) return false;

  // For EAN-13 and EAN-8: validate checksum (this kills most noise)
  if (code.length === 13) return isValidEan13(code);
  if (code.length === 8) return isValidEan8(code);

  // UPC-A 12 / EAN-14: accept as plausible (could also validate UPC-A checksum, optional)
  // To keep it simple, we accept 12/14 without checksum, but stability+lock already reduces noise strongly.
  return true;
}

function isValidEan13(ean) {
  if (!/^\d{13}$/.test(ean)) return false;
  let sum = 0;
  for (let i = 0; i < 12; i++) {
    const n = ean.charCodeAt(i) - 48;
    sum += (i % 2 === 0) ? n : (n * 3);
  }
  const check = (10 - (sum % 10)) % 10;
  return check === (ean.charCodeAt(12) - 48);
}

function isValidEan8(ean) {
  if (!/^\d{8}$/.test(ean)) return false;
  let sum = 0;
  for (let i = 0; i < 7; i++) {
    const n = ean.charCodeAt(i) - 48;
    sum += (i % 2 === 0) ? (n * 3) : n;
  }
  const check = (10 - (sum % 10)) % 10;
  return check === (ean.charCodeAt(7) - 48);
}

// --- Server calls ---
async function saveScanToServer(code) {
  if (!market) return;
  await fetch(location.pathname + '?action=save_scan&market=' + encodeURIComponent(market), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ market, code, ts: new Date().toISOString() })
  });
}

async function loadListAndRender() {
  if (!market) return;
  const res = await fetch(location.pathname + '?action=get_list&market=' + encodeURIComponent(market));
  const j = await res.json();
  const arr = (j && Array.isArray(j.list)) ? j.list : [];
  renderList(arr);
}

async function updateListOnServer(list) {
  if (!market) return;
  await fetch(location.pathname + '?action=update_list&market=' + encodeURIComponent(market), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ market, list })
  });
}

// --- Capture & upload WEBP while stream is still alive ---
async function captureAndUpload(code) {
  if (!market || !code) return;

  // Ensure we have a live frame
  await ensureVideoReady();

  const canvas = document.createElement('canvas');

  // Use actual video dimensions (fallback if missing)
  const vw = video.videoWidth || 1280;
  const vh = video.videoHeight || 720;

  canvas.width = vw;
  canvas.height = vh;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.7));
  if (!blob) return;

  const fd = new FormData();
  fd.append('market', market);
  fd.append('code', code);
  fd.append('image', blob, code + '.webp');

  await fetch(location.pathname + '?action=save_image&market=' + encodeURIComponent(market), {
    method: 'POST',
    body: fd
  });
}

function ensureVideoReady() {
  // Wait until metadata/dimensions exist
  return new Promise((resolve) => {
    if (video.readyState >= 2 && video.videoWidth > 0) return resolve(true);
    const onLoaded = () => {
      video.removeEventListener('loadedmetadata', onLoaded);
      resolve(true);
    };
    video.addEventListener('loadedmetadata', onLoaded, { once: true });
    // also resolve after short timeout as fallback
    setTimeout(() => resolve(true), 250);
  });
}

function renderList(arr) {
  listEl.innerHTML = '';
  if (!arr.length) {
    listEl.innerHTML = '<div class="text-muted">—</div>';
    return;
  }

  for (const item of arr) {
    const code = item.code || '';
    const ts = item.ts || '';
    const row = document.createElement('div');
    row.className = 'list-item';
    row.setAttribute('data-code', code);
    row.setAttribute('data-ts', ts);

    row.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <div class="drag-handle">≡</div>
        <div>
          <div class="code-text">${escapeHtml(code)}</div>
          <div class="ts-text">${escapeHtml(ts)}</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" data-action="up">▲</button>
        <button class="btn btn-sm btn-outline-secondary" data-action="down">▼</button>
        <button class="btn btn-sm btn-outline-danger" data-action="del">Löschen</button>
      </div>
    `;

    listEl.appendChild(row);
  }

  listEl.querySelectorAll('button[data-action]').forEach(btn => {
    btn.onclick = () => {
      const action = btn.getAttribute('data-action');
      const el = btn.closest('[data-code]');
      if (!el) return;

      if (action === 'del') {
        el.remove();
      } else if (action === 'up') {
        const prev = el.previousElementSibling;
        if (prev) el.parentNode.insertBefore(el, prev);
      } else if (action === 'down') {
        const next = el.nextElementSibling;
        if (next) el.parentNode.insertBefore(next, el);
      }
    };
  });
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

window.addEventListener('beforeunload', () => {
  if (stream) {
    try { stream.getTracks().forEach(t => t.stop()); } catch(e) {}
  }
});
</script>
</body>
</html>
