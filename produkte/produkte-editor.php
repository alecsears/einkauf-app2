<?php
declare(strict_types=1);

/**
 * produkte-editor.php (Weg A, performant, JSON bleibt unverändert)
 *
 * Ordnerstruktur wie in deiner zuordnung_editor.php:
 * root/
 *  ├─ produktliste.json
 *  ├─ einheiten.json
 *  ├─ vorratsorte.json
 *  └─ produkte-editor.php
 *  ../maerkte/abteilungen.txt
 *
 * Endpoints:
 *  - GET  ?action=data   => komplette produktliste.json (Map-Format)
 *  - GET  ?action=meta   => Optionen (einheiten/vorratsorte/abteilungen etc.)
 *  - POST ?action=save   => Patch speichern (added/changed/deleted) mit ID-Validierung
 */

const DATA_FILE = __DIR__ . '/produktliste.json';

function read_json_file(string $path): array {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function write_json_file_atomic(string $path, array $data): void {
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) throw new RuntimeException('JSON encode failed');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Write temp file failed');
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Atomic rename failed'); }
}

function json_response($payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function err(string $msg, int $code = 400): void {
    json_response(['ok' => false, 'error' => $msg], $code);
}

$action = $_GET['action'] ?? null;

if ($action === 'data') {
    json_response(read_json_file(DATA_FILE));
}

if ($action === 'meta') {
    // einheiten.json: Produkteinheiten (code -> label)
    $einheitenFile = __DIR__ . '/einheiten.json';
    $einheiten = [];
    if (file_exists($einheitenFile)) {
        $ed = json_decode(file_get_contents($einheitenFile), true);
        if (is_array($ed) && isset($ed['Produkteinheiten']) && is_array($ed['Produkteinheiten'])) {
            foreach ($ed['Produkteinheiten'] as $code => $arr) {
                $einheiten[$code] = (is_array($arr) && isset($arr['label'])) ? (string)$arr['label'] : (string)$code;
            }
        }
    }

    // vorratsorte.json: Vorratsorte (key -> label)
    $vorratsorteFile = __DIR__ . '/vorratsorte.json';
    $vorratsorte = [];
    if (file_exists($vorratsorteFile)) {
        $vd = json_decode(file_get_contents($vorratsorteFile), true);
        if (is_array($vd) && isset($vd['Vorratsorte']) && is_array($vd['Vorratsorte'])) {
            foreach ($vd['Vorratsorte'] as $key => $meta) {
                $vorratsorte[$key] = (is_array($meta) && isset($meta['label'])) ? (string)$meta['label'] : (string)$key;
            }
        }
    }
    if (!$vorratsorte) $vorratsorte = ['Sonstiger Ort' => 'Sonstiger Ort'];

    uksort($vorratsorte, function($a, $b) {
        $aIs = (mb_strtolower($a, 'UTF-8') === mb_strtolower('Sonstiger Ort', 'UTF-8'));
        $bIs = (mb_strtolower($b, 'UTF-8') === mb_strtolower('Sonstiger Ort', 'UTF-8'));
        if ($aIs && !$bIs) return 1;
        if (!$aIs && $bIs) return -1;
        return strnatcasecmp($a, $b);
    });

    // abteilungen.txt
    $abteilungenFile = __DIR__ . '/../maerkte/abteilungen.txt';
    $abteilungen = [];
    if (file_exists($abteilungenFile)) {
        $abteilungen = file($abteilungenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        sort($abteilungen, SORT_NATURAL | SORT_FLAG_CASE);
    }

    json_response([
        'einheiten' => $einheiten,
        'vorratsorte' => $vorratsorte,
        'abteilungen' => $abteilungen,
        'produktarten' => ['Food','Non-Food'],
        'jaNein' => ['ja','nein'],
    ]);
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method not allowed', 405);

    $raw = file_get_contents('php://input');
    $body = json_decode($raw ?: '', true);
    if (!is_array($body)) err('Invalid JSON body');

    $added   = (isset($body['added'])   && is_array($body['added']))   ? $body['added']   : [];
    $changed = (isset($body['changed']) && is_array($body['changed'])) ? $body['changed'] : [];
    $deleted = (isset($body['deleted']) && is_array($body['deleted'])) ? $body['deleted'] : [];

    $db = read_json_file(DATA_FILE);

    // Build indexes of current DB
    $idToKey = [];      // string id => key
    $usedIds = [];      // string id => true
    foreach ($db as $k => $p) {
        if (is_array($p) && isset($p['id'])) {
            $sid = (string)$p['id'];
            $idToKey[$sid] = $k;
            $usedIds[$sid] = true;
        }
    }

    // Helper to validate product array minimal shape
    $requireIdName = function($prod): array {
        if (!is_array($prod)) err('Product must be object');
        if (!isset($prod['id'])) err('Product.id missing');
        $sid = (string)$prod['id'];
        if ($sid === '' || !preg_match('/^\d+$/', $sid)) err('Product.id must be integer');
        if (!isset($prod['name']) || !is_string($prod['name']) || trim($prod['name']) === '') err('Product.name missing/empty');
        return [$sid, trim((string)$prod['name'])];
    };

    // --- VALIDATE PATCH: IDs dürfen nicht überschrieben/dupliziert werden ---
    // 1) Added IDs must be new and must not collide with DB or within added
    $addedIds = [];
    foreach ($added as $a) {
        if (!is_array($a)) err('Invalid added entry');
        $key = $a['key'] ?? null;
        $prod = $a['product'] ?? null;
        if (!is_string($key) || trim($key) === '') err('Added.key missing/empty');
        [$sid, $pname] = $requireIdName($prod);

        if (trim($key) !== $pname) err('Added.key must match product.name');

        if (isset($usedIds[$sid])) err("ID-Kollision: id=$sid existiert bereits (added)");
        if (isset($addedIds[$sid])) err("ID-Kollision: id=$sid doppelt in added");
        $addedIds[$sid] = true;
    }

    // 2) Changed IDs must exist in DB and must not change identity
    // Also: newKey collision with other existing product (rename)
    foreach ($changed as $c) {
        if (!is_array($c)) err('Invalid changed entry');
        $id = $c['id'] ?? null;
        $oldKey = $c['oldKey'] ?? null;
        $newKey = $c['newKey'] ?? null;
        $prod = $c['product'] ?? null;

        if ($id === null) err('Changed.id missing');
        $sid = (string)$id;
        if ($sid === '' || !preg_match('/^\d+$/', $sid)) err('Changed.id must be integer');

        if (!isset($usedIds[$sid])) err("Changed refers to unknown id=$sid");

        if (!is_string($oldKey) || trim($oldKey)==='') err('Changed.oldKey missing');
        if (!is_string($newKey) || trim($newKey)==='') err('Changed.newKey missing');

        [$pid, $pname] = $requireIdName($prod);
        if ($pid !== $sid) err("ID darf nicht geändert werden: changed.id=$sid, product.id=$pid");
        if (trim($newKey) !== $pname) err('Changed.newKey must match product.name');

        // rename collision: newKey exists but belongs to different id
        $existingKeyForId = $idToKey[$sid] ?? null;
        if ($existingKeyForId === null) err("Internal: missing key for id=$sid");

        if (isset($db[$newKey]) && $newKey !== $existingKeyForId) {
            $other = $db[$newKey];
            $otherId = (is_array($other) && isset($other['id'])) ? (string)$other['id'] : '';
            if ($otherId !== $sid) err("Rename-Kollision: '$newKey' gehört bereits zu id=$otherId");
        }
    }

    // 3) Deleted: ok (id/key)
    // Apply DELETE first
    foreach ($deleted as $d) {
        if (!is_array($d)) continue;
        $id  = $d['id']  ?? null;
        $key = $d['key'] ?? null;

        if ($id !== null) {
            $sid = (string)$id;
            $k = $idToKey[$sid] ?? null;
            if ($k !== null && isset($db[$k])) {
                unset($db[$k]);
                unset($idToKey[$sid]);
                unset($usedIds[$sid]);
            }
        } elseif (is_string($key) && isset($db[$key])) {
            $pid = (is_array($db[$key]) && isset($db[$key]['id'])) ? (string)$db[$key]['id'] : null;
            unset($db[$key]);
            if ($pid !== null) { unset($idToKey[$pid]); unset($usedIds[$pid]); }
        }
    }

    // Apply CHANGED (including rename oldKey -> newKey)
    foreach ($changed as $c) {
        $sid = (string)$c['id'];
        $oldKey = trim((string)$c['oldKey']);
        $newKey = trim((string)$c['newKey']);
        $product = $c['product'];

        // Ensure product.name consistent
        $product['name'] = $newKey;

        $currentKey = $idToKey[$sid] ?? $oldKey;

        if ($currentKey !== $newKey && isset($db[$currentKey])) unset($db[$currentKey]);
        $db[$newKey] = $product;

        $idToKey[$sid] = $newKey;
        $usedIds[$sid] = true;
    }

    // Apply ADDED
    foreach ($added as $a) {
        $key = trim((string)$a['key']);
        $product = $a['product'];
        $sid = (string)$product['id'];

        $product['name'] = $key;

        // final safety: must still be free (could have been deleted+readded etc.)
        if (isset($usedIds[$sid])) err("ID-Kollision beim Schreiben: id=$sid");
        $db[$key] = $product;

        $idToKey[$sid] = $key;
        $usedIds[$sid] = true;
    }

    try {
        write_json_file_atomic(DATA_FILE, $db);
    } catch (Throwable $e) {
        err($e->getMessage(), 500);
    }

    json_response(['ok'=>true]);
}

?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Produkte Editor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --row-h: 58px; }

    body { background: #f6f7f9; }

    .sticky-topbar {
      position: sticky; top: 0; z-index: 20;
      background: #f6f7f9; border-bottom: 1px solid rgba(0,0,0,.08);
    }

    .list-viewport {
      height: calc(100vh - 210px);
      min-height: 360px;
      overflow: auto;
      position: relative;
      border: 1px solid rgba(0,0,0,.12);
      border-radius: 1rem;
      background: #fff;
    }

    .spacer { position: relative; width: 100%; }

    .row-item {
      position: absolute; left: 0; right: 0;
      height: var(--row-h);
      display: flex; align-items: center; gap: .75rem;
      padding: 0 .9rem;
      border-bottom: 1px solid rgba(0,0,0,.06);
      cursor: pointer; user-select: none;
    }
    .row-item.alt { background: rgba(0,0,0,.02); } /* jede 2. Zeile leicht gefärbt */
    .row-item:hover { background: rgba(0,0,0,.045); }

    .iconcell {
      width: 32px; height: 32px;
      display: inline-flex; align-items: center; justify-content: center;
      border-radius: .6rem;
      background: rgba(0,0,0,.04);
      flex: 0 0 auto;
    }
    .iconcell svg { width: 18px; height: 18px; }

    .pill {
      font-size: .75rem;
      padding: .16rem .55rem;
      border-radius: 999px;
      border: 1px solid rgba(0,0,0,.14);
      white-space: nowrap;
    }

    .muted { color: rgba(0,0,0,.6); }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

    /* Skeleton loading */
    .skeleton-row {
      height: var(--row-h);
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: 0 .9rem;
      border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .sk {
      position: relative;
      overflow: hidden;
      background: rgba(0,0,0,.06);
      border-radius: .6rem;
    }
    .sk::after {
      content: "";
      position: absolute;
      top: 0; left: -40%;
      width: 40%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.55), transparent);
      animation: shimmer 1.1s infinite;
    }
    @keyframes shimmer {
      0% { transform: translateX(0); }
      100% { transform: translateX(250%); }
    }

    .sk-icon { width: 32px; height: 32px; border-radius: .6rem; }
    .sk-title { width: 40%; height: 14px; border-radius: 999px; }
    .sk-sub { width: 28%; height: 12px; border-radius: 999px; opacity: .8; }
    .sk-pill { width: 86px; height: 22px; border-radius: 999px; }

  </style>
</head>
<body>

<div class="sticky-topbar">
  <div class="container py-3">
    <div class="d-flex flex-wrap align-items-end gap-2">
      <div class="me-auto">
        <h4 class="mb-0">Produkte Editor</h4>
        <div class="small muted">
          <span id="statusText">Lade…</span>
          <span class="mx-2">•</span>
          <span class="mono">Dirty: <span id="dirtyCount">0</span></span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <div class="input-group" style="min-width:360px">
          <input id="searchInput" class="form-control"
                 placeholder='Suche… z.B. supermarktmenge>10 | abteilung:"Käse" | missing:vorratsort'>
          <button class="btn btn-outline-secondary" id="helpBtn" type="button" title="Syntax-Hilfe">
            <span class="mono">i</span>
          </button>
        </div>

        <select id="sortField" class="form-select" style="width: 240px" disabled>
          <option value="">Sortierfeld lädt…</option>
        </select>

        <button id="sortDirBtn" class="btn btn-outline-secondary" disabled title="Sortierreihenfolge umschalten">A→Z</button>

        <button id="addBtn" class="btn btn-outline-primary" disabled>+ Neu</button>
        <button id="saveBtn" class="btn btn-primary" disabled>Speichern</button>
        <button id="reloadBtn" class="btn btn-outline-secondary">Neu laden</button>
      </div>
    </div>
  </div>
</div>

<div class="container py-3">
  <div class="list-viewport" id="viewport" aria-label="Produktliste">
    <div class="spacer" id="spacer"></div>
  </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Such-Syntax Beispiele</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Du kannst Volltext-Suche und Filter kombinieren. Beispiele (klickbar):</p>
        <div class="list-group" id="helpExamples"></div>

        <hr>
        <div class="small muted">
          Unterstützt:
          <span class="mono">feld:wert</span> (exakt),
          <span class="mono">feld~teil</span> (enthält),
          <span class="mono">feld&gt;10</span>, <span class="mono">feld&gt;=10</span>, <span class="mono">feld&lt;10</span>, <span class="mono">feld&lt;=10</span> (numerisch),
          <span class="mono">missing:feld</span>, <span class="mono">has:feld</span>.
          Werte mit Leerzeichen in Quotes: <span class="mono">vorratsort:"Vorratsschrank"</span>.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Produkt bearbeiten</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>

      <div class="modal-body">
        <!-- ID ist intern, wird nicht angezeigt -->
        <input type="hidden" id="f_id">

        <form id="editForm" class="row g-3" autocomplete="off">
          <div class="col-12">
            <label class="form-label">Name (Key)</label>
            <input class="form-control" id="f_name" required>
            <div class="form-text">Der Name ist auch der JSON-Key. Umbenennen ist möglich.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Abteilung</label>
            <select class="form-select" id="f_abteilung"></select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vorratsort</label>
            <select class="form-select" id="f_vorratsort"></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Produktart</label>
            <select class="form-select" id="f_produktart"></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Standard</label>
            <select class="form-select" id="f_standard"></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Supermarktmenge</label>
            <input class="form-control" id="f_supermarktmenge" placeholder="z.B. 350">
          </div>

          <div class="col-md-4">
            <label class="form-label">Rezepteinheit</label>
            <select class="form-select" id="f_rezepteinheit"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Grundeinheit</label>
            <select class="form-select" id="f_grundeinheit"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Verpackungseinheit</label>
            <select class="form-select" id="f_verpackungseinheit"></select>
          </div>
        </form>

        <div class="alert alert-danger d-none mt-3" id="formError"></div>
      </div>

      <div class="modal-footer">
        <button type="button" id="deleteBtn" class="btn btn-outline-danger me-auto">Löschen</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
        <button type="button" id="applyBtn" class="btn btn-primary">Übernehmen</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Lucide Icons: best-effort per product (mapping). Falls CDN nicht gewünscht: entfernen, es fällt auf "package" zurück. -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
'use strict';

/**
 * Punkt 8 (Icons) – Machbarkeit:
 * - "für jedes Produkt ein Icon finden" ist nicht zuverlässig automatisch möglich (weil Kategorien/Name frei sind),
 *   aber best-effort ist sinnvoll: wir mappen bekannte Keywords (Käse, Obst, Gemüse, Fleisch, Getränke, Haushalt…)
 *   auf ein kleines Set an Lucide-Icons und nehmen sonst ein Default.
 * - Das kostet in der Virtualisierung kaum Performance, weil nur sichtbare Rows gerendert werden.
 */

const ROW_H = 58;

const state = {
  meta: null,

  db: {},             // original map {key: product}
  items: [],          // array of product refs (mutated in place)
  view: [],           // filtered/sorted array of refs

  byId: new Map(),    // id -> productRef
  idToKey: new Map(), // id -> current key (name)
  initialKeyById: new Map(), // id -> key at load time

  // For preserving unknown fields: keep original snapshot by id at open-modal time
  editBaseById: new Map(), // id -> full object clone (as baseline)

  dirtyIds: new Set(),
  deleted: new Map(), // id -> {id,key}
  added: new Map(),   // id -> {key, product}

  sort: { field: 'name', dir: 'asc' }, // dir: 'asc'|'desc'
};

const el = {
  statusText: document.getElementById('statusText'),
  dirtyCount: document.getElementById('dirtyCount'),
  searchInput: document.getElementById('searchInput'),

  sortField: document.getElementById('sortField'),
  sortDirBtn: document.getElementById('sortDirBtn'),

  addBtn: document.getElementById('addBtn'),
  saveBtn: document.getElementById('saveBtn'),
  reloadBtn: document.getElementById('reloadBtn'),

  viewport: document.getElementById('viewport'),
  spacer: document.getElementById('spacer'),

  helpBtn: document.getElementById('helpBtn'),
  helpModal: document.getElementById('helpModal'),
  helpExamples: document.getElementById('helpExamples'),

  modal: document.getElementById('editModal'),
  modalTitle: document.getElementById('modalTitle'),
  formError: document.getElementById('formError'),
  deleteBtn: document.getElementById('deleteBtn'),
  applyBtn: document.getElementById('applyBtn'),

  f_id: document.getElementById('f_id'),
  f_name: document.getElementById('f_name'),
  f_abteilung: document.getElementById('f_abteilung'),
  f_vorratsort: document.getElementById('f_vorratsort'),
  f_produktart: document.getElementById('f_produktart'),
  f_standard: document.getElementById('f_standard'),
  f_supermarktmenge: document.getElementById('f_supermarktmenge'),
  f_rezepteinheit: document.getElementById('f_rezepteinheit'),
  f_grundeinheit: document.getElementById('f_grundeinheit'),
  f_verpackungseinheit: document.getElementById('f_verpackungseinheit'),
};

const bsModal = new bootstrap.Modal(el.modal);
const bsHelp = new bootstrap.Modal(el.helpModal);

function setStatus(msg) { el.statusText.textContent = msg; }
function normalize(s) { return String(s ?? '').toLowerCase().trim(); }

function setDirtyUi() {
  const dirty = state.dirtyIds.size + state.added.size + state.deleted.size;
  el.dirtyCount.textContent = String(dirty);
  el.saveBtn.disabled = dirty === 0;
}

function debounce(fn, ms) {
  let t = null;
  return (...args) => { if (t) clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

function escapeHtml(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function showSkeleton() {
  // show ~12 skeleton rows
  el.spacer.innerHTML = '';
  el.spacer.style.height = 'auto';
  const n = 12;
  for (let i=0;i<n;i++) {
    const row = document.createElement('div');
    row.className = 'skeleton-row' + (i%2 ? ' alt' : '');
    row.innerHTML = `
      <div class="sk sk-icon"></div>
      <div style="flex:1 1 auto">
        <div class="sk sk-title mb-2"></div>
        <div class="sk sk-sub"></div>
      </div>
      <div class="d-flex gap-2">
        <div class="sk sk-pill"></div>
        <div class="sk sk-pill"></div>
        <div class="sk sk-pill"></div>
      </div>
    `;
    el.spacer.appendChild(row);
  }
}

async function loadMeta() {
  const res = await fetch('produkte-editor.php?action=meta&ts=' + Date.now(), {cache:'no-store'});
  if (!res.ok) throw new Error('Meta laden fehlgeschlagen: ' + res.status);
  state.meta = await res.json();
  if (!state.meta || typeof state.meta !== 'object') throw new Error('Ungültige Meta-Daten');
  buildSelectOptions();
}

function buildOptions(selectEl, values, includeEmpty=true) {
  selectEl.innerHTML = '';
  if (includeEmpty) {
    const o = document.createElement('option');
    o.value = '';
    o.textContent = '—';
    selectEl.appendChild(o);
  }
  for (const v of values) {
    const o = document.createElement('option');
    o.value = v;
    o.textContent = v;
    selectEl.appendChild(o);
  }
}

function buildOptionsFromMap(selectEl, mapObj, includeEmpty=true) {
  selectEl.innerHTML = '';
  if (includeEmpty) {
    const o = document.createElement('option');
    o.value = '';
    o.textContent = '—';
    selectEl.appendChild(o);
  }
  for (const [value, label] of Object.entries(mapObj)) {
    const o = document.createElement('option');
    o.value = value;
    o.textContent = `${label} (${value})`;
    selectEl.appendChild(o);
  }
}

function ensureOption(selectEl, value) {
  const v = String(value ?? '');
  if (!v) return;
  if ([...selectEl.options].some(o => o.value === v)) return;
  const o = document.createElement('option');
  o.value = v;
  o.textContent = v;
  selectEl.appendChild(o);
}

function buildSelectOptions() {
  buildOptions(el.f_abteilung, Array.isArray(state.meta.abteilungen) ? state.meta.abteilungen : [], true);
  buildOptionsFromMap(el.f_vorratsort, state.meta.vorratsorte || {'Sonstiger Ort':'Sonstiger Ort'}, true);
  buildOptions(el.f_produktart, Array.isArray(state.meta.produktarten) ? state.meta.produktarten : ['Food','Non-Food'], true);
  buildOptions(el.f_standard, Array.isArray(state.meta.jaNein) ? state.meta.jaNein : ['ja','nein'], true);

  buildOptionsFromMap(el.f_rezepteinheit, state.meta.einheiten || {}, true);
  buildOptionsFromMap(el.f_grundeinheit, state.meta.einheiten || {}, true);
  buildOptionsFromMap(el.f_verpackungseinheit, state.meta.einheiten || {}, true);
}

async function loadData() {
  showSkeleton();
  setStatus('Lade Produkte…');

  const res = await fetch('produkte-editor.php?action=data&ts=' + Date.now(), {cache:'no-store'});
  if (!res.ok) throw new Error('Laden fehlgeschlagen: ' + res.status);
  const db = await res.json();
  if (typeof db !== 'object' || db === null) throw new Error('Ungültiges JSON');

  state.db = db;
  rebuildIndexesFromDb();
  buildSortFieldOptions(); // union of keys
  applyFilterSort();

  setStatus(`Geladen: ${state.items.length} Produkte`);
  setDirtyUi();

  el.addBtn.disabled = false;
  el.sortField.disabled = false;
  el.sortDirBtn.disabled = false;
}

function rebuildIndexesFromDb() {
  state.items = [];
  state.byId.clear();
  state.idToKey.clear();
  state.initialKeyById.clear();

  for (const [key, p] of Object.entries(state.db)) {
    if (!p || typeof p !== 'object') continue;

    const product = {...p}; // shallow clone
    if (typeof product.name !== 'string' || product.name.trim() === '') product.name = key;

    const id = Number(product.id);
    if (!Number.isFinite(id)) continue;

    state.items.push(product);
    state.byId.set(id, product);
    state.idToKey.set(id, key);
    state.initialKeyById.set(id, key);
  }
}

/* ---------- Search Syntax ---------- */
/**
 * Supported tokens (space-separated, quotes for values with spaces):
 * - fulltext token: "käs" (matches name/abteilung/vorratsort/… contains)
 * - field:value exact (case-insensitive string normalize)
 * - field~part contains (case-insensitive)
 * - field>10, field>=10, field<10, field<=10 numeric compare
 * - missing:field, has:field
 */
function tokenize(q) {
  const tokens = [];
  let cur = '', inQ = false;
  for (let i=0;i<q.length;i++){
    const ch = q[i];
    if (ch === '"') { inQ = !inQ; continue; }
    if (!inQ && /\s/.test(ch)) { if (cur) tokens.push(cur); cur=''; continue; }
    cur += ch;
  }
  if (cur) tokens.push(cur);
  return tokens;
}

function parseQuery(qraw) {
  const q = (qraw || '').trim();
  if (!q) return {fulltext: [], filters: []};

  const tokens = tokenize(q);
  const fulltext = [];
  const filters = []; // {type, field, value?, op?, num?}

  for (const t of tokens) {
    // missing/has
    const idxColon = t.indexOf(':');
    if (idxColon > 0) {
      const left = t.slice(0, idxColon).trim();
      const right = t.slice(idxColon+1).trim();

      const lf = normalize(left);
      if (lf === 'missing' && right) { filters.push({type:'missing', field: normalize(right)}); continue; }
      if (lf === 'has' && right) { filters.push({type:'has', field: normalize(right)}); continue; }

      // field:value exact
      if (right !== '') {
        filters.push({type:'eq', field: normalize(left), value: normalize(right)});
        continue;
      }
    }

    // contains: field~part
    const idxTilde = t.indexOf('~');
    if (idxTilde > 0) {
      const field = t.slice(0, idxTilde).trim();
      const part = t.slice(idxTilde+1).trim();
      if (field && part !== '') {
        filters.push({type:'contains', field: normalize(field), value: normalize(part)});
        continue;
      }
    }

    // numeric compare: field>=10 etc.
    const m = t.match(/^([^<>=~:]+)\s*(>=|<=|>|<)\s*(-?\d+(?:[.,]\d+)?)$/);
    if (m) {
      const field = normalize(m[1].trim());
      const op = m[2];
      const num = parseFloat(m[3].replace(',', '.'));
      if (!Number.isNaN(num)) {
        filters.push({type:'num', field, op, num});
        continue;
      }
    }

    // otherwise fulltext
    fulltext.push(normalize(t));
  }

  return {fulltext, filters};
}

function getField(p, field) {
  return p[field];
}

function toNumberMaybe(v) {
  if (v === null || v === undefined) return NaN;
  const s = String(v).trim().replace(',', '.');
  if (!s) return NaN;
  return parseFloat(s);
}

function matchFilters(p, parsed) {
  const id = Number(p.id);
  if (state.deleted.has(id)) return false;

  for (const f of parsed.filters) {
    const val = getField(p, f.field);

    if (f.type === 'missing') {
      if (val !== undefined && val !== null && String(val).trim() !== '') return false;
      continue;
    }
    if (f.type === 'has') {
      if (val === undefined || val === null || String(val).trim() === '') return false;
      continue;
    }
    if (f.type === 'eq') {
      if (normalize(val) !== f.value) return false;
      continue;
    }
    if (f.type === 'contains') {
      if (!normalize(val).includes(f.value)) return false;
      continue;
    }
    if (f.type === 'num') {
      const n = toNumberMaybe(val);
      if (Number.isNaN(n)) return false;
      if (f.op === '>' && !(n > f.num)) return false;
      if (f.op === '>=' && !(n >= f.num)) return false;
      if (f.op === '<' && !(n < f.num)) return false;
      if (f.op === '<=' && !(n <= f.num)) return false;
      continue;
    }
  }

  // fulltext tokens across selected fields
  for (const t of parsed.fulltext) {
    if (!t) continue;
    const hay = [
      p.name, p.abteilung, p.vorratsort, p.produktart, p.standard,
      p.rezepteinheit, p.grundeinheit, p.verpackungseinheit, p.supermarktmenge
    ].map(normalize).join(' | ');
    if (!hay.includes(t)) return false;
  }

  return true;
}

/* ---------- Sorting (all properties) ---------- */
function buildSortFieldOptions() {
  // union of keys across products, plus implicit "name"
  const keys = new Set(['name']);
  for (const p of state.items) Object.keys(p).forEach(k => keys.add(k));
  // Do not show internal id in UI as requested, but allow sorting by it? user wants "nach allem" – ID ist intern,
  // daher lassen wir id weg.
  keys.delete('id');

  const arr = [...keys].sort((a,b)=>a.localeCompare(b,'de',{sensitivity:'base'}));
  el.sortField.innerHTML = '';
  for (const k of arr) {
    const o = document.createElement('option');
    o.value = k;
    o.textContent = k;
    el.sortField.appendChild(o);
  }

  // default
  state.sort.field = arr.includes('name') ? 'name' : arr[0] || 'name';
  state.sort.dir = 'asc';
  el.sortField.value = state.sort.field;
  setSortDirBtn();
}

function setSortDirBtn() {
  el.sortDirBtn.textContent = (state.sort.dir === 'asc') ? 'A→Z' : 'Z→A';
}

function compareValues(a, b) {
  // numeric if both parseable; otherwise string compare
  const na = toNumberMaybe(a);
  const nb = toNumberMaybe(b);
  const aNum = !Number.isNaN(na);
  const bNum = !Number.isNaN(nb);

  if (aNum && bNum) return na - nb;

  // string compare (natural-ish)
  const sa = String(a ?? '').trim();
  const sb = String(b ?? '').trim();
  return sa.localeCompare(sb, 'de', {sensitivity:'base', numeric:true});
}

function applyFilterSort() {
  const parsed = parseQuery(el.searchInput.value);

  let arr = state.items.filter(p => matchFilters(p, parsed));

  const field = state.sort.field || 'name';
  const dir = state.sort.dir;

  arr.sort((p1, p2) => {
    const v1 = getField(p1, field);
    const v2 = getField(p2, field);
    const cmp = compareValues(v1, v2) || compareValues(p1.name, p2.name);
    return dir === 'asc' ? cmp : -cmp;
  });

  state.view = arr;
  el.spacer.style.height = (state.view.length * ROW_H) + 'px';
  renderVirtual();
}

/* ---------- Tags with deterministic colors per value ---------- */
function hashStr(s) {
  let h = 2166136261;
  for (let i=0;i<s.length;i++) {
    h ^= s.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

function tagStyle(prop, value) {
  const key = prop + '::' + String(value ?? '');
  const h = hashStr(key) % 360;
  // readable pastel background
  return {
    background: `hsla(${h}, 70%, 92%, 1)`,
    border: `1px solid hsla(${h}, 40%, 55%, .6)`,
    color: `hsla(${h}, 45%, 22%, 1)`
  };
}

function makeTag(prop, value) {
  const span = document.createElement('span');
  span.className = 'pill';
  const txt = String(value ?? '').trim() || '—';
  span.textContent = txt;
  const st = tagStyle(prop, txt);
  span.style.background = st.background;
  span.style.border = st.border;
  span.style.color = st.color;
  return span;
}

/* ---------- Icons (best-effort mapping) ---------- */
function pickIcon(p) {
  const name = normalize(p.name);
  const dept = normalize(p.abteilung);
  const store = normalize(p.vorratsort);
  const kind = normalize(p.produktart);

  const hay = `${name} ${dept} ${store} ${kind}`;

  // keep to commonly existing lucide icons
  const rules = [
    {re: /(käse|cheese|milch|joghurt|butter|quark)/, icon: 'milk'},
    {re: /(brot|bröt|toast|bäck|bakery)/, icon: 'croissant'},
    {re: /(keks|cookie|kuchen|torte|süß|schoko)/, icon: 'cookie'},
    {re: /(obst|apfel|banane|birne|beere)/, icon: 'apple'},
    {re: /(gemüse|salat|tomate|gurke|karotte|zwiebel)/, icon: 'carrot'},
    {re: /(fleisch|wurst|schinken|hähn|rind|hack)/, icon: 'beef'},
    {re: /(fisch|lachs|thunfisch)/, icon: 'fish'},
    {re: /(getränk|saft|wasser|cola|limonade|bier|wein)/, icon: 'cup-soda'},
    {re: /(tiefkühl|frost|eis)/, icon: 'snowflake'},
    {re: /(putz|reiniger|spül|wasch|haushalt)/, icon: 'spray-can'},
    {re: /(tee|kaffee)/, icon: 'coffee'},
    {re: /(gewürz|pfeffer|salz|kräuter)/, icon: 'pepper'},
    {re: /(konserve|dose)/, icon: 'cylinder'},
  ];

  for (const r of rules) {
    if (r.re.test(hay)) return r.icon;
  }
  // fallback by productart
  if (kind === 'non-food') return 'shopping-bag';
  return 'package';
}

function renderIcon(iconName) {
  // lucide uses <i data-lucide="...">
  const wrap = document.createElement('div');
  wrap.className = 'iconcell';
  const i = document.createElement('i');
  i.setAttribute('data-lucide', iconName);
  wrap.appendChild(i);
  return wrap;
}

/* ---------- Virtual Rendering ---------- */
function renderVirtual() {
  const scrollTop = el.viewport.scrollTop;
  const height = el.viewport.clientHeight;

  const start = Math.max(0, Math.floor(scrollTop / ROW_H) - 6);
  const end = Math.min(state.view.length, Math.ceil((scrollTop + height) / ROW_H) + 6);

  el.spacer.innerHTML = '';

  for (let i = start; i < end; i++) {
    const p = state.view[i];
    const id = Number(p.id);

    const row = document.createElement('div');
    row.className = 'row-item' + ((i % 2) ? ' alt' : '');
    row.style.top = (i * ROW_H) + 'px';
    row.dataset.id = String(id);

    // Icon (best-effort)
    row.appendChild(renderIcon(pickIcon(p)));

    const left = document.createElement('div');
    left.style.flex = '1 1 auto';
    left.innerHTML = `
      <div class="fw-semibold">${escapeHtml(p.name || '')}</div>
      <div class="small muted">${escapeHtml(p.abteilung || '')}</div>
    `;

    const right = document.createElement('div');
    right.className = 'd-flex align-items-center gap-2';

    // Chips colored per value
    right.appendChild(makeTag('produktart', p.produktart || '—'));
    right.appendChild(makeTag('standard', p.standard || '—'));
    right.appendChild(makeTag('vorratsort', p.vorratsort || '—'));

    row.appendChild(left);
    row.appendChild(right);
    el.spacer.appendChild(row);
  }

  // re-initialize lucide icons for visible rows
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons({ attrs: { 'stroke-width': 2 } });
  }
}

/* ---------- Edit / Add / Delete ---------- */
function showFormError(msg) {
  el.formError.textContent = msg;
  el.formError.classList.remove('d-none');
}
function hideFormError() { el.formError.classList.add('d-none'); }

function fillFormFromProduct(p, key) {
  el.f_id.value = String(p.id ?? '');
  el.f_name.value = key || p.name || '';

  // ensure options exist if product has unknown values
  ensureOption(el.f_abteilung, p.abteilung);
  ensureOption(el.f_vorratsort, p.vorratsort);
  ensureOption(el.f_produktart, p.produktart);
  ensureOption(el.f_standard, p.standard);
  ensureOption(el.f_rezepteinheit, p.rezepteinheit);
  ensureOption(el.f_grundeinheit, p.grundeinheit);
  ensureOption(el.f_verpackungseinheit, p.verpackungseinheit);

  el.f_abteilung.value = p.abteilung ?? '';
  el.f_vorratsort.value = p.vorratsort ?? '';
  el.f_produktart.value = p.produktart ?? '';
  el.f_standard.value = p.standard ?? '';
  el.f_supermarktmenge.value = p.supermarktmenge ?? '';
  el.f_rezepteinheit.value = p.rezepteinheit ?? '';
  el.f_grundeinheit.value = p.grundeinheit ?? '';
  el.f_verpackungseinheit.value = p.verpackungseinheit ?? '';
}

function openEditModalById(id) {
  const p = state.byId.get(id);
  if (!p) return;

  const key = state.idToKey.get(id) || p.name || '';
  el.modalTitle.textContent = 'Produkt bearbeiten';
  el.deleteBtn.classList.remove('d-none');
  hideFormError();

  // baseline clone to preserve unknown fields even if UI doesn't show them
  state.editBaseById.set(id, structuredClone(p));

  fillFormFromProduct(p, key);
  bsModal.show();

  el.deleteBtn.dataset.id = String(id);
  el.applyBtn.dataset.id = String(id);
}

function getMaxId() {
  let max = 0;
  for (const p of state.items) max = Math.max(max, Number(p.id) || 0);
  for (const [id] of state.added) max = Math.max(max, id);
  return max;
}

function getUsedIdSet() {
  const s = new Set();
  for (const p of state.items) s.add(Number(p.id));
  for (const [id] of state.added) s.add(Number(id));
  for (const [id] of state.deleted) s.delete(Number(id));
  return s;
}

function nextFreeId() {
  const used = getUsedIdSet();
  let id = getMaxId() + 1;
  while (used.has(id)) id++;
  return id;
}

function openAddModal() {
  const id = nextFreeId();

  // new products: only known fields; no arbitrary extras
  const p = {
    id,
    name: '',
    abteilung: '',
    vorratsort: '',
    produktart: 'Food',
    standard: 'nein',
    supermarktmenge: '',
    rezepteinheit: '',
    grundeinheit: '',
    verpackungseinheit: ''
  };

  el.modalTitle.textContent = 'Neues Produkt';
  el.deleteBtn.classList.add('d-none');
  hideFormError();

  // baseline for new product is empty known fields
  state.editBaseById.set(id, structuredClone(p));

  fillFormFromProduct(p, '');
  bsModal.show();

  el.deleteBtn.dataset.id = '';
  el.applyBtn.dataset.id = String(id);
}

function readProductFromForm() {
  const key = el.f_name.value.trim();
  if (!key) throw new Error('Name darf nicht leer sein.');

  const id = Number(el.f_id.value);
  if (!Number.isFinite(id)) throw new Error('Ungültige interne ID.');

  // preserve unknown fields from baseline
  const base = state.editBaseById.get(id);
  if (!base || typeof base !== 'object') throw new Error('Interner Fehler: Baseline fehlt.');

  // merge: base + known fields updated
  const product = {
    ...base,
    id,                // lock id
    name: key,         // must match key
    abteilung: el.f_abteilung.value,
    vorratsort: el.f_vorratsort.value,
    produktart: el.f_produktart.value,
    standard: el.f_standard.value.trim(),
    supermarktmenge: el.f_supermarktmenge.value.trim(),
    rezepteinheit: el.f_rezepteinheit.value,
    grundeinheit: el.f_grundeinheit.value,
    verpackungseinheit: el.f_verpackungseinheit.value,
  };

  return { key, product };
}

function upsertLocalProduct(id, newKey, product) {
  const existsInMain = state.byId.has(id);
  const existsInAdded = state.added.has(id);

  if (!existsInMain && !existsInAdded) {
    state.added.set(id, { key: newKey, product });
  } else if (existsInAdded) {
    state.added.set(id, { key: newKey, product });
  } else {
    state.dirtyIds.add(id);
  }

  // local update: keep references stable
  if (!existsInMain) {
    state.items.push(product);
    state.byId.set(id, product);
  } else {
    const ref = state.byId.get(id);
    Object.keys(ref).forEach(k => delete ref[k]);
    Object.assign(ref, product);
  }

  state.idToKey.set(id, newKey);
  setDirtyUi();
}

function markDeleted(id) {
  const key = state.idToKey.get(id) || state.initialKeyById.get(id) || '';
  state.deleted.set(id, { id, key });
  state.dirtyIds.delete(id);
  state.added.delete(id);
  setDirtyUi();
}

/* ---------- Save + Client-side ID safety checks ---------- */
function validateBeforeSave() {
  // ensure no duplicate IDs (excluding deleted), and added IDs are new
  const used = new Map(); // id -> where
  for (const p of state.items) {
    const id = Number(p.id);
    if (state.deleted.has(id)) continue;
    if (!Number.isFinite(id)) throw new Error('Ungültige ID im Bestand: ' + p.name);
    if (used.has(id)) throw new Error('Doppelte ID im Bestand: ' + id);
    used.set(id, 'existing');
  }
  for (const [id, a] of state.added) {
    if (state.deleted.has(id)) continue;
    if (used.has(id)) throw new Error(`ID-Kollision: neue ID ${id} existiert bereits`);
    used.set(id, 'added');
  }

  // ensure keys unique across active items
  const keys = new Map(); // key -> id
  for (const p of state.items) {
    const id = Number(p.id);
    if (state.deleted.has(id)) continue;
    const k = String(state.idToKey.get(id) || p.name || '').trim();
    if (!k) throw new Error(`Leerer Name/Key bei ID ${id}`);
    if (keys.has(k) && keys.get(k) !== id) throw new Error(`Name-Kollision: "${k}" wird mehrfach verwendet`);
    keys.set(k, id);
  }
  for (const [id, a] of state.added) {
    if (state.deleted.has(id)) continue;
    const k = String(a.key || '').trim();
    if (!k) throw new Error(`Leerer Name/Key bei neuer ID ${id}`);
    if (keys.has(k) && keys.get(k) !== id) throw new Error(`Name-Kollision: "${k}" existiert bereits`);
    keys.set(k, id);
  }
}

function buildPatchPayload() {
  const added = [];
  for (const [id, a] of state.added.entries()) {
    if (state.deleted.has(id)) continue;
    added.push({ key: a.key, product: a.product });
  }

  const changed = [];
  for (const id of state.dirtyIds.values()) {
    if (state.deleted.has(id)) continue;
    const p = state.byId.get(id);
    if (!p) continue;

    const newKey = state.idToKey.get(id) || p.name || '';
    const oldKey = state.initialKeyById.get(id) || newKey;

    changed.push({ id, oldKey, newKey, product: p });
  }

  const deleted = [];
  for (const [id, d] of state.deleted.entries()) deleted.push(d);

  return { added, changed, deleted };
}

async function savePatch() {
  validateBeforeSave();

  const payload = buildPatchPayload();
  if (!payload.added.length && !payload.changed.length && !payload.deleted.length) {
    setStatus('Keine Änderungen.');
    return;
  }

  setStatus('Speichere…');
  el.saveBtn.disabled = true;

  const res = await fetch('produkte-editor.php?action=save', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });

  if (!res.ok) throw new Error('Speichern fehlgeschlagen: ' + res.status);
  const out = await res.json();
  if (!out.ok) throw new Error(out.error || 'Speichern fehlgeschlagen.');

  state.dirtyIds.clear();
  state.deleted.clear();
  state.added.clear();
  state.editBaseById.clear();

  await loadData();
  setStatus('Gespeichert.');
  setDirtyUi();
}

/* ---------- Help Examples ---------- */
function setSearch(q) {
  el.searchInput.value = q;
  applyFilterSort();
}

function buildHelpExamples() {
  const examples = [
    {
      title: 'A) Supermarktmenge > 10',
      q: 'supermarktmenge>10',
      desc: 'Zeigt alle Produkte, deren supermarktmenge numerisch größer als 10 ist.'
    },
    {
      title: 'B) Volltext enthält „käs“',
      q: 'käs',
      desc: 'Findet alle Produkte, deren Name/Abteilung/Vorratsort usw. „käs“ enthält.'
    },
    {
      title: 'C) Exakt: Abteilung „Internationale Feinkost“',
      q: 'abteilung:"Internationale Feinkost"',
      desc: 'Exakter Match über feld:wert (Quotes für Leerzeichen).'
    },
    {
      title: 'D) Teilstring: Name enthält „bio“',
      q: 'name~bio',
      desc: 'Teilstring-Suche über feld~teil (case-insensitive).'
    },
    {
      title: 'E) Fehlende Werte: ohne Vorratsort',
      q: 'missing:vorratsort',
      desc: 'Datenprüfung: zeigt alle Produkte ohne vorratsort.'
    },
  ];

  el.helpExamples.innerHTML = '';
  for (const ex of examples) {
    const a = document.createElement('button');
    a.type = 'button';
    a.className = 'list-group-item list-group-item-action';
    a.innerHTML = `
      <div class="d-flex justify-content-between align-items-center gap-2">
        <div class="fw-semibold">${escapeHtml(ex.title)}</div>
        <span class="mono">${escapeHtml(ex.q)}</span>
      </div>
      <div class="small muted mt-1">${escapeHtml(ex.desc)}</div>
    `;
    a.addEventListener('click', () => {
      bsHelp.hide();
      setSearch(ex.q);
    });
    el.helpExamples.appendChild(a);
  }
}

/* ---------- Events ---------- */
function wireEvents() {
  el.viewport.addEventListener('scroll', () => renderVirtual());

  el.spacer.addEventListener('click', (e) => {
    const row = e.target.closest('.row-item');
    if (!row) return;
    const id = Number(row.dataset.id);
    if (!Number.isFinite(id)) return;
    openEditModalById(id);
  });

  el.searchInput.addEventListener('input', debounce(() => applyFilterSort(), 200));

  el.sortField.addEventListener('change', () => {
    state.sort.field = el.sortField.value || 'name';
    applyFilterSort();
  });

  el.sortDirBtn.addEventListener('click', () => {
    state.sort.dir = (state.sort.dir === 'asc') ? 'desc' : 'asc';
    setSortDirBtn();
    applyFilterSort();
  });

  el.addBtn.addEventListener('click', () => openAddModal());

  el.reloadBtn.addEventListener('click', async () => {
    if ((state.dirtyIds.size + state.added.size + state.deleted.size) > 0) {
      if (!confirm('Ungespeicherte Änderungen verwerfen und neu laden?')) return;
    }
    state.dirtyIds.clear(); state.added.clear(); state.deleted.clear(); state.editBaseById.clear();
    await loadData();
    setDirtyUi();
  });

  el.saveBtn.addEventListener('click', async () => {
    try { await savePatch(); }
    catch (e) {
      setStatus('Fehler beim Speichern');
      alert(e.message || String(e));
      setDirtyUi();
    }
  });

  el.applyBtn.addEventListener('click', () => {
    hideFormError();
    try {
      const {key, product} = readProductFromForm();
      const id = Number(product.id);

      // Name collision check client-side
      for (const [otherId, otherKey] of state.idToKey.entries()) {
        if (state.deleted.has(otherId)) continue;
        if (otherKey === key && otherId !== id) {
          throw new Error(`Name "${key}" wird bereits verwendet.`);
        }
      }
      for (const [otherId, a] of state.added.entries()) {
        if (state.deleted.has(otherId)) continue;
        if (a.key === key && otherId !== id) {
          throw new Error(`Name "${key}" wird bereits von einem neuen Produkt verwendet.`);
        }
      }

      // If it's an "added" product: ensure id hasn't collided (shouldn't happen due to nextFreeId, but double-safety)
      if (state.added.has(id) === false && state.byId.has(id) === false) {
        // new but not registered yet
      }

      upsertLocalProduct(id, key, product);
      applyFilterSort();
      bsModal.hide();
    } catch (e) {
      showFormError(e.message || String(e));
    }
  });

  el.deleteBtn.addEventListener('click', () => {
    hideFormError();
    const id = Number(el.deleteBtn.dataset.id);
    if (!Number.isFinite(id)) return;
    if (!confirm('Dieses Produkt wirklich löschen?')) return;
    markDeleted(id);
    applyFilterSort();
    bsModal.hide();
  });

  el.modal.addEventListener('shown.bs.modal', () => {
    el.f_name.focus();
    el.f_name.select();
  });

  el.helpBtn.addEventListener('click', () => bsHelp.show());
}

/* ---------- Init ---------- */
(async function main() {
  wireEvents();
  buildHelpExamples();
  showSkeleton();

  try {
    setStatus('Lade Optionen…');
    await loadMeta();
    await loadData();
    setSortDirBtn();
    setDirtyUi();
  } catch (e) {
    setStatus('Fehler beim Laden');
    alert(e.message || String(e));
  }
})();
</script>

</body>
</html>
