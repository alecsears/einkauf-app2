<?php
$heute = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Rezeptauswahl</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    body { padding-top: 80px; background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
    .sticky-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background-color: #fff; border-bottom: 1px solid #ddd; padding: 10px 20px 0 20px; }
    .card-img-top-wrapper { position: relative; height: 100px; width: 100px; cursor: pointer; border-radius: 8px; background: #e9ecef; display: flex; align-items: center; justify-content: center; transition: box-shadow 0.2s; }
    .card-img-top { width: 100%; height: 100%; object-fit: cover; display: block; transition: filter 0.2s; }
    .material-symbols-outlined { font-size: 24px; vertical-align: middle; }
    .btn-icon { padding: 0.375rem; line-height: 1; width: 38px; height: 38px; }
    .buchstabe { font-weight: bold; font-size: 1.5rem; margin-top: 2rem; border-bottom: 1px solid #ccc; }
    .modal-img { width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem; }
    .modal-img-placeholder {
      width: 100%; height: 220px;
      background: #e0e0e0;
      color: #c0c0c0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 80px;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    .sticky-footer { 
      position: fixed; 
      bottom: 0; left: 0; right: 0; 
      background: #fff; 
      border-top: 1px solid #ddd; 
      padding: 0.75rem 1.5rem; 
      box-shadow: 0 -2px 8px rgba(0,0,0,0.04); 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      z-index: 1040;
    }
    .footer-btn { 
      font-size: 1.13rem; 
      font-weight: 500; 
      padding: 0.55em 1.7em; 
      border-radius: 12px; 
      letter-spacing: 0.02em; 
      display: flex; 
      align-items: center; 
      background: #4caf50; 
      color: #fff; 
      border: none; 
      box-shadow: 0 2px 8px rgba(76,175,80,0.10); 
      transition: background 0.16s; 
      text-decoration: none; 
    }
    .footer-btn:hover { 
      background: #388e3c; 
      color: #fff; 
      text-decoration: none; 
    }
    .card { min-height: auto; transition: border-color 0.2s, box-shadow 0.2s; border: 3px solid #ddd; }
    .card-title, .card h5 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: block; margin-bottom: 0; }
    .kachel-title-container { min-width: 0; flex: 1 1 0%; margin-right: 1rem; display: flex; align-items: center; }
    .kachel-btn-stack { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 0.3rem; height: 100%; min-width: 38px; }
    .kachel-content { display: flex; align-items: center; width: 100%; min-width: 0; justify-content: space-between; }
    .vegan-icon { position: absolute; top: 8px; left: 8px; background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 2px; font-size: 20px; color: #4caf50; z-index: 3; }
    .modal-body { max-height: 70vh; overflow-y: auto; }
    .modal-details-table { width: 100%; border-collapse: collapse; margin-bottom: 1.1em; background: transparent; }
    .modal-details-table th, .modal-details-table td { border-bottom: 1px solid #e0e0e0; padding: 0.48em 0.4em 0.48em 0.1em; font-size:1.06em; }
    .modal-details-table th { color: #333; background: #f7f7f7; font-weight: 600; }
    .modal-details-table tr:last-child th, .modal-details-table tr:last-child td { border-bottom: none; }
    .details-label { font-weight: 500; color: #888; }
    .details-value { }
    .modal-section-title { font-weight: 600; font-size:1.12em; margin-top: 1.4em; margin-bottom:0.45em; color: #222; }
    .modal-meta { font-size: 1em; color: #555; margin-bottom: 0.8em; }
    .filters-hidden { display: none !important; }
  </style>
</head>
<body>

<div class="sticky-header">
  <div class="container mt-2">
    <div class="d-flex align-items-center mb-2">
      <a href="../start.php" id="homeBtn" class="btn btn-outline-dark me-2" title="Zur Startseite">
        <span class="material-symbols-outlined">home</span>
      </a>
      <div class="input-group flex-grow-1 mb-0">
        <input type="text" id="suchfeld" class="form-control" placeholder="Rezepte suchen...">
        <button class="btn btn-outline-secondary" type="button" onclick="suchfeld.value=''; applyFilters();">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <button id="filterToggleButton" class="btn btn-outline-primary ms-2" title="Filter ein-/ausblenden" data-bs-toggle="button" aria-pressed="false" autocomplete="off">
        <span class="material-symbols-outlined">filter_list</span>
      </button>
    </div>

    <div id="filterControlsContainer" class="filters-hidden">
        <div class="d-flex align-items-center">
            <button id="vegFilter" class="btn btn-outline-success me-3" onclick="applyFilters()">Vegetarisch</button>
            <select id="freqFilter" class="form-select w-auto" onchange="applyFilters()">
                <option value="all">Alle Häufigkeiten</option>
                <option value="0">Selten gekocht</option>
                <option value="1">Manchmal gekocht</option>
                <option value="2">Evergreen</option>
            </select>
        </div>
    </div>
  </div>
</div>

<div class="container py-4">
  <h3 class="mb-4"><span id="rezeptCounter">0</span> Rezepte</h3>
  <div id="rezepteContainer" class="row g-4"></div>
</div>

<!-- Modal für Rezeptdetails -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <span class="modal-titel" id="modalRezeptTitel"></span>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body" id="modalRezeptBody">
        <!-- Dynamischer Inhalt -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Rezepte laden (nur Basisdaten, für Modal werden Details nachgeladen)
  const rezepte = <?php
    $rezepte = [];
    foreach (glob('../rezeptkasten/rezepte/*.json') as $datei) {
      $slug = pathinfo($datei, PATHINFO_FILENAME);
      $json = json_decode(file_get_contents($datei), true);
      if (empty($json['name'])) continue;

      // WEBP ONLY: Bildpfad prüfen
      $bild = file_exists("../rezeptkasten/bilder/{$slug}.webp") ? "../rezeptkasten/bilder/{$slug}.webp" : null;

      $rezepte[] = [
        'slug' => $slug,
        'titel' => $json['name'],
        'bild' => $bild,
        'vegetarisch' => $json['vegetarisch'],
        'kalorien' => $json['kalorien'],
        'frequenz' => $json['frequenz']
      ];
    }
    usort($rezepte, fn($a, $b) => strnatcasecmp($a['titel'], $b['titel']));
    echo json_encode($rezepte);
  ?>;

  // Produktliste (zum Auflösen im Modal)
  window.produktListe = {};
  async function ladeProduktListe() {
    try {
      const res = await fetch("../produkte/produktliste.json");
      if (res.ok) {
        const obj = await res.json();
        window.produktListe = {};
        Object.entries(obj).forEach(([name, data]) => {
          if (data.id !== undefined) window.produktListe[data.id] = { ...data, _name: name };
        });
      }
    } catch (e) {
      window.produktListe = {};
    }
  }
  ladeProduktListe();

  const container = document.getElementById('rezepteContainer');
  document.getElementById('rezeptCounter').textContent = rezepte.length;

  let recipeCards = [];
  rezepte.forEach(r => recipeCards.push({...r, visible:true}));

  function renderRecipes() {
    container.innerHTML = '';
    const grouped = {};
    recipeCards.forEach(r => {
      const buchstabe = r.titel.charAt(0).toUpperCase();
      if (!grouped[buchstabe]) grouped[buchstabe] = [];
      grouped[buchstabe].push(r);
    });
    let count = 0;
    for (const buch of Object.keys(grouped).sort()) {
      const visibleInGroup = grouped[buch].some(r => r.visible);
      if (!visibleInGroup) continue;

      const header = document.createElement('div');
      header.className='col-12 buchstabe';
      header.textContent=buch;
      container.appendChild(header);

      grouped[buch].forEach(r => {
        if (!r.visible) return;
        count++;
        const col = document.createElement('div'); col.className='col-md-4 col-sm-6';
        const card = document.createElement('div');
        card.className = 'card shadow-sm p-2 d-flex flex-row align-items-center';
        const imgWrapper = document.createElement('div'); imgWrapper.className='flex-shrink-0 card-img-top-wrapper';

        imgWrapper.innerHTML = r.bild
          ? `<img src="${r.bild}" class="card-img-top">`
          : `<span class="material-symbols-outlined" style="font-size:48px;color:#ccc;">restaurant</span>`;

        if (r.vegetarisch==='ja') imgWrapper.insertAdjacentHTML('beforeend','<span class="material-symbols-outlined vegan-icon">eco</span>');

        const content = document.createElement('div'); content.className='kachel-content ms-3';
        const titleC=document.createElement('div'); titleC.className='kachel-title-container';
        const title=document.createElement('h5'); title.className='card-title'; title.textContent=r.titel;
        titleC.appendChild(title);

        const btnStack = document.createElement('div');
        btnStack.className = 'kachel-btn-stack';

        const infoBtn = document.createElement('button');
        infoBtn.className = 'btn btn-outline-secondary btn-icon kachel-preview-btn';
        infoBtn.title = 'Rezeptinfo anzeigen';
        infoBtn.innerHTML = '<span class="material-symbols-outlined">info</span>';
        infoBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          showPreviewModal(r.slug, r.bild);
        });

        const editBtn = document.createElement('a');
        editBtn.href = `../rezeptkasten/rezepteditor.php?edit=${r.slug}.json`;
        editBtn.className = 'btn btn-outline-secondary btn-icon kachel-edit-btn';
        editBtn.title = 'Bearbeiten';
        editBtn.innerHTML = '<span class="material-symbols-outlined">edit</span>';
        editBtn.addEventListener('click', function(e) { e.stopPropagation(); });

        btnStack.appendChild(infoBtn);
        btnStack.appendChild(editBtn);
        content.append(titleC, btnStack);
        card.append(imgWrapper, content);
        col.appendChild(card);
        container.appendChild(col);
      });
    }
    document.getElementById('rezeptCounter').textContent = count;
  }

  renderRecipes();

  function applyFilters() {
    const vegFilter = document.getElementById('vegFilter');
    const vegOn = vegFilter.classList.contains('active');
    if (event.currentTarget === vegFilter) {
      vegFilter.classList.toggle('active');
    }
    const freqVal = document.getElementById('freqFilter').value;
    recipeCards.forEach(r => {
      r.visible = true;
      if (document.getElementById('vegFilter').classList.contains('active') && r.vegetarisch!=='ja') r.visible = false;
      if (freqVal!=='all' && r.frequenz.toString()!==freqVal) r.visible = false;
    });
    renderRecipes();
  }

  // ---- MODAL ----
  async function showPreviewModal(slug, bild) {
    try {
      const res = await fetch(`../rezeptkasten/rezepte/${slug}.json?ts=${Date.now()}`);
      if (!res.ok) throw new Error("Rezeptdaten konnten nicht geladen werden!");
      const details = await res.json();

      // Bild-URL bestimmen: param > details.bild > Standardpfad
      const imgUrl = (bild || details.bild || `../rezeptkasten/bilder/${slug}.webp`);

      let bildHtml = "";
      if (imgUrl) {
        bildHtml = `<img src="${imgUrl}?t=${Date.now()}" class="modal-img" alt="">`;
      } else {
        bildHtml = `<div class="modal-img-placeholder">
          <span class="material-symbols-outlined" style="font-size:48px;color:#ccc;">restaurant</span>
        </div>`;
      }

      // Zutaten-Tabelle
      let zutatenTableHtml = "";
      if (Array.isArray(details.zutaten) && details.zutaten.length > 0) {
        zutatenTableHtml = `<table class="modal-details-table mb-2"><thead>
          <tr><th>Zutat</th><th style="text-align:right;">Menge</th><th>Einheit</th></tr></thead><tbody>`;
        for (const z of details.zutaten) {
          let zName = "";
          if (z.id && window.produktListe && window.produktListe[z.id]) {
            zName = window.produktListe[z.id]._name || window.produktListe[z.id].name || "";
          } else {
            zName = z.name || "Unbekannt";
          }
          zutatenTableHtml += `<tr>
            <td>${zName}</td>
            <td style="text-align:right;">${(z.rezeptmenge !== undefined ? z.rezeptmenge : "")}</td>
            <td>${z.rezepteinheit !== undefined ? z.rezepteinheit : ""}</td>
          </tr>`;
        }
        zutatenTableHtml += `</tbody></table>`;
      }

      // Kalorien/Frequenz
      let metaHtml = "";
      if (details.kalorien || details.frequenz !== undefined) {
        metaHtml += `<div class="modal-meta">`;
        if (details.kalorien) {
          metaHtml += `<span>Kalorien: <b>${details.kalorien} kcal</b></span><br>`;
        }
        if (details.frequenz !== undefined) {
          let freqTxt = "Unbekannt";
          if (details.frequenz == 0) freqTxt = "Selten gekocht";
          if (details.frequenz == 1) freqTxt = "Manchmal gekocht";
          if (details.frequenz == 2) freqTxt = "Evergreen";
          metaHtml += `${freqTxt}</b></span>`;
        }
        metaHtml += `</div>`;
      }

      // Zubereitung
      let zubereitungHtml = "";
      if (details.zubereitung) {
        let text = details.zubereitung;
        text = text.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
        text = text.replace(/\*(.+?)\*/g, '<i>$1</i>');
        text = text.replace(/(^|\n)(\d+\.\s.*(?:\n\d+\.\s.*)*)/g, function(match, pre, list) {
          const lines = list.split(/\n/).filter(Boolean);
          if (lines.length < 2) return match;
          return pre + '<ol>' + lines.map(l => '<li>' + l.replace(/^\d+\.\s/, '') + '</li>').join('') + '</ol>';
        });
        text = text.replace(/(^|\n)((?:- .*(?:\n|$))+)/g, function(match, pre, list) {
          const lines = list.split(/\n/).filter(l=>l.match(/^- /));
          if (lines.length < 2) return match;
          return pre + '<ul>' + lines.map(l => '<li>' + l.replace(/^- /, '') + '</li>').join('') + '</ul>';
        });
        text = text.replace(/\n/g, '<br>');

        zubereitungHtml = `<div class="modal-section-title">Zubereitung</div>
            <div style="white-space:normal" id="zubereitungText">${text}</div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-3 mt-2" id="ttsZubereitungBtn">
              <span class="material-symbols-outlined align-middle" style="font-size:20px;">volume_up</span> Vorlesen
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3 ms-2 mt-2 d-none" id="ttsStopBtn">
              <span class="material-symbols-outlined align-middle" style="font-size:20px;">stop</span> Stopp
            </button>
        `;
      }

      document.getElementById('modalRezeptTitel').innerHTML = details.name +
        (details.vegetarisch === 'ja' ? ' <span class="modal-vegan-icon material-symbols-outlined" title="Vegetarisch">eco</span>' : '');

      document.getElementById('modalRezeptBody').innerHTML = `
        ${bildHtml}
        ${zutatenTableHtml}
        ${zubereitungHtml}
        ${metaHtml}
      `;

      // TTS
      if (details.zubereitung) {
        const ttsBtn = document.getElementById('ttsZubereitungBtn');
        const ttsStopBtn = document.getElementById('ttsStopBtn');
        let utterance = null;
        ttsBtn?.addEventListener('click', () => {
          if ('speechSynthesis' in window) {
            let ttsText = details.zubereitung.replace(/\*\*|\*/g, '').replace(/<[^>]+>/g, ' ');
            utterance = new SpeechSynthesisUtterance(ttsText);
            utterance.lang = 'de-DE';
            window.speechSynthesis.speak(utterance);
            ttsBtn.classList.add('d-none');
            ttsStopBtn.classList.remove('d-none');
            utterance.onend = utterance.onerror = () => {
              ttsBtn.classList.remove('d-none');
              ttsStopBtn.classList.add('d-none');
            };
          } else {
            alert('Vorlesen wird von diesem Browser nicht unterstützt.');
          }
        });
        ttsStopBtn?.addEventListener('click', () => {
          window.speechSynthesis.cancel();
          ttsBtn.classList.remove('d-none');
          ttsStopBtn.classList.add('d-none');
        });
      }

      const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
      modal.show();
    } catch (e) {
      alert("Fehler beim Laden der Rezeptdetails: " + e.message);
    }
  }

  const suchfeld = document.getElementById('suchfeld');
  suchfeld.addEventListener('input', () => {
    const term = suchfeld.value.trim().toLowerCase();
    recipeCards.forEach(r => r.visible = r.titel.toLowerCase().includes(term));
    renderRecipes();
  });

  document.addEventListener('DOMContentLoaded', () => {
    const filterToggleButton = document.getElementById('filterToggleButton');
    const filterControlsContainer = document.getElementById('filterControlsContainer');
    filterToggleButton.addEventListener('click', () => {
      filterControlsContainer.classList.toggle('filters-hidden');
      filterToggleButton.classList.toggle('active');
    });
  });

  document.getElementById('homeBtn').addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = this.href;
  });
</script>

<div class="sticky-footer" style="justify-content: center;">
  <a href="../rezeptkasten/rezepteditor.php" class="footer-btn">
    <span class="material-symbols-outlined me-2">add</span>
    Neues Rezept
  </a>
</div>
</body>
</html>
