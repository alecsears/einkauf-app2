<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Prüfen</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="../assets/tokens.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/02_pruefen.css">
</head>
<body>

<!-- ① HEADER -->
<header id="app-header">
  <div class="container py-2">
    <div class="row align-items-center">
      <div class="col">
        <h1 class="h4 mb-0" id="headlinePruefen">0 Elemente prüfen</h1>
      </div>
    </div>
  </div>
</header>

<!-- ② CONTENT -->
<main id="app-content">
  <div class="container py-3">
    <div class="row">
      <div class="col">
        <div class="table-responsive" id="warenkorb"></div>
      </div>
    </div>
  </div>
</main>

<!-- ③ FOOTER -->
<footer id="app-footer">
  <div class="container py-2">
    <div class="row align-items-center g-2">

      <!-- Links: Zurück -->
      <div class="col-auto">
        <button type="button" class="pill-btn" id="zurueckBtn">Zurück</button>
      </div>

      <!-- Mitte: Produkt hinzufügen -->
      <div class="col d-flex justify-content-center">
        <button type="button" class="pill-btn pill-btn--accent" id="addZutatBtn">
          Produkt hinzufügen
        </button>
      </div>

      <!-- Rechts: Fertig -->
      <div class="col-auto">
        <button type="button" class="pill-btn" id="speichernBtn">Fertig</button>
      </div>

    </div>
  </div>
</footer>


<!-- ── Details-Modal ─────────────────────────────────── -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalZutatName"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>

      <div class="modal-body">
        <span id="modalStandardBadge" class="modal-standard-badge"></span>
        <div id="modalRezepte" class="mt-2" style="font-size:1.05rem;"></div>
      </div>

      <div class="modal-footer justify-content-end">
        <button type="button" class="pill-btn" data-bs-dismiss="modal">Schließen</button>
      </div>

    </div>
  </div>
</div>


<!-- ── Zutat-hinzufügen-Modal ────────────────────────── -->
<div class="modal fade" id="addZutatModal" tabindex="-1" aria-labelledby="addZutatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="addZutatModalLabel">Produkt hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3 position-relative">
          <label for="modalZutatInput" class="form-label">Produkt suchen</label>
          <input type="text" id="modalZutatInput" class="form-control" autocomplete="off"
                 placeholder="Produktname eingeben…">
          <div id="modalZutatInputSuggestions"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="pill-btn" id="modalAbbrechenBtn"
                data-bs-dismiss="modal">Abbrechen</button>
        <button type="button" class="pill-btn pill-btn--accent" id="modalOkBtn">Hinzufügen</button>
      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
'use strict';

let zutaten         = [];
let produktListe    = {};
let produktNamenListe = [];
let einheiten       = {};

/* ── Hilfsfunktionen Einheiten ─────────────────────── */
function _buildEinheitenLabelToTranslationMap(einheitenObj) {
  const m = new Map();
  if (!einheitenObj || typeof einheitenObj !== 'object') return m;
  for (const code in einheitenObj) {
    const e = einheitenObj[code];
    if (!e) continue;
    const label = e.label;
    if (!label) continue;
    const t = Number(e.translation);
    m.set(String(label), Number.isFinite(t) ? t : 1);
  }
  return m;
}

function _buildBasiseinheitSummeById(zutatenArr, einheitenObj) {
  const labelToTranslation = _buildEinheitenLabelToTranslationMap(einheitenObj);
  const sumById = new Map();
  for (const z of (zutatenArr || [])) {
    if (!z || !z.id) continue;
    const idStr    = String(z.id);
    const menge    = Number(z.rezeptmenge) || 0;
    const label    = z.rezepteinheit || '';
    const faktor   = labelToTranslation.get(String(label)) ?? 1;
    sumById.set(idStr, (sumById.get(idStr) || 0) + menge * faktor);
  }
  return sumById;
}

/* ── Daten laden ───────────────────────────────────── */
async function ladeProduktDaten() {
  let prodRes, einhRes;
  try {
    [prodRes, einhRes] = await Promise.all([
      fetch('../produkte/produktliste.json?ts=' + Date.now()),
      fetch('../produkte/einheiten.json?ts='    + Date.now())
    ]);
  } catch(e) { return; }

  if (prodRes?.ok) {
    let prodRaw = {};
    try { prodRaw = await prodRes.json(); } catch {}
    produktListe      = {};
    produktNamenListe = [];
    Object.entries(prodRaw || {}).forEach(([name, val]) => {
      if (!val || val.id === undefined || val.id === null || val.id === '') return;
      const id = String(val.id);
      produktListe[id] = { ...val, _name: name };
      produktNamenListe.push(name);
    });
    produktNamenListe.sort((a,b) => a.localeCompare(b, 'de', { sensitivity:'base' }));
  }

  if (einhRes?.ok) {
    let ej = {};
    try { ej = await einhRes.json(); } catch {}
    einheiten = ej?.Produkteinheiten ?? {};
  }
}

/* ── Hilfsfunktionen ───────────────────────────────── */
function getProduktName(prod, id) {
  return prod?._name || prod?.name || `#ID:${id}`;
}
function getEinheitLabel(code) {
  if (!code) return '';
  if (einheiten?.[code]?.label) return einheiten[code].label;
  const lower = code.toLowerCase();
  for (const k in einheiten) {
    if (k.toLowerCase() === lower && einheiten[k].label) return einheiten[k].label;
  }
  return code;
}

function deleteZutat(produktId) {
  zutaten = zutaten.filter(z => String(z.id) !== String(produktId));
  render();
}

function updateMenge(produktId, neuePackungsMenge) {
  const produkt = produktListe[String(produktId)];
  if (!produkt || !produkt.supermarktmenge ||
      isNaN(parseFloat(produkt.supermarktmenge)) ||
      parseFloat(produkt.supermarktmenge) <= 0) { render(); return; }

  const smMenge = parseFloat(produkt.supermarktmenge);
  const neueBasis = neuePackungsMenge * smMenge;
  let updated = false;
  const verbleibend = [];

  for (let i = 0; i < zutaten.length; i++) {
    if (String(zutaten[i].id) === String(produktId)) {
      if (!updated) {
        zutaten[i].rezeptmenge  = neueBasis;
        zutaten[i].rezepteinheit = produkt.supermarkteinheit || '';
        updated = true;
        verbleibend.push(zutaten[i]);
      }
      // doppelte Einträge derselben ID verwerfen
    } else {
      verbleibend.push(zutaten[i]);
    }
  }
  zutaten = verbleibend;
  if (!updated && neuePackungsMenge > 0) {
    zutaten.push({
      id: produktId,
      rezeptmenge: neueBasis,
      rezepteinheit: produkt.supermarkteinheit || '',
      rezeptquelle: [],
      standard: produkt.standard
    });
  }
  render();
}

/* ── LocalStorage laden ────────────────────────────── */
async function ladeZutatenAusLocalStorage() {
  zutaten = [];
  let rezepteData = [];
  try { rezepteData = JSON.parse(localStorage.getItem('wochenrezepte') || '[]'); } catch {}

  const slugs = (Array.isArray(rezepteData) && rezepteData.length > 0 && typeof rezepteData[0] === 'object' && rezepteData[0].zutaten)
    ? rezepteData.map(r => r.slug)
    : rezepteData;

  let allZutaten = [];
  for (const slug of slugs) {
    try {
      const res = await fetch(`../rezeptkasten/rezepte/${slug}.json?ts=${Date.now()}`);
      if (res.ok) {
        const details = await res.json();
        if (Array.isArray(details.zutaten)) {
          allZutaten = allZutaten.concat(details.zutaten.map(z => ({
            id:          z.id,
            rezeptmenge: Number(z.rezeptmenge) || 0,
            rezepteinheit: z.rezepteinheit || '',
            rezeptquelle: [details.name || slug],
            standard: produktListe[z.id]?.standard
          })));
        }
      }
    } catch {}
  }

  // Summieren + rezeptquelle aggregieren
  const summed = {};
  for (const z of allZutaten) {
    if (!z.id) continue;
    const key = String(z.id);
    if (!summed[key]) {
      summed[key] = { ...z };
    } else {
      summed[key].rezeptmenge += Number(z.rezeptmenge) || 0;
      (z.rezeptquelle || []).forEach(rq => {
        if (!summed[key].rezeptquelle.includes(rq)) summed[key].rezeptquelle.push(rq);
      });
    }
  }

  // Standard-Zutaten ergänzen
  for (const [id, prod] of Object.entries(produktListe)) {
    if ((prod.standard && String(prod.standard).toLowerCase() === 'ja') || prod.standard === true) {
      if (!summed[id]) {
        summed[id] = {
          id,
          rezeptmenge:   0,
          rezepteinheit: prod.supermarkteinheit || '',
          rezeptquelle:  [],
          standard:      prod.standard
        };
      }
    }
  }

  zutaten = Object.values(summed);
  render();
}

/* ── Render ────────────────────────────────────────── */
function sortVorratsorte(keys) {
  return keys.sort((a,b) => {
    const al = String(a||'').toLowerCase();
    const bl = String(b||'').toLowerCase();
    if (al === 'sonstiger ort' && bl !== 'sonstiger ort') return 1;
    if (bl === 'sonstiger ort' && al !== 'sonstiger ort') return -1;
    return String(a||'').localeCompare(String(b||''), 'de', { sensitivity:'base' });
  });
}

function render() {
  const korb = document.getElementById('warenkorb');
  korb.innerHTML = '';

  const basiseinheitById = _buildBasiseinheitSummeById(zutaten, einheiten);

  // Ein Anzeigeobjekt je ProduktID
  const itemsById = {};
  zutaten.forEach(z => {
    if (!z.id) return;
    const idStr     = String(z.id);
    const prod      = produktListe[idStr] || {};
    const mengeEinheit = basiseinheitById.get(idStr) || 0;
    const hasRecipe = Array.isArray(z.rezeptquelle) && z.rezeptquelle.length > 0;
    const isStandard = (z.standard && String(z.standard).toLowerCase() === 'ja') || z.standard === true;
    const cluster   = hasRecipe ? 'Rezeptzutaten' : 'Standard-Zutaten';
    const vorratsort = (prod.vorratsort && String(prod.vorratsort).trim())
      ? String(prod.vorratsort).trim()
      : 'Sonstiger Ort';

    if (!itemsById[idStr]) {
      itemsById[idStr] = {
        id: idStr,
        name: getProduktName(prod, idStr),
        menge_einheit: mengeEinheit,
        supermarktmenge: prod.supermarktmenge ? Number(prod.supermarktmenge) : null,
        verpackungseinheit: prod.verpackungseinheit || prod.supermarkteinheit || '',
        zutatObj: z, cluster, isStandard, vorratsort
      };
    } else {
      itemsById[idStr].menge_einheit = mengeEinheit;
      if (cluster === 'Rezeptzutaten') itemsById[idStr].cluster = 'Rezeptzutaten';
      itemsById[idStr].zutatObj  = z;
      itemsById[idStr].isStandard = itemsById[idStr].isStandard || isStandard;
      if (!itemsById[idStr].vorratsort || itemsById[idStr].vorratsort === 'Sonstiger Ort') {
        itemsById[idStr].vorratsort = vorratsort;
      }
    }
  });

  const gruppen = { 'Rezeptzutaten': [], 'Standard-Zutaten': [] };
  Object.values(itemsById).forEach(it => gruppen[it.cluster].push(it));

  const numProdukte = Object.keys(itemsById).length;
  document.getElementById('headlinePruefen').textContent = `${numProdukte} Elemente prüfen`;

  if (numProdukte === 0) {
    korb.innerHTML = '<p class="text-center py-4 text-muted">Die Liste ist leer. Füge Produkte hinzu!</p>';
    return;
  }

  const table  = document.createElement('table');
  table.className = 'table table-sm align-middle mb-2 korb-tabelle';
  const tbody  = document.createElement('tbody');

  ['Rezeptzutaten', 'Standard-Zutaten'].forEach(clusterName => {
    const liste = gruppen[clusterName];
    if (!liste.length) return;

    // Haupt-Überschrift
    const titleText = clusterName === 'Rezeptzutaten'
      ? 'Rezeptzutaten'
      : 'Standard-Zutaten';
    const th = document.createElement('tr');
    th.innerHTML = `<td colspan="4" class="cluster-heading"
        style="font-weight:600;text-transform:uppercase;font-size:1.4em;">${titleText}</td>`;
    tbody.appendChild(th);

    // Vorratsort-Gruppierung
    const byOrt = {};
    liste.forEach(it => {
      const ort = it.vorratsort || 'Sonstiger Ort';
      (byOrt[ort] = byOrt[ort] || []).push(it);
    });

    sortVorratsorte(Object.keys(byOrt)).forEach(ort => {
      const ortListe = byOrt[ort];
      if (!ortListe?.length) return;

      // Unter-Überschrift
      const sub = document.createElement('tr');
      sub.innerHTML = `<td colspan="4" class="cluster-heading">${ort}</td>`;
      tbody.appendChild(sub);

      // Produkte alphabetisch
      ortListe.sort((a,b) => a.name.localeCompare(b.name, 'de', { sensitivity:'base' }));

      ortListe.forEach(z => {
        const tr = document.createElement('tr');
        tr.dataset.produktId = z.id;

        /* -- Name-Spalte -- */
        const nameTd = document.createElement('td');
        nameTd.className = 'col-produkt ps-2 pe-1';
        nameTd.innerHTML = `
          <span class="produkt-name-link${z.isStandard ? ' standard-produkt' : ''}">${z.name}</span>
          <br><span class="einheit-untertext">${Math.round(z.menge_einheit)}</span>`;
        nameTd.querySelector('.produkt-name-link').onclick = e => {
          e.preventDefault(); e.stopPropagation();
          showDetailsModal(z.zutatObj, z.name);
        };
        tr.appendChild(nameTd);

        /* -- +/- Spalte -- */
        const anpassungTd = document.createElement('td');
        anpassungTd.className = 'col-plus-minus text-center';
        if (z.supermarktmenge && z.supermarktmenge > 0) {
          const benoetigt  = Math.ceil(z.menge_einheit / z.supermarktmenge);
          const mengeGroup = document.createElement('div');
          mengeGroup.className = 'menge-btn-group';

          const minusBtn = document.createElement('button');
          minusBtn.className = 'menge-btn';
          minusBtn.type = 'button';
          minusBtn.textContent = '−';
          minusBtn.onclick = () => updateMenge(z.id, Math.max(0, benoetigt - 1));

          const numberSpan = document.createElement('span');
          numberSpan.className = 'menge-display';
          numberSpan.textContent = benoetigt;

          const plusBtn = document.createElement('button');
          plusBtn.className = 'menge-btn';
          plusBtn.type = 'button';
          plusBtn.textContent = '+';
          plusBtn.onclick = () => updateMenge(z.id, benoetigt + 1);

          mengeGroup.append(minusBtn, numberSpan, plusBtn);
          anpassungTd.appendChild(mengeGroup);
        } else {
          anpassungTd.textContent = '–';
        }
        tr.appendChild(anpassungTd);

        /* -- Einheit-Spalte -- */
        const einheitTd = document.createElement('td');
        einheitTd.className = 'col-einheit-verp ps-1 pe-1';
        einheitTd.textContent = (z.supermarktmenge && z.supermarktmenge > 0)
          ? (getEinheitLabel(z.verpackungseinheit) || '')
          : '–';
        tr.appendChild(einheitTd);

        /* -- Löschen-Spalte -- */
        const deleteTd = document.createElement('td');
        deleteTd.className = 'col-delete text-center pe-1';
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'menge-btn menge-btn--delete';
        deleteBtn.type = 'button';
        deleteBtn.title = 'Produkt entfernen';
        deleteBtn.textContent = '×';
        deleteBtn.onclick = () => deleteZutat(z.id);
        deleteTd.appendChild(deleteBtn);
        tr.appendChild(deleteTd);

        tbody.appendChild(tr);
      });
    });
  });

  table.appendChild(tbody);
  korb.appendChild(table);
}

/* ── Details-Modal anzeigen ────────────────────────── */
function showDetailsModal(zutatObj, zutatName) {
  document.getElementById('modalZutatName').textContent = zutatName || '';
  const badge = document.getElementById('modalStandardBadge');
  if (zutatObj.standard !== undefined && zutatObj.standard !== null) {
    badge.textContent  = zutatObj.standard === 'ja' ? 'Standard-Zutat' : 'Nicht Standard';
    badge.className    = 'modal-standard-badge ' + (zutatObj.standard === 'ja' ? 'ja' : 'nein');
  } else {
    badge.textContent  = 'Nicht Standard';
    badge.className    = 'modal-standard-badge nein';
  }
  const rezepteDiv = document.getElementById('modalRezepte');
  rezepteDiv.innerHTML = (Array.isArray(zutatObj.rezeptquelle) && zutatObj.rezeptquelle.length)
    ? `<strong>Verwendet in:</strong><ul class="mb-0 mt-1">${zutatObj.rezeptquelle.map(r => `<li>${r}</li>`).join('')}</ul>`
    : '<em>Keine Rezeptquelle vorhanden.</em>';
  new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

/* ── Einkaufsliste speichern ───────────────────────── */
async function saveEinkaufslisteJson() {
  const basiseinheitById = _buildBasiseinheitSummeById(zutaten, einheiten);
  const summed = {};

  for (const z of zutaten) {
    if (!z.id) continue;
    const idStr  = String(z.id);
    const prod   = produktListe[idStr] || {};
    if (!summed[idStr]) {
      summed[idStr] = {
        id: idStr,
        menge_einheit:     basiseinheitById.get(idStr) || 0,
        supermarktmenge:   prod.supermarktmenge ? Number(prod.supermarktmenge) : null,
        verpackungseinheit: prod.verpackungseinheit || prod.supermarkteinheit || ''
      };
    } else {
      summed[idStr].menge_einheit += basiseinheitById.get(idStr) || 0;
    }
  }

  const einkaufsliste = Object.values(summed)
    .filter(z => z.supermarktmenge && z.supermarktmenge > 0)
    .map(z => ({
      id: Number(z.id),
      einkaufslistenmenge:  Math.ceil(z.menge_einheit / z.supermarktmenge),
      einkaufslisteneinheit: z.verpackungseinheit || 'st'
    }));

  let rezepteData = [];
  try { rezepteData = JSON.parse(localStorage.getItem('wochenrezepte') || '[]'); } catch {}

  try {
    const response = await fetch('../daten/schreibe_rezepte_und_zutaten.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ rezepte: rezepteData, einkaufsliste })
    });
    if (response.ok) {
      await response.json().catch(() => null);
      localStorage.clear();
      window.location.href = '../wochenplanung/03_liste.php';
    } else {
      alert('Fehler beim Speichern: ' + await response.text());
    }
  } catch(err) {
    alert('Netzwerkfehler: ' + err.message);
  }
}

/* ── Autocomplete ──────────────────────────────────── */
const modalZutatInput     = document.getElementById('modalZutatInput');
const suggestionsContainer = document.getElementById('modalZutatInputSuggestions');

modalZutatInput.addEventListener('input', function() {
  const term = this.value.trim().toLowerCase();
  suggestionsContainer.innerHTML = '';
  if (!term) { suggestionsContainer.style.display = 'none'; return; }

  const filtered = produktNamenListe.filter(n => n.toLowerCase().includes(term));
  if (filtered.length) {
    const frag = document.createDocumentFragment();
    filtered.forEach(name => {
      const div = document.createElement('div');
      div.textContent = name;
      div.addEventListener('click', () => {
        modalZutatInput.value = name;
        suggestionsContainer.style.display = 'none';
        document.getElementById('modalOkBtn').focus();
      });
      frag.appendChild(div);
    });
    suggestionsContainer.appendChild(frag);
    suggestionsContainer.style.display = 'block';
  } else {
    suggestionsContainer.style.display = 'none';
  }
});

document.addEventListener('click', e => {
  if (!modalZutatInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
    suggestionsContainer.style.display = 'none';
  }
});

function showAddZutatModal() {
  modalZutatInput.value = '';
  suggestionsContainer.style.display = 'none';
  suggestionsContainer.innerHTML = '';
  const modal = new bootstrap.Modal(document.getElementById('addZutatModal'));
  modal.show();
  document.getElementById('addZutatModal').addEventListener('shown.bs.modal', () => {
    modalZutatInput.focus();
  }, { once: true });
}

/* ── Event-Listener ────────────────────────────────── */
document.getElementById('modalOkBtn').addEventListener('click', function() {
  const zutatName = modalZutatInput.value.trim();
  if (!zutatName) { alert('Bitte ein Produkt wählen!'); return; }

  const targetLower = zutatName.toLowerCase();
  let found = null, zutatId = '';
  for (const id in produktListe) {
    const p = produktListe[id];
    if (p?._name?.toLowerCase() === targetLower) { found = p; zutatId = id; break; }
  }
  if (!found) { alert('Produkt nicht gefunden!'); return; }

  zutaten.push({
    id: zutatId,
    rezeptmenge:   1,
    rezepteinheit: found.verpackungseinheit || found.supermarkteinheit || '',
    rezeptquelle:  [],
    standard:      found.standard
  });

  bootstrap.Modal.getInstance(document.getElementById('addZutatModal')).hide();
  render();
});

document.getElementById('addZutatBtn').addEventListener('click', showAddZutatModal);

document.getElementById('zurueckBtn').addEventListener('click', e => {
  e.preventDefault();
  if (confirm('Achtung: Alle Änderungen gehen verloren. Wirklich zurück?')) {
    window.location.href = '01_waehlen.php';
  }
});

document.getElementById('speichernBtn').addEventListener('click', e => {
  e.preventDefault();
  saveEinkaufslisteJson();
});

document.addEventListener('DOMContentLoaded', async () => {
  await ladeProduktDaten();
  await ladeZutatenAusLocalStorage();
});
</script>
</body>
</html>
