<?php
declare(strict_types=1);

/**
 * markt_erstellen.php
 *
 * Änderungen nach deinen Vorgaben:
 * 1) Jede angeklickte Abteilung verschwindet oben (nur einmal wählbar).
 * 2) Beim Speichern wird die Liste automatisch vervollständigt:
 *    Alle nicht angeklickten Abteilungen werden ans Ende angehängt (vollständige JSON).
 * 3) Kein Zurücksetzen-Button.
 * 4) Keine Suche.
 * 5) Marktname wird erst beim Speichern per Dialog abgefragt (Modal).
 * 6) Komplettcode (UI + API).
 *
 * Dateien:
 * - abteilungen.json liegt im selben Verzeichnis wie diese Datei
 * - Ausgabe: lokationen/<slug>.json (wird angelegt)
 */

const ABTEILUNGEN_FILE = __DIR__ . '/abteilungen.json';
const LOKATIONEN_DIR   = __DIR__ . '/lokationen';

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function err(string $msg, int $code = 400): void {
    json_response(['ok' => false, 'error' => $msg], $code);
}
function read_json(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function write_json_atomic(string $file, array $data): void {
    $tmp = $file . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new RuntimeException('JSON encode failed');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Write temp failed');
    if (!rename($tmp, $file)) { @unlink($tmp); throw new RuntimeException('Atomic rename failed'); }
}
function slugify(string $text): string {
    $text = trim($text);
    $text = strtr($text, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ß'=>'ss']);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'markt';
}

$action = $_GET['action'] ?? null;

if ($action === 'meta') {
    $raw = read_json(ABTEILUNGEN_FILE);
    $names = [];
    foreach ($raw as $row) {
        if (!is_array($row)) continue;
        $n = $row['abteilungsname'] ?? null;
        if (is_string($n) && trim($n) !== '') $names[] = trim($n);
    }
    $names = array_values(array_unique($names));
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    json_response(['ok' => true, 'abteilungen' => $names]);
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method not allowed', 405);

    $body = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($body)) err('Invalid JSON body');

    $marktname = $body['name'] ?? '';
    $selected  = $body['selected'] ?? null;

    if (!is_string($marktname) || trim($marktname) === '') err('Marktname fehlt.');
    if (!is_array($selected)) err('Selected list fehlt.');

    // Load full dept list from file to ensure completeness
    $raw = read_json(ABTEILUNGEN_FILE);
    $all = [];
    foreach ($raw as $row) {
        if (!is_array($row)) continue;
        $n = $row['abteilungsname'] ?? null;
        if (is_string($n) && trim($n) !== '') $all[] = trim($n);
    }
    $all = array_values(array_unique($all));
    sort($all, SORT_NATURAL | SORT_FLAG_CASE);

    // Normalize selected (keep order, unique, only those that exist in all)
    $allSet = array_fill_keys($all, true);
    $seen = [];
    $sel = [];
    foreach ($selected as $n) {
        if (!is_string($n)) continue;
        $n = trim($n);
        if ($n === '' || isset($seen[$n])) continue;
        if (!isset($allSet[$n])) continue;
        $seen[$n] = true;
        $sel[] = $n;
    }

    // Append missing departments at end
    foreach ($all as $n) {
        if (!isset($seen[$n])) $sel[] = $n;
    }

    if (count($sel) !== count($all)) {
        // should not happen, but safety
        err('Interner Fehler: Liste nicht vollständig.', 500);
    }

    // Ensure output directory
    if (!is_dir(LOKATIONEN_DIR)) {
        if (!mkdir(LOKATIONEN_DIR, 0775, true) && !is_dir(LOKATIONEN_DIR)) {
            err('Konnte lokationen/ nicht anlegen (Rechte?).', 500);
        }
    }

    // Choose file name (no overwrite; auto-suffix)
    $slug = slugify($marktname);
    $path = LOKATIONEN_DIR . '/' . $slug . '.json';
    $i = 2;
    while (file_exists($path)) {
        $path = LOKATIONEN_DIR . '/' . $slug . '-' . $i . '.json';
        $i++;
    }

    $out = [
        'name' => trim($marktname),
        'abteilungen' => [],
    ];
    $order = 1;
    foreach ($sel as $n) {
        $out['abteilungen'][] = ['name' => $n, 'order' => $order++];
    }

    try {
        write_json_atomic($path, $out);
    } catch (Throwable $e) {
        err('Speichern fehlgeschlagen: ' . $e->getMessage(), 500);
    }

    json_response(['ok' => true, 'file' => 'lokationen/' . basename($path)]);
}

?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Markt erstellen</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f6f7f9; }
  .sticky-topbar {
    position: sticky; top: 0; z-index: 20;
    background:#f6f7f9; border-bottom:1px solid rgba(0,0,0,.08);
  }
  .card-soft { border:1px solid rgba(0,0,0,.12); border-radius: 1rem; }
  .listbox { max-height: 70vh; overflow:auto; }
  .big-btn {
    font-size: 1.15rem;
    padding: 14px 14px;
    border-radius: 1rem;
  }
  .chip {
    display:flex; align-items:center; gap:.75rem;
    padding: 14px 16px;
    border-radius: 1rem;
    border: 1px solid rgba(0,0,0,.14);
    background:#fff;
    font-size: 1.15rem;
    user-select:none;
  }
  .chip:active { transform: scale(0.99); }
  .handle {
    width: 40px; height: 40px;
    border-radius: .9rem;
    background: rgba(0,0,0,.06);
    display:flex; align-items:center; justify-content:center;
    font-weight: 800;
    flex: 0 0 auto;
  }
  .muted { color: rgba(0,0,0,.6); }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
</style>
</head>
<body>

<div class="sticky-topbar">
  <div class="container py-3">
    <div class="d-flex flex-wrap align-items-end gap-2">
      <div class="me-auto">
        <h4 class="mb-0">Markt erstellen</h4>
        <div class="small muted" id="statusText">Lade Abteilungen…</div>
      </div>
      <button class="btn btn-primary btn-lg" id="saveBtn" type="button" disabled>Speichern</button>
    </div>
  </div>
</div>

<div class="container py-3">
  <div class="row g-3">

    <!-- Oben: verbleibende Abteilungen (klicken => wandert nach rechts und verschwindet hier) -->
    <div class="col-lg-6">
      <div class="card card-soft">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="fw-semibold">Abteilungen (noch offen)</div>
            <span class="ms-auto small muted"><span id="countOpen">0</span></span>
          </div>
          <div class="listbox" id="openList"></div>
        </div>
      </div>
    </div>

    <!-- Rechts: Reihenfolge + Drag&Drop -->
    <div class="col-lg-6">
      <div class="card card-soft">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="fw-semibold">Reihenfolge</div>
            <span class="ms-auto small muted"><span id="countSel">0</span></span>
          </div>

          <div class="listbox" id="selList"></div>
          <div class="small muted mt-2">Ziehen zum Sortieren · Tippen zum Entfernen</div>

          <div class="alert alert-danger d-none mt-3" id="errBox"></div>
          <div class="alert alert-success d-none mt-3" id="okBox"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Name Dialog beim Speichern -->
<div class="modal fade" id="nameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Marktname</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body">
        <input class="form-control form-control-lg" id="marktNameInput" placeholder="z. B. Aldi Griesheim">
        <div class="small muted mt-2" id="nameHint"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
        <button type="button" class="btn btn-primary" id="confirmSaveBtn">Speichern</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
'use strict';

const el = {
  statusText: document.getElementById('statusText'),
  saveBtn: document.getElementById('saveBtn'),

  openList: document.getElementById('openList'),
  selList: document.getElementById('selList'),

  countOpen: document.getElementById('countOpen'),
  countSel: document.getElementById('countSel'),

  errBox: document.getElementById('errBox'),
  okBox: document.getElementById('okBox'),

  nameModal: document.getElementById('nameModal'),
  marktNameInput: document.getElementById('marktNameInput'),
  confirmSaveBtn: document.getElementById('confirmSaveBtn'),
  nameHint: document.getElementById('nameHint'),
};

const bsNameModal = new bootstrap.Modal(el.nameModal);

const state = {
  all: [],
  selected: [],  // ordered list (user)
  open: [],      // remaining departments (all - selected)
};

function showErr(msg){
  el.okBox.classList.add('d-none');
  el.errBox.textContent = msg;
  el.errBox.classList.remove('d-none');
}
function showOk(msg){
  el.errBox.classList.add('d-none');
  el.okBox.textContent = msg;
  el.okBox.classList.remove('d-none');
}
function clearMsgs(){
  el.errBox.classList.add('d-none');
  el.okBox.classList.add('d-none');
}
function escapeHtml(s){
  return String(s)
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
}

function recomputeOpen(){
  const selSet = new Set(state.selected);
  state.open = state.all.filter(n => !selSet.has(n));
}

function renderOpen(){
  el.openList.innerHTML = '';
  el.countOpen.textContent = String(state.open.length);

  for (const name of state.open) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-dark w-100 text-start big-btn mb-2';
    btn.textContent = name;
    btn.addEventListener('click', () => {
      clearMsgs();
      // add once
      if (!state.selected.includes(name)) state.selected.push(name);
      recomputeOpen();
      renderSelected();
      renderOpen();
      updateSaveState();
    });
    el.openList.appendChild(btn);
  }
}

function renderSelected(){
  el.selList.innerHTML = '';
  el.countSel.textContent = String(state.selected.length);

  state.selected.forEach((name, idx) => {
    const row = document.createElement('div');
    row.className = 'chip mb-2';
    row.dataset.name = name;

    row.innerHTML = `
      <div class="handle">≡</div>
      <div style="flex:1 1 auto">
        <div class="fw-semibold">${escapeHtml(name)}</div>
        <div class="small muted">Order: ${idx+1}</div>
      </div>
    `;

    row.addEventListener('click', () => {
      clearMsgs();
      // remove from selected -> returns to open
      state.selected = state.selected.filter(n => n !== name);
      recomputeOpen();
      renderSelected();
      renderOpen();
      updateSaveState();
    });

    el.selList.appendChild(row);
  });
}

function updateSaveState(){
  // enabled sobald mindestens 1 Abteilung gewählt wurde (du kannst auch ohne wählen speichern, dann hängt er alles hinten dran;
  // aber UX: mind. 1 Klick ist sinnvoll, sonst ist Reihenfolge nur alphabetisch)
  el.saveBtn.disabled = state.selected.length === 0;
}

async function loadMeta(){
  el.statusText.textContent = 'Lade Abteilungen…';
  const res = await fetch('markt_erstellen.php?action=meta&ts=' + Date.now(), {cache:'no-store'});
  if (!res.ok) throw new Error('Meta laden fehlgeschlagen: ' + res.status);
  const data = await res.json();
  if (!data.ok || !Array.isArray(data.abteilungen)) throw new Error('Ungültige Meta-Daten');

  state.all = data.abteilungen;
  state.selected = [];
  recomputeOpen();

  renderSelected();
  renderOpen();
  updateSaveState();

  el.statusText.textContent = '';
}

function openNameDialog(){
  clearMsgs();
  // Hinweis: beim Speichern werden nicht gewählte Abteilungen automatisch ans Ende angehängt
  const missing = state.all.length - state.selected.length;
  el.nameHint.textContent = missing > 0
    ? `${missing} Abteilungen werden automatisch ans Ende angehängt.`
    : `Alle Abteilungen sind bereits in Reihenfolge.`;

  el.marktNameInput.value = '';
  bsNameModal.show();

  // Focus
  setTimeout(() => {
    el.marktNameInput.focus();
    el.marktNameInput.select();
  }, 150);
}

async function confirmSave(){
  clearMsgs();
  const name = el.marktNameInput.value.trim();
  if (!name) {
    showErr('Bitte Marktname eingeben.');
    return;
  }

  el.confirmSaveBtn.disabled = true;
  el.saveBtn.disabled = true;
  el.statusText.textContent = 'Speichere…';

  const payload = {
    name,
    selected: state.selected
  };

  try {
    const res = await fetch('markt_erstellen.php?action=save', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });

    const out = await res.json().catch(() => null);

    if (!res.ok || !out || !out.ok) {
      const msg = out && out.error ? out.error : ('Speichern fehlgeschlagen (HTTP ' + res.status + ')');
      showErr(msg);
      el.statusText.textContent = '';
      el.confirmSaveBtn.disabled = false;
      updateSaveState();
      return;
    }

    bsNameModal.hide();
    showOk('Gespeichert: ' + out.file);
    el.statusText.textContent = '';
    el.confirmSaveBtn.disabled = false;

    // Optional: nach erfolgreichem Save UI zurücksetzen
    // state.selected = [];
    // recomputeOpen();
    // renderSelected(); renderOpen(); updateSaveState();

  } catch (e) {
    showErr(e.message || String(e));
    el.statusText.textContent = '';
    el.confirmSaveBtn.disabled = false;
    updateSaveState();
  }
}

new Sortable(el.selList, {
  animation: 150,
  handle: '.handle',
  onSort: () => {
    // read order back from DOM
    const names = Array.from(el.selList.querySelectorAll('[data-name]')).map(x => x.dataset.name);
    state.selected = names;
    renderSelected(); // update order numbers
    // open list remains computed by set difference
    recomputeOpen();
    renderOpen();
  }
});

el.saveBtn.addEventListener('click', openNameDialog);
el.confirmSaveBtn.addEventListener('click', confirmSave);
el.marktNameInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') confirmSave();
});

loadMeta().catch(e => showErr(e.message || String(e)));
</script>

</body>
</html>
