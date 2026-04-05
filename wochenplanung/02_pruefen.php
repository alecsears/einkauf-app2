<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Prüfen</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="../assets/tokens.css">
  <style>
    :root {
      --bs-primary-rgb: 49, 132, 253;
      --bs-light-bg-subtle: #fcfcfd;
    }
    body {
      padding-top: 24px;
      padding-bottom: 120px;
      background-color: var(--color-bg);
      font-family: var(--font-family-base);
      line-height: 1.6;
    }
    .material-symbols-outlined {
      font-size: 24px;
      vertical-align: middle;
      cursor: pointer;
    }
    .sticky-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      z-index: 1050;
      background-color: var(--color-surface);
      padding: 12px 16px;
      box-shadow: 0 -2px 15px rgba(0,0,0,0.08);
      border-top: 1px solid var(--color-border);
    }
    .content-card { border-radius: 0.75rem; }
    .korb-tabelle { width: 100%; }
    .korb-tabelle td {
      vertical-align: middle;
      padding: 1rem 0.5rem;
      border-top: none !important;
      border-bottom: 1px solid var(--bs-border-color-translucent) !important;
    }
    .korb-tabelle tr:last-child td { border-bottom: none !important; }
    .col-produkt { width: 55%; min-width: 110px; }
    .col-plus-minus { width: 110px; min-width: 92px; max-width: 120px; }
    .col-einheit-verp {
      width: 13%; min-width: 55px;
      font-size: 0.85rem !important;
    }
    .col-delete { width: 40px; min-width: 40px; }
    .cluster-heading {
      font-weight: 300;
      font-size: 0.8em;
      color: var(--color-text-muted) !important;
      padding: 0.8em 1em;
      background-color: transparent !important;
      border-bottom: 2px solid var(--bs-border-color-translucent);
    }
    .standard-s-badge {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      width: 1em; height: 1em;
      font-size: 0.5em; font-weight: 600;
      margin-left: 8px; vertical-align: middle;
      background-color: var(--color-info); color: var(--color-surface);
      border: none; border-radius: 50%;
      padding: 0; line-height: 1;
    }
    .produkt-name-link {
      text-decoration: none; cursor: pointer;
      color: var(--bs-body-color); font-weight: 500;
    }
    .produkt-name-link:hover { text-decoration: underline; }
    .einheit-untertext { font-size: 0.8em; color: var(--bs-secondary-color); }
    .menge-btn-group {
      display: flex; align-items: center; justify-content: center;
      width: 100px; margin: 0 auto; background: var(--color-surface);
      border-radius: 8px; border: 1px solid var(--bs-border-color); padding: 2px;
    }
    .menge-btn-group .menge-btn {
      border: none; background: none; width: 32px; height: 60px !important;
      display: flex; align-items: center; justify-content: center;
      font-size: 26px !important; color: var(--bs-body-color);
      transition: background-color 0.2s, color 0.2s; border-radius: 6px;
    }
    .produkt-name-link.standard-produkt { color: var(--color-primary) !important; }
    .menge-btn-group .menge-btn .material-symbols-outlined { font-size: 2rem; }
    .menge-btn-group .menge-btn:active, .menge-btn-group .menge-btn:focus {
      background-color: var(--bs-primary-bg-subtle);
      color: var(--bs-primary); outline: none;
    }
    .menge-btn-group .menge-display {
      font-size: 1rem; font-weight: bold; text-align: center;
      color: var(--bs-body-color); min-width: 28px; padding: 0 4px;
    }
    .btn .material-symbols-outlined { margin-right: 4px; font-size: 22px; }
    .btn-icon-only .material-symbols-outlined { margin: 0; }
    .modal-body { max-height: 70vh; overflow-y: auto; }
    .modal-title { font-size: 1.3rem; font-weight: 500; }
    .modal-rezepte-list { font-size: 1.1rem; margin-bottom: 0.3em; margin-top: 0.7em; }
    .modal-standard-badge { display: inline-block; background: var(--color-placeholder-bg); color: var(--color-text-secondary); border-radius: 1em; padding: 0.2em 1em; font-size: 1em; font-weight: 500; margin-bottom: 0.6em; }
    .modal-standard-badge.ja { background: var(--color-info); color: var(--color-surface); }
    .modal-standard-badge.nein { background: var(--color-dot-inactive); color: var(--color-text-muted); }

    #modalZutatInputSuggestions {
      border: 1px solid #ced4da;
      position: absolute;
      background-color: var(--color-surface);
      width: calc(100% - 2rem);
      z-index: 1055;
      display: none;
      overflow-y: auto;
      border-radius: 0 0 0.5rem 0.5rem;
      box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }

    #modalZutatInputSuggestions div { padding: 0.5rem 0.75rem; cursor: pointer; }
    #modalZutatInputSuggestions div:hover { background-color: #e9ecef; }

    @media (max-width: 600px) {
      body { padding-top: 16px; }
      .container { padding-left: 8px; padding-right: 8px; }
      .korb-tabelle td { font-size: 1.1rem; padding: 12px 4px;}
      .col-produkt { width: 52%; }
      .col-plus-minus { width: 105px; }
      .col-delete { width: 38px; }
      .menge-btn-group { width: 95px; }
    }
    .col-produkt .produkt-name-link { font-size: 1rem; }

    @media (max-width: 600px) {
      .modal-dialog { margin: 0; }
      .modal-content { height: 100dvh; border-radius: 0; }
      .modal-body { overflow-y: auto; -webkit-overflow-scrolling: touch; }
    }
    @media (max-width: 600px) {
      .modal-header,
      .modal-footer {
        position: sticky;
        background: var(--color-surface);
        z-index: 1;
      }
      .modal-header { top: 0; }
      .modal-footer { bottom: 0; }
    }
  </style>
</head>
<body>
<div class="container py-4">
  <h1 class="mb-4" id="headlinePruefen">0 Elemente prüfen</h1>
  <div class="table-responsive" id="warenkorb"></div>
</div>

<div class="sticky-footer d-flex align-items-center">
  <div class="flex-grow-1">
    <button type="button" class="btn btn-outline-dark" id="zurueckBtn">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <div class="flex-grow-1 text-center">
    <button class="btn btn-success" id="addZutatBtn">
      <span class="material-symbols-outlined">add_circle</span> Produkt
    </button>
  </div>

  <div class="flex-grow-1 text-end">
    <button class="btn btn-outline-dark" id="speichernBtn" style="max-width:340px;">
      Fertig
    </button>
  </div>
</div>

<!-- Details-Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-body">
        <h5 class="modal-title" id="modalZutatName"></h5>
        <span id="modalStandardBadge" class="modal-standard-badge"></span>
        <div class="modal-rezepte-list" id="modalRezepte"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <span class="material-symbols-outlined">close</span> Schließen
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add-Zutat-Modal -->
<div class="modal fade" id="addZutatModal" tabindex="-1" aria-labelledby="addZutatModalLabel" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="addZutatModalLabel">Zutat hinzufügen</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="mb-3">
        <label for="modalZutatInput" class="form-label">Zutat</label>
        <input type="text" id="modalZutatInput" class="form-control" autocomplete="off">
        <div id="modalZutatInputSuggestions"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" id="modalAbbrechenBtn" data-bs-dismiss="modal">Abbrechen</button>
      <button type="button" id="modalOkBtn" class="btn btn-primary">OK</button>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
'use strict';

let zutaten = [];
let produktListe = {};
let produktNamenListe = [];
let einheiten = {};

// ---------- Helper: Basiseinheiten-Summen effizient berechnen ----------
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
    const idStr = String(z.id);
    const menge = Number(z.rezeptmenge) || 0;
    const einheitLabel = z.rezepteinheit || '';
    const faktor = labelToTranslation.get(String(einheitLabel)) ?? 1;
    const prev = sumById.get(idStr) || 0;
    sumById.set(idStr, prev + (menge * faktor));
  }
  return sumById;
}

// -------------------- Daten laden --------------------
async function ladeProduktDaten() {
  let prodRes, einhRes;
  try {
    [prodRes, einhRes] = await Promise.all([
      fetch('../produkte/produktliste.json?ts=' + Date.now()),
      fetch('../produkte/einheiten.json?ts=' + Date.now())
    ]);
  } catch (e) {
    produktListe = {};
    produktNamenListe = [];
    einheiten = {};
    return;
  }

  if (prodRes && prodRes.ok) {
    let prodRaw = {};
    try { prodRaw = await prodRes.json(); } catch { prodRaw = {}; }

    produktListe = {};
    produktNamenListe = [];

    Object.entries(prodRaw || {}).forEach(([name, val]) => {
      if (!val) return;
      if (val.id !== undefined && val.id !== null && val.id !== "") {
        const produktIdStr = String(val.id);
        produktListe[produktIdStr] = { ...val, _name: name };
        produktNamenListe.push(name);
      }
    });

    produktNamenListe.sort((a, b) => a.localeCompare(b, 'de', { sensitivity: 'base' }));
  }

  if (einhRes && einhRes.ok) {
    let einheitenJson = {};
    try { einheitenJson = await einhRes.json(); } catch { einheitenJson = {}; }
    einheiten = (einheitenJson && einheitenJson.Produkteinheiten) ? einheitenJson.Produkteinheiten : {};
  }
}

function getTranslationFaktor(code) {
  if (!code) return 1;
  const key = String(code).trim();
  if (einheiten?.[key]?.translation !== undefined) return Number(einheiten[key].translation);
  const lowerKey = key.toLowerCase();
  for (const k in einheiten) {
    if (k.toLowerCase() === lowerKey && einheiten[k].translation !== undefined) return Number(einheiten[k].translation);
  }
  return 1;
}
function getProduktName(prod, id) {
  return prod?._name || prod?.name || `#ID:${id}`;
}
function getEinheitLabel(code) {
  if (!code) return "";
  if (einheiten?.[code]?.label) return einheiten[code].label;
  const lowerKey = code.toLowerCase();
  for (const k in einheiten) {
    if (k.toLowerCase() === lowerKey && einheiten[k].label) return einheiten[k].label;
  }
  return code;
}
function deleteZutat(produktId) {
  zutaten = zutaten.filter(z => String(z.id) !== String(produktId));
  render();
}
function updateMenge(produktId, neuePackungsMenge) {
  const produkt = produktListe[String(produktId)];
  if (!produkt || !produkt.supermarktmenge || isNaN(parseFloat(produkt.supermarktmenge)) || parseFloat(produkt.supermarktmenge) <= 0) {
    render();
    return;
  }
  const supermarktMengeNum = parseFloat(produkt.supermarktmenge);
  const neueBasisMengeGesamt = neuePackungsMenge * supermarktMengeNum;
  let zutatAktualisiert = false;
  const verbleibendeZutaten = [];
  let ersteZutatGefunden = false;
  for (let i = 0; i < zutaten.length; i++) {
    if (String(zutaten[i].id) === String(produktId)) {
      if (!ersteZutatGefunden) {
        zutaten[i].rezeptmenge = neueBasisMengeGesamt;
        zutaten[i].rezepteinheit = produkt.supermarkteinheit || "";
        ersteZutatGefunden = true;
        zutatAktualisiert = true;
        verbleibendeZutaten.push(zutaten[i]);
      }
    } else {
      verbleibendeZutaten.push(zutaten[i]);
    }
  }
  zutaten = verbleibendeZutaten;
  if (!zutatAktualisiert && neuePackungsMenge > 0) {
    zutaten.push({
      id: produktId,
      rezeptmenge: neueBasisMengeGesamt,
      rezepteinheit: produkt.supermarkteinheit || "",
      rezeptquelle: [],
      standard: produkt.standard
    });
  }
  render();
}

async function ladeZutatenAusLocalStorage() {
  zutaten = [];
  let rezepteData = [];
  try { rezepteData = JSON.parse(localStorage.getItem('wochenrezepte') || '[]'); }
  catch { rezepteData = []; }

  let slugs = [];
  if (Array.isArray(rezepteData) && rezepteData.length > 0 && typeof rezepteData[0] === 'object' && rezepteData[0].zutaten) {
    slugs = rezepteData.map(r => r.slug);
  } else {
    slugs = rezepteData;
  }

  let allZutaten = [];
  for (const slug of slugs) {
    try {
      const res = await fetch(`../rezeptkasten/rezepte/${slug}.json?ts=${Date.now()}`);
      if (res.ok) {
        const details = await res.json();
        if (Array.isArray(details.zutaten)) {
          allZutaten = allZutaten.concat(details.zutaten.map(z => ({
            id: z.id,
            rezeptmenge: Number(z.rezeptmenge) || 0,
            rezepteinheit: z.rezepteinheit || '',
            rezeptquelle: [details.name || slug],
            standard: produktListe[z.id]?.standard
          })));
        }
      }
    } catch(e){}
  }

  // Zutaten pro ID summieren und rezeptquelle aggregieren
  const summed = {};
  for (const z of allZutaten) {
    if (!z.id) continue;
    const key = String(z.id);
    if (!summed[key]) {
      summed[key] = {...z};
    } else {
      summed[key].rezeptmenge += Number(z.rezeptmenge) || 0;
      if (Array.isArray(z.rezeptquelle)) {
        z.rezeptquelle.forEach(rq => {
          if (!summed[key].rezeptquelle.includes(rq)) summed[key].rezeptquelle.push(rq);
        });
      }
    }
  }

  // Standard-Zutaten (aus Produktliste) ergänzen, falls noch nicht enthalten
  for (const [id, prod] of Object.entries(produktListe)) {
    if ((prod.standard && String(prod.standard).toLowerCase() === 'ja') || prod.standard === true) {
      if (!summed.hasOwnProperty(id)) {
        summed[id] = {
          id: id,
          rezeptmenge: 0,
          rezepteinheit: prod.supermarkteinheit || "",
          rezeptquelle: [],
          standard: prod.standard
        };
      }
    }
  }

  zutaten = Object.values(summed);
  render();
}

// ----------- Basiseinheitensumme für Unterzeile (bleibt erhalten) --------
function berechneBasiseinheitSumme(zutatenArr, produktId, einheiten) {
  let summe = 0;
  zutatenArr.forEach(z => {
    if (String(z.id) === String(produktId)) {
      const menge = Number(z.rezeptmenge) || 0;
      const einheitLabel = z.rezepteinheit || '';
      let faktor = 1;
      for (const code in einheiten) {
        if (einheiten[code].label === einheitLabel) {
          faktor = Number(einheiten[code].translation) || 1;
          break;
        }
      }
      summe += menge * faktor;
    }
  });
  return summe;
}

// ------------------ RENDER: 2 Hauptlisten, darin Gruppierung nach Vorratsort ------------------
function render() {
  const korb = document.getElementById('warenkorb');
  korb.innerHTML = '';

  const basiseinheitById = _buildBasiseinheitSummeById(zutaten, einheiten);

  // je ProduktID ein Anzeigeobjekt
  const itemsById = {};
  zutaten.forEach(z => {
    if (!z.id) return;
    const idStr = String(z.id);
    const prod = produktListe[idStr] || {};
    const mengeEinheit = basiseinheitById.get(idStr) || 0;

    // Hauptliste: Rezeptzutaten vs Standard-Zutaten (nicht mischen!)
    const hasRecipe = Array.isArray(z.rezeptquelle) && z.rezeptquelle.length > 0;
    const isStandard = (z.standard && String(z.standard).toLowerCase() === 'ja') || z.standard === true;
    const cluster = hasRecipe ? 'Rezeptzutaten' : 'Standard-Zutaten';

    // Vorratsort aus produktliste.json
    const vorratsort = (prod.vorratsort && String(prod.vorratsort).trim())
      ? String(prod.vorratsort).trim()
      : 'Sonstiger Ort';

    if (!itemsById[idStr]) {
      itemsById[idStr] = {
        id: idStr,
        name: getProduktName(prod, idStr),
        menge_einheit: mengeEinheit,
        supermarktmenge: prod.supermarktmenge ? Number(prod.supermarktmenge) : null,
        verpackungseinheit: prod.verpackungseinheit || prod.supermarkteinheit || "",
        zutatObj: z,
        cluster,
        isStandard,
        vorratsort
      };
    } else {
      itemsById[idStr].menge_einheit = mengeEinheit;
      if (cluster === 'Rezeptzutaten') itemsById[idStr].cluster = 'Rezeptzutaten';
      itemsById[idStr].zutatObj = z;
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

  const clusterOrder = ['Rezeptzutaten', 'Standard-Zutaten'];

  // Sortierung der Vorratsorte: alphabetisch, aber "Sonstiger Ort" immer zuletzt
  function sortVorratsorte(keys) {
    return keys.sort((a, b) => {
      const al = String(a || '').toLowerCase();
      const bl = String(b || '').toLowerCase();
      if (al === 'sonstiger ort' && bl !== 'sonstiger ort') return 1;
      if (bl === 'sonstiger ort' && al !== 'sonstiger ort') return -1;
      return String(a || '').localeCompare(String(b || ''), 'de', { sensitivity: 'base' });
    });
  }

  if (numProdukte > 0) {
    const table = document.createElement('table');
    table.className = 'table table-sm align-middle mb-2 korb-tabelle';
    const tbody = document.createElement('tbody');

    clusterOrder.forEach(clusterName => {
      const liste = gruppen[clusterName];
      if (!liste.length) return;

      // Haupt-Überschrift: Rezeptzutaten & Standard-Zutaten gleiches Design, doppelt so groß
      const th = document.createElement('tr');
      const titleText = (clusterName === 'Rezeptzutaten')
        ? '🍽️ Rezeptzutaten'
        : '⭐ Standard-Zutaten';

      th.innerHTML = `
        <td colspan="4" class="cluster-heading"
            style="font-weight:600; text-transform:uppercase note; font-size:1.6em;">
          ${titleText}
        </td>`;
      tbody.appendChild(th);

      // innerhalb der Hauptliste nach Vorratsort gruppieren
      const byOrt = {};
      liste.forEach(it => {
        const ort = it.vorratsort || 'Sonstiger Ort';
        if (!byOrt[ort]) byOrt[ort] = [];
        byOrt[ort].push(it);
      });

      const orte = sortVorratsorte(Object.keys(byOrt));

      orte.forEach(ort => {
        const ortListe = byOrt[ort];
        if (!ortListe || !ortListe.length) return;

        // Unter-Überschrift (Vorratsort)
        const sub = document.createElement('tr');
        sub.innerHTML = `<td colspan="4" class="cluster-heading">${ort}</td>`;
        tbody.appendChild(sub);

        // Produkte alphabetisch
        ortListe.sort((a, b) => a.name.localeCompare(b.name, 'de', { sensitivity: 'base' }));

        ortListe.forEach(z => {
          const tr = document.createElement('tr');
          tr.dataset.produktId = z.id;
          tr.style.height = "auto";

          // Name + Unterzeile (Basiseinheit gerundet)
          const nameTd = document.createElement('td');
          nameTd.className = 'col-produkt text-left ps-2 pe-1';
          const isStd = z.isStandard;
          nameTd.innerHTML = `
            <span class="produkt-name-link${isStd ? ' standard-produkt' : ''}">${z.name}</span>
            <br><span class="einheit-untertext">${Math.round(z.menge_einheit)}</span>
          `;
          nameTd.querySelector('.produkt-name-link').onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            showDetailsModal(z.zutatObj, z.name);
          };
          tr.appendChild(nameTd);

          // +/- Packungen
          const anpassungTd = document.createElement('td');
          anpassungTd.className = 'col-plus-minus text-center';
          if (z.supermarktmenge && z.supermarktmenge > 0) {
            const benoetigt = Math.ceil(z.menge_einheit / z.supermarktmenge);
            const mengeGroup = document.createElement('div');
            mengeGroup.className = 'menge-btn-group';

            const minusBtn = document.createElement('button');
            minusBtn.className = 'menge-btn minus';
            minusBtn.type = "button";
            minusBtn.innerHTML = '<span class="material-symbols-outlined">remove</span>';

            const numberSpan = document.createElement('span');
            numberSpan.className = 'menge-display';
            numberSpan.textContent = benoetigt;

            const plusBtn = document.createElement('button');
            plusBtn.className = 'menge-btn plus';
            plusBtn.type = "button";
            plusBtn.innerHTML = '<span class="material-symbols-outlined">add</span>';

            minusBtn.onclick = () => {
              let newVal = Math.max(0, benoetigt - 1);
              updateMenge(z.id, newVal);
            };
            plusBtn.onclick = () => updateMenge(z.id, benoetigt + 1);

            mengeGroup.appendChild(minusBtn);
            mengeGroup.appendChild(numberSpan);
            mengeGroup.appendChild(plusBtn);
            anpassungTd.appendChild(mengeGroup);
          } else {
            anpassungTd.textContent = "-";
          }
          tr.appendChild(anpassungTd);

          // Verpackungseinheit
          const einheitVerpTd = document.createElement('td');
          einheitVerpTd.className = 'col-einheit-verp text-left ps-1 pe-1';
          if (z.supermarktmenge && z.supermarktmenge > 0) {
            einheitVerpTd.textContent = getEinheitLabel(z.verpackungseinheit) || '';
          } else {
            einheitVerpTd.textContent = "-";
          }
          tr.appendChild(einheitVerpTd);

          // Löschen
          const deleteTd = document.createElement('td');
          deleteTd.className = 'col-delete text-center delete-btn pe-1';
          const deleteIcon = document.createElement('span');
          deleteIcon.className = 'material-symbols-outlined';
          deleteIcon.textContent = 'delete';
          deleteIcon.title = 'Produkt löschen';
          deleteIcon.onclick = () => deleteZutat(z.id);
          deleteTd.appendChild(deleteIcon);
          tr.appendChild(deleteTd);

          tbody.appendChild(tr);
        });
      });
    });

    table.appendChild(tbody);
    korb.appendChild(table);
  } else {
    korb.innerHTML = '<p class="text-center p-3">Die Liste ist leer. Füge Zutaten hinzu!</p>';
  }
}

function showDetailsModal(zutatObj, zutatName) {
  const modalEl = new bootstrap.Modal(document.getElementById('detailsModal'));
  document.getElementById('modalZutatName').textContent = zutatName || '';
  const badge = document.getElementById('modalStandardBadge');
  if (typeof zutatObj.standard !== "undefined" && zutatObj.standard !== null) {
    badge.textContent = zutatObj.standard === "ja" ? "Standard-Zutat" : "Nicht Standard";
    badge.className = "modal-standard-badge " + (zutatObj.standard === "ja" ? "ja" : "nein");
  } else {
    badge.textContent = "Nicht Standard";
    badge.className = "modal-standard-badge nein";
  }
  const rezepteDiv = document.getElementById('modalRezepte');
  if (Array.isArray(zutatObj.rezeptquelle) && zutatObj.rezeptquelle.length > 0) {
    rezepteDiv.innerHTML = `<b>Verwendet in:</b><ul style="margin-bottom:0.5em;">${zutatObj.rezeptquelle.map(r => `<li>${r}</li>`).join('')}</ul>`;
  } else {
    rezepteDiv.innerHTML = "<em>Keine Rezeptquelle vorhanden.</em>";
  }
  modalEl.show();
}

async function saveEinkaufslisteJson() {
  const basiseinheitById = _buildBasiseinheitSummeById(zutaten, einheiten);

  let summed = {};
  for (const z of zutaten) {
    if (!z.id) continue;
    const idStr = String(z.id);
    const prod = produktListe[idStr] || {};
    const einheitenWert = basiseinheitById.get(idStr) || 0;

    if (!summed[idStr]) {
      summed[idStr] = {
        id: idStr,
        menge_einheit: einheitenWert,
        supermarktmenge: prod.supermarktmenge ? Number(prod.supermarktmenge) : null,
        verpackungseinheit: prod.verpackungseinheit || prod.supermarkteinheit || "",
      };
    } else {
      summed[idStr].menge_einheit += einheitenWert;
    }
  }

  const arr = Object.values(summed);
  const einkaufsliste = arr.filter(z => z.supermarktmenge && z.supermarktmenge > 0).map(z => {
    const benoetigt = Math.ceil(z.menge_einheit / z.supermarktmenge);
    let einheit = z.verpackungseinheit || "st";
    if (!einheit) einheit = "st";
    return {
      id: Number(z.id),
      einkaufslistenmenge: benoetigt,
      einkaufslisteneinheit: einheit
    };
  });

  let rezepteData = [];
  try { rezepteData = JSON.parse(localStorage.getItem('wochenrezepte') || '[]'); }
  catch { rezepteData = []; }

  try {
    const response = await fetch('../daten/schreibe_rezepte_und_zutaten.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ rezepte: rezepteData, einkaufsliste })
    });
    if (response.ok) {
      await response.json().catch(() => null);
      localStorage.clear();
      window.location.href = '../wochenplanung/03_liste.php';
    } else {
      const errorData = await response.text();
      alert('Fehler beim Speichern der Einkaufsliste: ' + errorData);
    }
  } catch (error) {
    alert('Netzwerk- oder Skriptfehler beim Speichern: ' + error.message);
  }
}

// -------------------- UI: Add-Zutat + Autocomplete --------------------
const modalZutatInput = document.getElementById('modalZutatInput');
const suggestionsContainer = document.getElementById('modalZutatInputSuggestions');

modalZutatInput.addEventListener('input', function() {
  const inputText = this.value.trim().toLowerCase();
  suggestionsContainer.innerHTML = '';
  if (inputText.length > 0) {
    const filteredNamen = produktNamenListe.filter(name => name.toLowerCase().includes(inputText));
    if (filteredNamen.length > 0) {
      const frag = document.createDocumentFragment();
      filteredNamen.forEach(name => {
        const div = document.createElement('div');
        div.textContent = name;
        div.addEventListener('click', function() {
          modalZutatInput.value = name;
          suggestionsContainer.style.display = 'none';
          suggestionsContainer.innerHTML = '';
          document.getElementById('modalOkBtn').focus();
        });
        frag.appendChild(div);
      });
      suggestionsContainer.appendChild(frag);
      suggestionsContainer.style.display = 'block';
    } else {
      suggestionsContainer.style.display = 'none';
    }
  } else {
    suggestionsContainer.style.display = 'none';
  }
});

document.addEventListener('click', function(event) {
  if (!modalZutatInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
    suggestionsContainer.style.display = 'none';
  }
});

function showAddZutatModal() {
  modalZutatInput.value = "";
  suggestionsContainer.style.display = 'none';
  suggestionsContainer.innerHTML = '';
  const modal = new bootstrap.Modal(document.getElementById('addZutatModal'));
  modal.show();
  document.getElementById('addZutatModal').addEventListener('shown.bs.modal', function () {
    modalZutatInput.focus();
  }, { once: true });
}

document.getElementById('modalOkBtn').addEventListener('click', function() {
  const zutatName = modalZutatInput.value.trim();
  if (!zutatName) { alert("Bitte ein Produkt wählen!"); return; }

  let found = null, zutatId = "";
  const targetLower = zutatName.toLowerCase();

  for (const id in produktListe) {
    const p = produktListe[id];
    if (p && p['_name'] && p['_name'].toLowerCase() === targetLower) {
      found = p; zutatId = id; break;
    }
  }
  if (!found) { alert("Produkt nicht gefunden!"); return; }

  const menge = 1;
  const einheitCode = found.verpackungseinheit || found.supermarkteinheit || "";
  zutaten.push({
    id: zutatId,
    rezeptmenge: menge,
    rezepteinheit: einheitCode,
    rezeptquelle: [],
    standard: found && found.standard ? found.standard : undefined
  });

  bootstrap.Modal.getInstance(document.getElementById('addZutatModal')).hide();
  render();
});

document.getElementById('addZutatBtn').addEventListener('click', showAddZutatModal);
document.getElementById('zurueckBtn').addEventListener('click', function(e) {
  e.preventDefault();
  if (confirm('Achtung: Alle Änderungen gehen verloren. Wirklich zurück?')) {
    window.location.href = '01_waehlen.php';
  }
});
document.getElementById('speichernBtn').addEventListener('click', (e) => {
  e.preventDefault();
  saveEinkaufslisteJson();
});

document.addEventListener('DOMContentLoaded', async function() {
  await ladeProduktDaten();
  await ladeZutatenAusLocalStorage();
});
</script>
</body>
</html>
