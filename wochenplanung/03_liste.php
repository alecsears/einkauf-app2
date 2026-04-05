<?php
// Einkaufsliste aus home/daten/einkaufsliste.json lesen – relativ zu diesem Script
$einkaufslistePfad = __DIR__ . '/../daten/einkaufsliste.json';
$arr = ['offen' => [], 'erledigt' => []];
if (file_exists($einkaufslistePfad)) {
    $raw = file_get_contents($einkaufslistePfad);
    $data = json_decode($raw, true);
    if (is_array($data)) {
        $arr['offen'] = isset($data['offen']) && is_array($data['offen']) ? $data['offen'] : [];
        $arr['erledigt'] = isset($data['erledigt']) && is_array($data['erledigt']) ? $data['erledigt'] : [];
    }
}
?>
<?php
$vorratsortePath = __DIR__ . '/../produkte/vorratsorte.json';
$vorratsorte = [];

if (is_file($vorratsortePath)) {
    $raw = file_get_contents($vorratsortePath);
    $j = json_decode($raw, true);
    if (is_array($j) && isset($j['Vorratsorte']) && is_array($j['Vorratsorte'])) {
        $vorratsorte = $j['Vorratsorte']; // z.B. ["Kühlschrank" => ["label"=>...,"beschreibung"=>...], ...]
    }
}

// Optional: nach Label sortieren
uasort($vorratsorte, function($a, $b) {
    $la = is_array($a) && isset($a['label']) ? (string)$a['label'] : '';
    $lb = is_array($b) && isset($b['label']) ? (string)$b['label'] : '';
    return strcasecmp($la, $lb);
});
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Einkaufsliste</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="../assets/tokens.css">
  <style>
    body { padding-top: 20px; padding-bottom: 40px; background: #0B0320 !important; color: #e3e3e3; font-family: var(--font-family-base); }
    .material-symbols-outlined { font-size: var(--font-size-icon-md); vertical-align: middle; }
    .table, .table-striped, .table-striped>tbody>tr, .table-striped>tbody>tr td, .table-striped>tbody>tr th {
      background: transparent !important;
      color: #fff !important;
    }
    .table td, .table th {
      font-size:1.1rem;
      padding: 0.6rem 0.7rem;
      border: none !important;
      color: #fff !important;
      border-bottom: 2px solid #444 !important;
      vertical-align: middle;
    }
    .table tr:last-child td { border-bottom: none !important; }
    .row-hover:hover { background-color:#363a3d !important; cursor:pointer; }
    .badge.bg-secondary { background:#000000 !important;  }
    .badge.bg-success { background:#000000 !important; color: #fff; }
    .btn-outline-light { border-color: #444; color: #e3e3e3; }
    .btn-outline-light:hover { background: #444; }
    .modal-content { background: #232528; color: #e3e3e3;  height: 100vh;
      display: flex;
      flex-direction: column;}
    .form-control, .form-select { background: #232528; color: #e3e3e3; border: 1px solid #444; }
    .form-control:focus, .form-select:focus { background: #232528; color: #fff; }
    .autocomplete-suggestions {
      border: 1px solid #444;
      position: absolute;
      background-color: #232528;
      color: #e3e3e3;
      z-index: 1055;
      width: calc(100% - 10px);
      left: 5px;

      /* NEU: deutlich mehr Platz */
      max-height: 40vh;
      overflow-y: auto;

      border-radius: 0 0 0.5rem 0.5rem;
      box-shadow: 0 10px 18px rgba(0,0,0,0.25);
    }

    .autocomplete-suggestion { padding: 8px; cursor: pointer; }
    .autocomplete-suggestion:hover { background-color: #2b2f32; }
    .modal-backdrop.show { opacity:0.9; }
    .btn-clear { color: #000000; border-color: #000000; }
    .btn-clear:hover { background: #000000; color: #fff; }
    .list-cluster-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--color-primary) !important;
      border-radius: 12px;
      padding: 12px 18px;
      margin-bottom: 1rem;
      font-weight: 500;
      font-size: 1.2rem;
      letter-spacing: 0.03em;
    }
    .form-text {
      color: #aaa !important;
      font-size: 0.9em;
      margin-top: 2px;
      margin-bottom: 4px;
    }
    .list-cluster-header .badge { font-size: 1.1em; }
    .einheit-td {
      font-size: 0.83em !important;
      color: #b6b6b6 !important;
      font-weight: 400;
      opacity: 0.9;
      padding-left: 0.2em;
      padding-right: 0.0em;
      white-space: nowrap;
    }
    .menge-td {
      font-size: 1.09em !important;
      font-weight: 500;
      text-align: right;
      padding-right: 0.4em;
      min-width: 3em;
    }
    #erledigtContainer .table tr:last-child td { border-bottom: none !important; }
    .container { max-width: 100%; }
    @keyframes flash-green {
      0%   { background-color: var(--color-accent-green) !important; }
      100% { background-color: transparent !important; }
    }
    .flash-animation {
      animation: flash-green 0.8s ease;
      background-color: var(--color-accent-green) !important;
      transition: background-color 0.8s;
    }
    .sticky-footer {
      position: fixed;
      left: 0;
      bottom: 0;
      width: 100%;
      background: #0B0320;
      z-index: 1030;
      padding: 16px 0 20px 0;
      box-shadow: 0 -2px 16px 0 rgba(0,0,0,0.13);
      text-align: center;
    }
    @media (max-width: 576px) {
      .sticky-footer { padding-bottom: 10px; }
    }

    /* Tabs (Dark Theme) */
    .nav-tabs .nav-link {
      color: #cfd3d7;
      border-color: transparent;
      font-weight: 500;
    }
    .nav-tabs .nav-link:hover { color: #ffffff; }
    .nav-tabs .nav-link.active {
      color: #ffffff !important;
      font-weight: 700;
      background: #232528;
      border-color: #444 #444 #232528;
    }

    /* ==========================================================
       NEU: Verhindert versehentliches Text-Markieren auf der Liste
       - Klick auf Zeilen funktioniert weiterhin (JS bleibt gleich)
       - Inputs/Selects im Modal bleiben normal selektierbar
       ========================================================== */
    #einkaufslisteContainer,
    #einkaufslisteContainer * {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-touch-callout: none;
    }

    /* In Eingabefeldern muss Text markierbar bleiben */
    #addProductModal input,
    #addProductModal textarea,
    #addProductModal select,
    #supermarktModal input,
    #supermarktModal textarea,
    #supermarktModal select {
      -webkit-user-select: text;
      -moz-user-select: text;
      -ms-user-select: text;
      user-select: text;
     _toggle: none;
    }
  </style>
</head>
<body>
  <div class="container py-2" style="padding-bottom:60px; position:relative;">
    <div class="d-flex justify-content-start align-items-center gap-2 mb-4">
      <a href="../start.php" class="btn btn-outline-light" title="Zur Startseite">
        <span class="material-symbols-outlined"  style="font-size:24px;">home</span>
      </a>
      <button class="btn btn-outline-light" type="button" id="marketSelectBtn">
        <span class="material-symbols-outlined" style="font-size:24px; margin-right:4px;">storefront</span>
        <span id="marketName">Wird geladen...</span>
      </button>
    </div>
    <div id="loadingSpinner" style="display:none;">
      <div class="spinner-border text-light" role="status"></div>
      <div>Liste wird geladen ...</div>
    </div>
    <div id="einkaufslisteContainer"></div>
  </div>

  <!-- Modal: Produkt hinzufügen (mit Tabs) -->
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
     <form id="addProductForm" class="modal-content" autocomplete="off" novalidate>

        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">Produkt hinzufügen</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>

        <div class="modal-body pt-0">
          <!-- Tabs -->
          <ul class="nav nav-tabs mt-3" id="addProductTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-bekannt-tab" data-bs-toggle="tab" data-bs-target="#tab-bekannt" type="button" role="tab" aria-controls="tab-bekannt" aria-selected="true">
                Bekanntes Produkt
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-neu-tab" data-bs-toggle="tab" data-bs-target="#tab-neu" type="button" role="tab" aria-controls="tab-neu" aria-selected="false">
                Neues Produkt
              </button>
            </li>
          </ul>

          <div class="tab-content mt-3" id="addProductTabsContent">
            <!-- TAB 1: Bekanntes Produkt -->
            <div class="tab-pane fade show active" id="tab-bekannt" role="tabpanel" aria-labelledby="tab-bekannt-tab" tabindex="0">
              <div class="mb-3 position-relative">
                <label for="produktInput" class="form-label">Produkt</label>
                <input type="text" class="form-control" id="produktInput" required autocomplete="off">
                <div class="autocomplete-suggestions" id="autocompleteBox" style="display:none;"></div>
              </div>
              <div class="mb-3">
                <label for="mengeInput" class="form-label">Packungen</label>
                <input type="number" class="form-control" id="mengeInput" required min="1" value="1" inputmode="numeric">
              </div>
              <div class="mb-3">
                <label for="einheitInput" class="form-label">Einheit</label>
                <input type="text" class="form-control" id="einheitInput" readonly>
              </div>
            </div>

            <!-- TAB 2: Neues Produkt -->
            <div class="tab-pane fade" id="tab-neu" role="tabpanel" aria-labelledby="tab-neu-tab" tabindex="0">
              <div class="row g-3">
                <input type="hidden" id="np_id">

                <div class="col-12">
                  <label for="np_name" class="form-label">Produktname</label>
                  <input type="text" class="form-control" id="np_name" required>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="np_rezepteinheit" class="form-label">Rezepteinheit</label>
                  <div class="form-text">In welcher Einheit steht das Produkt üblicherweise im Rezept?</div>
                  <select id="np_rezepteinheit" class="form-select" required></select>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="np_grundeinheit" class="form-label">Grundeinheit</label>
                   <div class="form-text">In welcher Einheit wird das Produkt gemessen?</div>
                  <select id="np_grundeinheit" class="form-select" required></select>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="np_verpackungseinheit" class="form-label">Packungsart</label>
                   <div class="form-text">Wie nennt man die Packung im Supermarkt?</div>
                  <select id="np_verpackungseinheit" class="form-select" required></select>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="np_supermarktmenge" class="form-label">Grundeinheiten pro Packung</label>
                   <div class="form-text">Wie oft passt 1 Grundeinheit in die Packung?</div>
                  <input type="number" class="form-control" id="np_supermarktmenge" min="1" step="1" inputmode="numeric" required>
                </div>

                <div class="col-12">
                  <label for="np_abteilung" class="form-label">Abteilung</label>
                   <div class="form-text">Wo findet man das Produkt?</div>
                  <select id="np_abteilung" class="form-select" required></select>
                </div>
               <div class="col-12">
  <label for="np_vorratsort" class="form-label">Vorratsort</label>

  <select class="form-select" id="np_vorratsort">
    <option value="">(optional) Bitte wählen…</option>
    <?php foreach ($vorratsorte as $key => $meta): 
        $label = is_array($meta) && isset($meta['label']) ? (string)$meta['label'] : (string)$key;
        $desc  = is_array($meta) && isset($meta['beschreibung']) ? (string)$meta['beschreibung'] : '';
    ?>
      <option value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
              data-beschreibung="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>

  <div class="form-text" id="np_vorratsort_help"></div>
</div>


                <div class="col-12 d-flex align-items-center gap-2">
                  <input type="checkbox" id="np_standard" class="form-check-input">
                  <label for="np_standard" class="form-check-label">Standard</label>
                </div>

                <div class="col-12">
                  <label for="np_produktart" class="form-label">Produktart</label>
                  <select id="np_produktart" class="form-select" required>
                    <option value="Food">Food</option>
                    <option value="Non-Food">Non-Food</option>
                  </select>
                </div>
              </div>
            </div> <!-- /tab-neu -->
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-success">Hinzufügen</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal für Supermarkt Auswahl -->
  <div class="modal fade" id="supermarktModal" tabindex="-1" aria-labelledby="supermarktModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="supermarktForm">
        <div class="modal-header">
          <h5 class="modal-title" id="supermarktModalLabel">Supermarkt wählen</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <div class="modal-body" id="supermarktRadioGroup"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-success">Auswählen</button>
        </div>
      </form>
    </div>
  </div>

  <div class="sticky-footer">
    <button class="btn btn-success btn-lg"  id="wideAddProductBtn" style="min-width:220px;">
      <span class="material-symbols-outlined" style="font-size:30px;">add</span> Produkt hinzufügen
    </button>
  </div>

  <script>
    'use strict';

    // Einkaufsliste aus PHP übernehmen
    let einkaufsliste = <?php echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    einkaufsliste.offen = Array.isArray(einkaufsliste.offen) ? einkaufsliste.offen : [];
    einkaufsliste.erledigt = Array.isArray(einkaufsliste.erledigt) ? einkaufsliste.erledigt : [];

    let abteilungen = [];
    let abteilungsObjekte = [];
    let marktFiles = [];
    let marktNames = [];
    let selectedMarktFile = "";
    let einheitKlarformMap = {};
    let produktListe = {};
    let id2produkt = {};
    let alleProduktNamen = [];
    let lastAddedId = null;

    // NEU: für Tab 2
    let einheitenListe = [];      // [{code, label}]
    let abteilungenTxtListe = []; // ["Obst & Gemüse", ...]

    function getKlarformEinheit(code) {
      return einheitKlarformMap[code] || code || '';
    }

    async function ladeEinheiten() {
      try {
        const res = await fetch('../produkte/einheiten.json', { cache: 'no-store' });
        const json = await res.json();
        einheitKlarformMap = {};
        einheitenListe = [];
        if (json && json.Produkteinheiten) {
          Object.entries(json.Produkteinheiten).forEach(([code, val]) => {
            const label = (val && typeof val === 'object' && val.label) ? val.label : String(code);
            einheitKlarformMap[code] = label;
            einheitenListe.push({ code, label });
          });
          einheitenListe.sort((a,b)=> a.label.localeCompare(b.label, 'de'));
        }
      } catch (e) {
        console.warn('Einheiten konnten nicht geladen werden:', e);
        einheitKlarformMap = {};
        einheitenListe = [];
      }
    }

    async function ladeProduktListe() {
      const res = await fetch('../produkte/produktliste.json', { cache: 'no-store' });
      produktListe = await res.json();
      id2produkt = {};
      alleProduktNamen = [];
      Object.entries(produktListe).forEach(([name, prod]) => {
        if(prod && typeof prod.id !== 'undefined') {
          id2produkt[prod.id] = {...prod, _name: name};
          alleProduktNamen.push({
            id: prod.id,
            name: name,
            supermarkteinheit: prod.supermarkteinheit || "",
            supermarktmenge: prod.supermarktmenge || 1,
            verpackungseinheit: prod.verpackungseinheit || prod.supermarkteinheit || ""
          });
        }
      });
    }

    async function ladeMaerkte() {
      const res = await fetch('../maerkte/maerkte_liste.php?type=json', { cache: 'no-store' });
      const files = await res.json();
      marktNames = [];
      for (const file of files.filter(f => f.endsWith('.json'))) {
        try {
          const json = await fetch(`../maerkte/lokationen/${file}`, { cache: 'no-store' }).then(r => r.json());
          marktNames.push({file, name: json.name || file.replace('.json','')});
        } catch(e) {
          marktNames.push({file, name: file.replace('.json','')});
        }
      }
      if(!selectedMarktFile && marktNames.length) selectedMarktFile = marktNames[0].file;
      updateMarktButton();
    }

    function updateMarktButton() {
      const m = marktNames.find(m=>m.file===selectedMarktFile);
      document.getElementById('marketName').textContent = m ? m.name : 'Markt wählen';
    }

    // NEU: Abteilungen (TXT) für Tab 2 laden
    async function ladeAbteilungenTxt() {
      try {
        const res = await fetch('../maerkte/abteilungen.txt', { cache: 'no-store' });
        const text = await res.text();
        abteilungenTxtListe = text.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
      } catch (e) {
        console.warn('Abteilungen (TXT) konnten nicht geladen werden:', e);
        abteilungenTxtListe = [];
      }
    }

    document.getElementById('marketSelectBtn').addEventListener('click', (ev) => {
      ev.preventDefault();
      const group = document.getElementById('supermarktRadioGroup');
      group.innerHTML = marktNames.map((m, i) => `
        <div class="form-check">
          <input class="form-check-input" type="radio" name="supermarktRadio" id="marktRadio${i}" value="${m.file}" ${selectedMarktFile===m.file ? 'checked' : ''}>
          <label class="form-check-label" for="marktRadio${i}">
            ${m.name}
          </label>
        </div>
      `).join('');
      new bootstrap.Modal(document.getElementById('supermarktModal')).show();
    });

    document.addEventListener('DOMContentLoaded', function() {
      const addBtn = document.getElementById('wideAddProductBtn');
      if (addBtn) {
        addBtn.addEventListener('click', async function(){
          // TAB 1 (bekannt): reset
          document.getElementById('produktInput').value = '';
          document.getElementById('mengeInput').value = 1;
          document.getElementById('einheitInput').value = '';
          document.getElementById('autocompleteBox').style.display = 'none';

          // TAB 2 (neu): Felder befüllen
          try {
            if (!einheitenListe.length) await ladeEinheiten();
            if (!abteilungenTxtListe.length) await ladeAbteilungenTxt();

            document.getElementById('np_id').value = nextFreeProductId();
            document.getElementById('np_name').value = '';
            document.getElementById('np_supermarktmenge').value = '';
     document.getElementById('np_vorratsort').value = '';
document.getElementById('np_vorratsort_help').textContent = '';
document.getElementById('np_vorratsort').addEventListener('change', (e) => {
  const opt = e.target.selectedOptions[0];
  const desc = opt ? (opt.getAttribute('data-beschreibung') || '') : '';
  document.getElementById('np_vorratsort_help').textContent = desc;
});



            document.getElementById('np_standard').checked = false;
            document.getElementById('np_produktart').value = 'Food';

            // Einheiten-Dropdowns
            const einhOptions = einheitenListe.map(x => ({ value: x.code, label: x.label }));
            fillSelect(document.getElementById('np_rezepteinheit'), einhOptions);
            fillSelect(document.getElementById('np_grundeinheit'), einhOptions);
            fillSelect(document.getElementById('np_verpackungseinheit'), einhOptions);

            // Abteilung-Dropdown
            const abtOptions = abteilungenTxtListe.map(name => ({ value: name, label: name }));
            fillSelect(document.getElementById('np_abteilung'), abtOptions);
          } catch(e) {
            console.warn('Initialisierung Neues-Produkt-Felder:', e);
          }

          // Immer mit Tab 1 starten
          const firstTabBtn = document.querySelector('#tab-bekannt-tab');
          if (firstTabBtn) bootstrap.Tab.getOrCreateInstance(firstTabBtn).show();

          new bootstrap.Modal(document.getElementById('addProductModal')).show();
        });
      }
    });

    // Fokus-Funktion für beide Tabs
    document.getElementById('addProductModal').addEventListener('shown.bs.modal', () => {
      const tab1Active = document.getElementById('tab-bekannt').classList.contains('active');
      const tab2Active = document.getElementById('tab-neu').classList.contains('active');

      if (tab1Active) {
        const input = document.getElementById('produktInput');
        if (input) input.focus();
      } else if (tab2Active) {
        const input = document.getElementById('np_name');
        if (input) input.focus();
      }
    });
    document.getElementById('addProductTabs').addEventListener('shown.bs.tab', (e) => {
      if (e.target.id === 'tab-neu-tab') {
        const input = document.getElementById('np_name');
        if (input) input.focus();
      } else if (e.target.id === 'tab-bekannt-tab') {
        const input = document.getElementById('produktInput');
        if (input) input.focus();
      }
    });

    // Helper: Select füllen
    function fillSelect(selectEl, options, {valueKey='value', labelKey='label'} = {}) {
      if (!selectEl) return;
      selectEl.innerHTML = options.map(o =>
        `<option value="${String(o[valueKey]).replaceAll('"','&quot;')}">${String(o[labelKey])}</option>`
      ).join('');
    }

    // Kleinste freie ID ermitteln (überschreibt nie bestehende)
    function nextFreeProductId() {
      const used = new Set(Object.keys(id2produkt).map(k => Number(k)).filter(Number.isFinite));
      let i = 1;
      while (used.has(i)) i++;
      return i;
    }

    // Kleine Utils & robustes Laden
    function trimStr(v){ return (v ?? '').toString().trim(); }
    function isEmpty(v){ return trimStr(v) === ''; }

    function assertNotEmpty(fields) {
      const empty = fields.find(f => isEmpty(f.value));
      if (empty) throw new Error(`Bitte fülle das Feld „${empty.label}“ aus.`);
    }

    async function loadProduktlisteFresh() {
      const r = await fetch('../produkte/produktliste.json?ts=' + Date.now(), { cache: 'no-store' });
      if (!r.ok) throw new Error('produktliste.json nicht erreichbar');
      let data = null;
      try { data = await r.json(); } catch { throw new Error('produktliste.json ist ungültig formatiert.'); }
      if (!data || typeof data !== 'object') throw new Error('produktliste.json leer oder ungültig.');
      return data;
    }

    // Autocomplete + Einheitsfeld setzen
    let selectedProduktId = null;
    document.getElementById('produktInput').addEventListener('input', function(ev){
      const val = ev.target.value.toLowerCase();
      selectedProduktId = null;
      if(!val) {
        document.getElementById('autocompleteBox').style.display = 'none';
        document.getElementById('einheitInput').value = '';
        return;
      }
      const matches = alleProduktNamen.filter(p => p.name.toLowerCase().includes(val)).slice(0,10);
      const box = document.getElementById('autocompleteBox');
      if(matches.length) {
        box.innerHTML = matches.map(p=>`<div class="autocomplete-suggestion" data-id="${p.id}">${p.name}</div>`).join('');
        box.style.display = 'block';
      } else {
        box.style.display = 'none';
      }
      document.getElementById('einheitInput').value = '';
    });

    document.getElementById('autocompleteBox').addEventListener('click', function(ev){
      const el = ev.target.closest('.autocomplete-suggestion');
      if(el) {
        const id = Number(el.getAttribute('data-id'));
        const prod = alleProduktNamen.find(p => p.id === id);
        if(prod) {
          document.getElementById('produktInput').value = prod.name;
          document.getElementById('einheitInput').value = getKlarformEinheit(prod.verpackungseinheit);
          selectedProduktId = prod.id;
          document.getElementById('autocompleteBox').style.display = 'none';
        }
      }
    });

    // Autocomplete schließen bei Klick außerhalb
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('#produktInput') && !ev.target.closest('#autocompleteBox')) {
        const box = document.getElementById('autocompleteBox');
        if (box) box.style.display = 'none';
      }
    });

    document.getElementById('supermarktForm').addEventListener('submit', async function(ev){
      ev.preventDefault();
      const selected = document.querySelector('input[name="supermarktRadio"]:checked');
      if (selected && selected.value !== selectedMarktFile) {
        selectedMarktFile = selected.value;
        updateMarktButton();
        await ladeAbteilungen(selectedMarktFile);
        render();
      }
      bootstrap.Modal.getInstance(document.getElementById('supermarktModal')).hide();
    });

    async function ladeAbteilungen(marktFile) {
      abteilungen = [];
      abteilungsObjekte = [];
      try {
        const json = await fetch(`../maerkte/lokationen/${marktFile}`, { cache: 'no-store' }).then(r => r.json());
        abteilungsObjekte = Array.isArray(json.abteilungen) ? json.abteilungen.slice() : [];
        abteilungsObjekte.sort((a, b) => (a.order ?? 9999) - (b.order ?? 9999));
        abteilungen = abteilungsObjekte.map(a => a.name);
      } catch(e) {
        abteilungen = [];
        abteilungsObjekte = [];
      }
    }

    async function ladeListe() {
      showLoading(true);
      render();
      showLoading(false);
    }

    function showLoading(show) {
      document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
      document.getElementById('einkaufslisteContainer').style.display = show ? 'none' : '';
    }

    function gruppiereNachAbteilungen(zutatenListe) {
      const gruppen = {};
      abteilungen.forEach(a => gruppen[a] = []);
      const sonstige = [];
      zutatenListe.forEach(zutat => {
        const prod = id2produkt[zutat.id];
        let abt = prod && prod.abteilung ? prod.abteilung : null;
        if (abt && abteilungen.includes(abt)) {
          gruppen[abt].push({
            ...zutat,
            name: prod._name || prod.name || "Unbekannt",
            abteilung: abt
          });
        } else {
          if(prod) console.log('Nicht zugeordnet:', prod._name, 'abteilung:', abt);
          sonstige.push({
            ...zutat,
            name: prod ? (prod._name || prod.name || "Unbekannt") : "Unbekannt",
            abteilung: "Sonstige"
          });
        }
      });
      if (sonstige.length) gruppen['Sonstige'] = sonstige;
      return gruppen;
    }

    window.erledigtCollapsed = true;
    function addErledigtToggleListener() {
      const erledigtHeader = document.getElementById('erledigtHeader');
      if (erledigtHeader) {
        erledigtHeader.addEventListener('click', function(e) {
          if (e.target.closest('#clearErledigtBtn') || e.target.classList.contains('badge')) return;
          window.erledigtCollapsed = !window.erledigtCollapsed;
          render();
        });
      }
    }

    function render() {
      const offene = einkaufsliste.offen || [];
      const erledigte = einkaufsliste.erledigt || [];
      const gruppen = gruppiereNachAbteilungen(offene);

      const container = document.getElementById('einkaufslisteContainer');
      container.innerHTML = '';

      let offeneHtml = `
        <div class="list-cluster-header">
          <span>Produkte</span>
          <span class="badge bg-success">${offene.length}</span>
        </div>`;

      const abteilungsReihenfolge = abteilungen.concat('Sonstige');
      let tabellenHtml = '';

      abteilungsReihenfolge.forEach(abt => {
        const zutaten = gruppen[abt] || [];
        if (!zutaten.length) return;
        tabellenHtml += `<tr class="cluster-head" style="background:none !important;">
            <td colspan="3" style="color: #AE93F5 !important; border-bottom: 2px solid #AE93F5 !important; padding-top: 1.5rem !important;">${abt}</td>
          </tr>`;
        zutaten.forEach(p => {
          const rowId = 'row_'+p.id;
          tabellenHtml += `
            <tr class="row-hover" id="${rowId}" data-id="${p.id}">
              <td>${p.name}</td>
              <td class="menge-td">${p.einkaufslistenmenge}</td>
              <td class="einheit-td">${getKlarformEinheit(p.einkaufslisteneinheit)}</td>
            </tr>`;
        });
      });
      offeneHtml += `<div class="table-responsive"><table class="table table-striped"><tbody>${tabellenHtml}</tbody></table></div>`;
      container.innerHTML += offeneHtml;

      if (erledigte.length > 0) {
        let erledigteTabellenHtml = `
          <tr class="cluster-head" style="background:none !important;">
            <td colspan="3" style="color: #4B15DA !important; border-bottom: 2px solid #4B15DA !important; padding-top: 1.5rem !important;"></td>
          </tr>
        `;
        erledigte.forEach(p => {
          erledigteTabellenHtml += `
            <tr class="row-hover" data-id="${p.id}">
              <td>${id2produkt[p.id]?._name || id2produkt[p.id]?.name || 'Unbekannt'}</td>
              <td class="menge-td">${p.einkaufslistenmenge}</td>
              <td class="einheit-td">${getKlarformEinheit(p.einkaufslisteneinheit)}</td>
            </tr>
          `;
        });

        container.innerHTML += `
          <div id="erledigtContainer" class="mt-4">
            <div class="list-cluster-header d-flex justify-content-between align-items-center" style="cursor:pointer;"
                 id="erledigtHeader">
              <span>Erledigt</span>
              <div class="d-flex align-items-center" style="gap:12px;">
                <button class="btn btn-clear btn-sm" id="clearErledigtBtn" type="button" title="Erledigte Einträge löschen">
                  <span class="material-symbols-outlined" style="font-size:22px;">delete</span>
                </button>
                <span class="badge bg-secondary">${erledigte.length}</span>
              </div>
            </div>
            <div id="erledigtTableWrapper" style="display:${window.erledigtCollapsed ? "none" : "block"};">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tbody>${erledigteTabellenHtml}</tbody>
                </table>
              </div>
            </div>
          </div>
        `;
      }
      addErledigtToggleListener();
    }

    // Delegation für Klick auf Tabellenzeilen (statt inline onclick)
    document.addEventListener('click', function(ev){
      // Löschen-Button erledigt
      if(ev.target.closest && ev.target.closest('#clearErledigtBtn')) {
        ev.preventDefault();
        if (confirm("Möchtest du alle erledigten Einträge wirklich löschen?")) {
          einkaufsliste.erledigt = [];
          saveEinkaufslisteJson().then(render).catch(err=>alert(err.message||String(err)));
        }
        return;
      }

      const row = ev.target.closest('tr[data-id]');
      if (!row) return;
      const id = Number(row.getAttribute('data-id'));
      if (!Number.isFinite(id)) return;
      const fromErledigt = !!row.closest('#erledigtContainer');
      toggleErledigt(id, fromErledigt);
    });

    window.toggleErledigt = function(id, fromErledigt = false) {
      id = Number(id);
      let src = fromErledigt ? einkaufsliste.erledigt : einkaufsliste.offen;
      let dst = fromErledigt ? einkaufsliste.offen : einkaufsliste.erledigt;
      const idx = src.findIndex(z => Number(z.id) === id);
      if (idx >= 0) {
        const item = src.splice(idx, 1)[0];
        dst.push(item);
        saveEinkaufslisteJson().then(render).catch(err=>alert(err.message||String(err)));
      }
    };

    document.getElementById('addProductForm').addEventListener('submit', async function(ev){
      ev.preventDefault();

      const tab1Active = document.getElementById('tab-bekannt').classList.contains('active');
      const tab2Active = document.getElementById('tab-neu').classList.contains('active');

      try {
        if (tab1Active) {
          // === BEKANNTES PRODUKT (wie vorher) ===
          const name = trimStr(document.getElementById('produktInput').value);
          const mengeStr = trimStr(document.getElementById('mengeInput').value);
          assertNotEmpty([
            {label:'Produkt', value:name},
            {label:'Packungen', value:mengeStr}
          ]);
          let menge = Number(mengeStr);
          if (!Number.isFinite(menge) || menge <= 0) menge = 1;

          const prodObj = alleProduktNamen.find(p => p.name.toLowerCase() === name.toLowerCase());
          if(!prodObj) throw new Error('Produkt nicht gefunden. Bitte aus der Liste auswählen.');

          const id = prodObj.id;
          const prod = id2produkt[id];
          if(!prod) throw new Error('Produktdaten konnten nicht geladen werden.');

          const einheitCode = prod.verpackungseinheit || prod.supermarkteinheit || "";
          const eintrag = {
            id: id,
            einkaufslistenmenge: menge,
            einkaufslisteneinheit: getKlarformEinheit(einheitCode)
          };

          einkaufsliste.offen ??= [];
          const exist = einkaufsliste.offen.find(z => Number(z.id) === Number(id));
          if (exist) {
            exist.einkaufslistenmenge =
              (parseFloat(exist.einkaufslistenmenge) || 0) + (parseFloat(menge) || 0);
          } else {
            einkaufsliste.offen.push(eintrag);
          }

          await saveEinkaufslisteJson();
          render();
          bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
          return;
        }

        if (tab2Active) {
          // === NEUES PRODUKT ANLEGEN & DANN VERWENDEN ===
          const np = {
            name: trimStr(document.getElementById('np_name').value),
            rezepteinheit: trimStr(document.getElementById('np_rezepteinheit').value),
            grundeinheit: trimStr(document.getElementById('np_grundeinheit').value),
            verpackungseinheit: trimStr(document.getElementById('np_verpackungseinheit').value),
            supermarktmenge: trimStr(document.getElementById('np_supermarktmenge').value),
            vorratsort: trimStr(document.getElementById('np_vorratsort').value),

            abteilung: trimStr(document.getElementById('np_abteilung').value),
            standard: document.getElementById('np_standard').checked ? 'ja' : 'nein',
            produktart: trimStr(document.getElementById('np_produktart').value)
          };

          // Plausicheck (kein Feld leer, Menge > 0)
          assertNotEmpty([
            {label:'Produktname', value:np.name},
            {label:'Rezepteinheit', value:np.rezepteinheit},
            {label:'Grundeinheit', value:np.grundeinheit},
            {label:'Packungsart', value:np.verpackungseinheit},
            {label:'Grundeinheiten pro Packung', value:np.supermarktmenge},
            {label:'Abteilung', value:np.abteilung},
            {label:'Produktart', value:np.produktart},
          ]);
          const mengeNum = Number(np.supermarktmenge);
          if (!Number.isFinite(mengeNum) || mengeNum <= 0) {
            throw new Error('„Grundeinheiten pro Packung“ muss eine positive Zahl sein.');
          }

          // 1) Name uniqueness clientseitig prüfen
          const freshList = await loadProduktlisteFresh();
          const nameExists = Object.keys(freshList || {}).some(
            k => k.toLowerCase() === np.name.toLowerCase()
          );
          if (nameExists) throw new Error('Produktname existiert bereits.');

          // 2) Server: produktliste.json schreiben (Server vergibt freie ID & prüft nochmal)
          const resp = await fetch('../produkte/schreibe_produktliste.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(np)
          });
          let jr = null;
          try { jr = await resp.json(); } catch { /* noop */ }
          if (!resp.ok || !jr || jr.ok !== true) {
            const msg = jr && jr.error ? jr.error : `Speichern in produktliste.json fehlgeschlagen (HTTP ${resp.status}).`;
            throw new Error(msg);
          }

          const created = jr.produkt; // inkl. id
          const createdId = Number(created.id);

          // Lokale Caches updaten
          produktListe[jr.name] = created;
          id2produkt[createdId] = { ...created, _name: jr.name };
          alleProduktNamen.push({
            id: createdId,
            name: jr.name,
            supermarkteinheit: created.supermarkteinheit || '',
            supermarktmenge: created.supermarktmenge || 1,
            verpackungseinheit: created.verpackungseinheit || created.supermarkteinheit || ''
          });

          // 3) Danach erst in einkaufsliste.json eintragen (Default: 1 Packung)
          const einheitCode = created.verpackungseinheit || created.supermarkteinheit || '';
          const eintrag = {
            id: createdId,
            einkaufslistenmenge: 1,
            einkaufslisteneinheit: getKlarformEinheit(einheitCode)
          };
          einkaufsliste.offen ??= [];
          const exist = einkaufsliste.offen.find(z => Number(z.id) === createdId);
          if (exist) {
            exist.einkaufslistenmenge = (parseFloat(exist.einkaufslistenmenge) || 0) + 1;
          } else {
            einkaufsliste.offen.push(eintrag);
          }

          await saveEinkaufslisteJson();
          render();
          bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
          return;
        }

        alert('Kein aktiver Tab erkannt.');
      } catch (err) {
        alert(err.message || String(err));
      }
    });

    async function saveEinkaufslisteJson() {
      const r = await fetch('../daten/schreibe_einkaufsliste.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(einkaufsliste)
      });
      if (!r.ok) throw new Error('Schreiben der einkaufsliste.json fehlgeschlagen.');
    }

    (async function init(){
      await ladeEinheiten();
      await ladeProduktListe();
      await ladeMaerkte();
      await ladeAbteilungenTxt(); // neu: für Tab „Neues Produkt“
      if(selectedMarktFile) await ladeAbteilungen(selectedMarktFile);
      await ladeListe();
    })();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
