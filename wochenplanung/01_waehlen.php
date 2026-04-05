<?php
$heute = date('d.m.Y');

// Statistik serverseitig laden
$statsRaw = [];
$statsFile = '../daten/stats.json';
if (file_exists($statsFile)) {
    $s = json_decode(file_get_contents($statsFile), true);
    if (is_array($s) && !empty($s['rezepte'])) {
        foreach ($s['rezepte'] as $name => $data) {
            $statsRaw[$name] = $data['anzahl'] ?? 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Rezeptauswahl</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link href="/einkauf-app/assets/rezept-modal.css" rel="stylesheet">
  <style>
    body { padding-top: 120px; padding-bottom: 120px;background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
    .sticky-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background-color: #fff; border-bottom: 1px solid #ddd; padding: 0px 0px 0px 0px; }
    .card-img-top-wrapper {  border-radius: 10px;position: relative; height: 100px; width: 100px; cursor: pointer; border-radius: 10px; background: #4B15DA; display: flex; align-items: center; justify-content: center; transition: box-shadow 0.2s; }
    .card-img-top {  border-radius: 10px;width: 100%; height: 100%; object-fit: cover; display: block; transition: filter 0.2s; }
    .material-symbols-outlined { font-size: 24px; vertical-align: middle; }
    .btn-icon { padding: 0.375rem; line-height: 1; width: 38px; height: 38px; }
    .buchstabe { font-weight: bold; font-size: 1.5rem; margin-top: 3rem; border-bottom: 1px solid #ccc; }
    .card {  border-radius: 16px; min-height: auto; transition: border-color 0.2s, box-shadow 0.2s; border: 2px solid #ddd; }
    .selected-card { border: 1px solid #4B15DA !important; background: #EDE7FB !important; box-shadow: 0 0 0 3px #4B15DA !important; }
    .card-title, .card h5 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: block; margin-bottom: 0; }
    .kachel-title-container { min-width: 0; flex: 1 1 0%; margin-right: 1rem; display: flex; align-items: center; }
    .kachel-btn-stack { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 0.3rem; height: 100%; min-width: 38px; }
    .kachel-content { display: flex; align-items: center; width: 100%; min-width: 0; justify-content: space-between; }
    .vegan-icon { position: absolute; top: 8px; left: 8px; background-color: #23af64cc; border-radius: 50%; padding: 2px; font-size:25px; color: #fff; z-index: 3; }
    .auswahl-liste { display: flex; flex-wrap: wrap; gap: 0.2rem; margin-bottom: 0.5rem; margin-top: 0.5rem; }
    .auswahl-badge { background: #4B15DA; color: #fff; border-radius: 1rem; padding: 0.3em 0.3em; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.2em; }
    .details-label { font-weight: 500; color: #888; }
    .details-value { }
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #ddd; padding: 0.75rem 1.5rem; box-shadow: 0 -2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; z-index: 1040; }
    .footer-btn { font-size: 1.13rem; font-weight: 500; padding: 0.55em 1.7em; border-radius: 12px; letter-spacing: 0.02em; display: flex; align-items: center; background: #23af64; color: #fff; border: none; box-shadow: 0 2px 8px #23af641a; transition: background 0.16s; text-decoration: none; }
    .footer-btn:hover { background: #1c8951; color: #fff; text-decoration: none; }
    .footer-reset-btn { font-size: 1.13rem; font-weight: 500; padding: 0.55em 1.7em; border-radius: 12px; letter-spacing: 0.02em; display: flex; align-items: center; background: #fff; color: #23af64; border: 2px solid #23af64; box-shadow: 0 2px 8px #23af641a; transition: background 0.16s; text-decoration: none; }
    .footer-reset-btn:hover { background: #e8f6ea; color: #1c8951; border-color: #1c8951; text-decoration: none; }
    .card-img-top-wrapper .material-symbols-outlined {
      user-select: none;
      -webkit-user-select: none;
      -webkit-touch-callout: none;
      -webkit-tap-highlight-color: transparent;
    }
    /* Filter-Button-Style (gemeinsam für alle Filter) */
    .zufall-kachel.filter-btn {
      display: flex;
      height: 42px;
      align-items: center;
      gap: 0.1em;
      background: #f6f7fa;
      border-radius: 50px;
      border: 1.5px solid #e0e0e0;
      padding: 0.2em 1em;
      width: fit-content;
      box-shadow: 0 2px 10px #815be50a;
      color: #4B15DA !important;
      text-decoration: none !important;
      font-weight: 500;
      font-size: 1.13rem;
      padding: 0 18px;
      transition: background 0.2s, color 0.2s, border-color 0.2s;
      cursor: pointer;
    }
    .zufall-kachel.filter-btn .material-symbols-outlined {
      color: #4B15DA;
      transition: color 0.2s;
    }
    .zufall-kachel.filter-btn.active {
      background: #815BE5 !important;
      color: #fff !important;
      border-color: #4B15DA !important;
    }
    .zufall-kachel.filter-btn.active .material-symbols-outlined {
      color: #fff !important;
    }

    /* Action-Buttons (Reset etc.) */
    .zufall-kachel.action-btn {
      display: flex;
      height: 42px;
      align-items: center;
      gap: 0.1em;
      background: #f6f7fa;
      border-radius: 50px;
      border: 1.5px solid #e0e0e0;
      padding: 0.2em 1em;
      width: fit-content;
      box-shadow: 0 2px 10px #815be50a;
      color: #4B15DA !important;
      text-decoration: none !important;
      font-weight: 500;
      font-size: 1.13rem;
      padding: 0 18px;
      transition: background 0.2s, color 0.2s, border-color 0.2s;
      cursor: pointer;
    }
    .zufall-kachel.action-btn .material-symbols-outlined {
      color: #4B15DA;
      transition: color 0.2s;
    }
    .zufall-kachel.action-btn:hover,
    .zufall-kachel.action-btn:focus {
      background: #EDE7FB !important;
      color: #815BE5 !important;
      border-color: #4B15DA !important;
      text-decoration: none !important;
    }
    .zufall-kachel.action-btn:hover .material-symbols-outlined,
    .zufall-kachel.action-btn:focus .material-symbols-outlined {
      color: #815BE5 !important;
    }

    .rezept-titel {
      color: green;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="sticky-header">
  <div class="container py-2" style="padding-bottom:60px; position:relative;">
    <div class="d-flex align-items-center mb-2">
      <a href="../start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
        <span class="material-symbols-outlined" style="font-size:24px;" >home</span>
      </a>
      <div class="input-group flex-grow-1 mb-0">
        <input type="text" id="suchfeld" class="form-control" placeholder="Suchen...">
        <button class="btn btn-outline-secondary" type="button" onclick="suchfeld.value=''; applyFilters();">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center ">
        <div id="vegFilterIcon" class="zufall-kachel filter-btn me-2" onclick="toggleVegFilter()">
          <span class="material-symbols-outlined">eco</span>
        </div>
        <div id="hauptgerichtFilterIcon" class="zufall-kachel filter-btn me-2" onclick="toggleHauptgerichtFilter()">
          <span class="material-symbols-outlined">stockpot</span>
        </div>

        <!-- Random (Toggle) – nur Würfel, keine automatische Auswahl -->
        <div id="randomOrderBtn" class="zufall-kachel filter-btn me-2" onclick="toggleRandomOrder()">
          <span class="material-symbols-outlined">casino</span>
        </div>

        <!-- Häufigkeits-Sortierung -->
        <div id="frequenzOrderBtn" class="zufall-kachel filter-btn me-2" onclick="toggleFrequenzOrder()">
          <span class="material-symbols-outlined">bar_chart</span>
        </div>
      </div>

      <div>
        <div id="resetBtnHeader" class="zufall-kachel action-btn me-2" onclick="resetAuswahlUndFilter(); return false;">
          <span class="material-symbols-outlined">restart_alt</span>
        </div>
      </div>
    </div>

    <div id="auswahlListe" class="auswahl-liste"></div>
  </div>
</div>

<div class="container py-4">
  <h3 class="mb-4"><span id="rezeptCounter">0</span> köstliche Rezepte</h3>
  <div id="rezepteContainer" class="row g-4"></div>
</div>

<?php include '../assets/rezept-modal.html'; ?>

<div class="sticky-footer d-flex justify-content-center align-items-center">
  <a href="02_pruefen.php"
     class="zufall-kachel action-btn me-2"
     id="uebernehmenBtn"
     style="max-width:340px;">
    <span id="anzahlRezepte" class="fw-bold me-2">0</span>
    übernehmen
  </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/rezept-modal.js"></script>
<script>
  // Rezeptauswahl NUR im LocalStorage zwischenspeichern!
  const STORAGE_KEY = 'wochenrezepte';

  // Rezepte laden (nur Basisdaten, für Modal werden Details nachgeladen)
  const rezepte = <?php
    $rezepte = [];
    foreach (glob('../rezeptkasten/rezepte/*.json') as $datei) {
      $slug = pathinfo($datei, PATHINFO_FILENAME);
      $json = json_decode(file_get_contents($datei), true);
      if (empty($json['name'])) continue;
      $bild = file_exists("../rezeptkasten/bilder/{$slug}.webp") ? "../rezeptkasten/bilder/{$slug}.webp" : null;
      $rezepte[] = [
        'slug' => $slug,
        'titel' => $json['name'],
        'bild' => $bild,
        'vegetarisch' => $json['vegetarisch'],
        'hauptgericht' => isset($json['hauptgericht']) ? $json['hauptgericht'] : null,
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

  let selectedSlugs = [];
  let recipeCards = [];
  rezepte.forEach(r => recipeCards.push({...r, visible:true}));

  // --- NEU: Random-Order Toggle State ---
  let isRandomOrder = false;
  let prevOrderSlugs = null;      // Reihenfolge VOR Random (nur sichtbare)
  let randomOrderSlugs = null;    // aktuelle Random-Reihenfolge (nur sichtbare)

  // --- Frequenz-Order Toggle State ---
  let isFrequenzOrder = false;
  let frequenzOrderSlugs = null;

  // Statistik-Daten (serverseitig geladen)
  const statsData = <?php echo json_encode($statsRaw, JSON_UNESCAPED_UNICODE); ?>;
  // Lowercase-Lookup-Map für schnellen Zugriff in getFrequenz()
  const statsDataLower = Object.fromEntries(
    Object.entries(statsData).map(([k, v]) => [k.toLowerCase(), v])
  );

  function shuffleInPlace(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
  }

  function getCurrentVisibleOrderSlugsDefault() {
    // Default-Anzeige-Reihenfolge (wie bisher): alphabetisch nach Titel
    const visible = recipeCards.filter(r => r.visible);
    visible.sort((a,b) => strnatcmp(a.titel, b.titel));
    return visible.map(r => r.slug);
  }

  function strnatcmp(a, b) {
    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
  }

  function getFrequenz(titel) {
    return statsDataLower[titel.toLowerCase()] || 0;
  }

  function toggleFrequenzOrder() {
    const btn = document.getElementById('frequenzOrderBtn');
    isFrequenzOrder = !isFrequenzOrder;

    if (isFrequenzOrder) {
      // Random-Order deaktivieren (nur einer der beiden Modi aktiv)
      if (isRandomOrder) {
        isRandomOrder = false;
        prevOrderSlugs = null;
        randomOrderSlugs = null;
        document.getElementById('randomOrderBtn').classList.remove('active');
      }
      btn.classList.add('active');
      renderRecipes();
    } else {
      btn.classList.remove('active');
      frequenzOrderSlugs = null;
      renderRecipes();
    }
  }

  function toggleRandomOrder() {
    const btn = document.getElementById('randomOrderBtn');
    isRandomOrder = !isRandomOrder;

    if (isRandomOrder) {
      btn.classList.add('active');

      // Frequenz-Order deaktivieren (nur einer der beiden Modi aktiv)
      if (isFrequenzOrder) {
        isFrequenzOrder = false;
        frequenzOrderSlugs = null;
        document.getElementById('frequenzOrderBtn').classList.remove('active');
      }

      // aktuelle Reihenfolge merken (nur sichtbare)
      prevOrderSlugs = getCurrentVisibleOrderSlugsDefault();

      // Random-Reihenfolge erzeugen
      randomOrderSlugs = [...prevOrderSlugs];
      shuffleInPlace(randomOrderSlugs);

      renderRecipes();
    } else {
      btn.classList.remove('active');
      // zurück zur vorherigen Reihenfolge (Default-Reihenfolge); Filters bleiben aktiv
      prevOrderSlugs = null;
      randomOrderSlugs = null;
      renderRecipes();
    }
  }

  // Lade rezeptwahl aus localStorage zu Beginn
  function ladeRezeptwahl() {
    selectedSlugs = [];
    speichereRezeptwahl();
    renderRecipes();
  }

  function showLoading() {
    const el = document.getElementById('loadingIndicator');
    if (el) el.style.display = 'flex';
  }
  function hideLoading() {
    const el = document.getElementById('loadingIndicator');
    if (el) el.style.display = 'none';
  }

  function toggleVegFilter() {
    const btn = document.getElementById('vegFilterIcon');
    btn.classList.toggle('active');
    handleResponsiveFilterState();
    applyFilters();
  }
  function toggleHauptgerichtFilter() {
    const btn = document.getElementById('hauptgerichtFilterIcon');
    btn.classList.toggle('active');
    handleResponsiveFilterState();
    applyFilters();
  }

  // Reset für Auswahl UND Filter
  function resetAuswahlUndFilter() {
    selectedSlugs = [];
    speichereRezeptwahl();

    // Filter zurücksetzen
    document.getElementById('vegFilterIcon').classList.remove('active');
    document.getElementById('hauptgerichtFilterIcon').classList.remove('active');
    handleResponsiveFilterState(true);

    // Random-Toggle zurücksetzen
    isRandomOrder = false;
    prevOrderSlugs = null;
    randomOrderSlugs = null;
    document.getElementById('randomOrderBtn').classList.remove('active');

    // Frequenz-Toggle zurücksetzen
    isFrequenzOrder = false;
    frequenzOrderSlugs = null;
    document.getElementById('frequenzOrderBtn').classList.remove('active');

    applyFilters();
  }

  // Filter-Button-State für Mobilgeräte zurücksetzen
  function handleResponsiveFilterState(forceReset = false) {
    if (window.innerWidth <= 600) { // Mobilgerät
      const vegBtn = document.getElementById('vegFilterIcon');
      const hauptBtn = document.getElementById('hauptgerichtFilterIcon');
      if (
        forceReset ||
        (!vegBtn.classList.contains('active') && !hauptBtn.classList.contains('active'))
      ) {
        vegBtn.classList.remove('active');
        hauptBtn.classList.remove('active');
      }
    }
  }

  function renderAuswahlListe() {
    const auswahlDiv = document.getElementById('auswahlListe');
    auswahlDiv.innerHTML = '';
    if (selectedSlugs.length === 0) return;

    selectedSlugs.forEach(slug => {
      const card = recipeCards.find(r => r.slug === slug);
      if (card) {
        const badge = document.createElement('div');
        badge.className = 'auswahl-badge';
        if (card.vegetarisch === 'ja') {
          badge.innerHTML = '<span class="material-symbols-outlined" style="font-size:17px;vertical-align:-3px;margin-right:3px;">eco</span>';
        }
        badge.innerHTML += card.titel;
        badge.onclick = function() {
          showPreviewModal(card.slug, card.bild);
        };
        auswahlDiv.appendChild(badge);
      }
    });
  }

  function renderRecipes() {
    container.innerHTML = '';

    // Zählung: nur sichtbare
    const visibleCount = recipeCards.reduce((acc, r) => acc + (r.visible ? 1 : 0), 0);
    document.getElementById('rezeptCounter').textContent = visibleCount;

    if (isFrequenzOrder) {
      const visible = recipeCards.filter(r => r.visible);
      const maxFreq = visible.reduce((m, r) => Math.max(m, getFrequenz(r.titel)), 0);

      // 5 gleichmäßige Stufen von maxFreq → 0
      const gruppen = [];
      if (maxFreq === 0) {
        gruppen.push({ min: 0, max: 0, label: '0' });
      } else {
        for (let i = 0; i < 5; i++) {
          const upperBound = Math.round(maxFreq - i * (maxFreq / 5));
          const lowerBound = i === 4 ? 0 : Math.round(maxFreq - (i + 1) * (maxFreq / 5)) + 1;
          const label = lowerBound === 0 && upperBound === 0
            ? '0'
            : lowerBound === upperBound
              ? `${lowerBound}`
              : `${lowerBound}–${upperBound}`;
          gruppen.push({ min: lowerBound, max: upperBound, label });
        }
      }

      gruppen.forEach(gruppe => {
        const inGruppe = visible.filter(r => {
          const freq = getFrequenz(r.titel);
          return freq >= gruppe.min && freq <= gruppe.max;
        });

        if (inGruppe.length === 0) return;

        // Cluster-Header (gleicher Style wie A–Z: .col-12.buchstabe)
        const header = document.createElement('div');
        header.className = 'col-12 buchstabe';
        header.innerHTML = `<span class="material-symbols-outlined" style="font-size:1.2rem;vertical-align:-3px;margin-right:4px;">bar_chart</span>${gruppe.label}×`;
        container.appendChild(header);

        // Rezepte innerhalb der Gruppe alphabetisch sortieren
        inGruppe.sort((a, b) => strnatcmp(a.titel, b.titel));

        inGruppe.forEach(r => {
          const col = document.createElement('div'); col.className = 'col-md-4 col-sm-6';
          const card = document.createElement('div');
          card.className = 'card shadow-sm p-2 d-flex flex-row align-items-center';
          if (selectedSlugs.includes(r.slug)) card.classList.add('selected-card');

          const imgWrapper = document.createElement('div');
          imgWrapper.className = 'flex-shrink-0 card-img-top-wrapper';
          imgWrapper.innerHTML = r.bild
            ? `<img src="${r.bild}" class="card-img-top">`
            : `<span class="material-symbols-outlined" style="font-size:48px;color:#815BE5;">restaurant</span>`;
          if (r.vegetarisch === 'ja') imgWrapper.insertAdjacentHTML('beforeend', '<span class="material-symbols-outlined vegan-icon">eco</span>');

          imgWrapper.onclick = function(e) {
            e.stopPropagation();
            showPreviewModal(r.slug, r.bild);
          };

          const content = document.createElement('div'); content.className = 'kachel-content ms-3';
          const titleC = document.createElement('div'); titleC.className = 'kachel-title-container';
          const title = document.createElement('h5'); title.className = 'card-title'; title.textContent = r.titel;
          titleC.appendChild(title);

          const btnStack = document.createElement('div');
          btnStack.className = 'kachel-btn-stack';

          content.append(titleC, btnStack);
          card.append(imgWrapper, content);

          card.onclick = function(e) {
            if (e.target.closest('.btn')) return;
            toggleAuswahl(r.slug);
          };

          col.appendChild(card);
          container.appendChild(col);
        });
      });

      renderAuswahlListe();
      document.getElementById('anzahlRezepte').textContent = selectedSlugs.length;
      return;
    }

    if (isRandomOrder && Array.isArray(randomOrderSlugs)) {
      // Random-Ansicht: flach (ohne Buchstaben-Cluster), in randomOrderSlugs
      randomOrderSlugs.forEach(slug => {
        const r = recipeCards.find(x => x.slug === slug);
        if (!r || !r.visible) return;

        const col = document.createElement('div'); col.className='col-md-4 col-sm-6';
        const card = document.createElement('div');
        card.className = 'card shadow-sm p-2 d-flex flex-row align-items-center';
        if (selectedSlugs.includes(r.slug)) card.classList.add('selected-card');

        const imgWrapper = document.createElement('div');
        imgWrapper.className='flex-shrink-0 card-img-top-wrapper';
        imgWrapper.innerHTML = r.bild
          ? `<img src="${r.bild}" class="card-img-top">`
          : `<span class="material-symbols-outlined" style="font-size:48px;color:#815BE5;">restaurant</span>`;
        if (r.vegetarisch==='ja') imgWrapper.insertAdjacentHTML('beforeend','<span class="material-symbols-outlined vegan-icon">eco</span>');

        imgWrapper.onclick = function(e) {
          e.stopPropagation();
          showPreviewModal(r.slug, r.bild);
        };

        const content = document.createElement('div'); content.className='kachel-content ms-3';
        const titleC=document.createElement('div'); titleC.className='kachel-title-container';
        const title=document.createElement('h5'); title.className='card-title'; title.textContent=r.titel;
        titleC.appendChild(title);

        const btnStack = document.createElement('div');
        btnStack.className = 'kachel-btn-stack';

        content.append(titleC, btnStack);
        card.append(imgWrapper, content);

        card.onclick = function(e) {
          if (e.target.closest('.btn')) return;
          toggleAuswahl(r.slug);
        };

        col.appendChild(card);
        container.appendChild(col);
      });

      renderAuswahlListe();
      document.getElementById('anzahlRezepte').textContent = selectedSlugs.length;
      return;
    }

    // Default-Ansicht: wie bisher alphabetisch gruppiert nach Buchstabe
    const grouped = {};
    recipeCards.forEach(r => {
      const buchstabe = r.titel.charAt(0).toUpperCase();
      if (!grouped[buchstabe]) grouped[buchstabe] = [];
      grouped[buchstabe].push(r);
    });

    for (const buch of Object.keys(grouped).sort()) {
      const visibleInGroup = grouped[buch].some(r => r.visible);
      if (!visibleInGroup) continue;

      const header = document.createElement('div');
      header.className='col-12 buchstabe';
      header.textContent=buch;
      container.appendChild(header);

      grouped[buch].forEach(r => {
        if (!r.visible) return;

        const col = document.createElement('div'); col.className='col-md-4 col-sm-6';
        const card = document.createElement('div');
        card.className = 'card shadow-sm p-2 d-flex flex-row align-items-center';
        if (selectedSlugs.includes(r.slug)) card.classList.add('selected-card');

        const imgWrapper = document.createElement('div');
        imgWrapper.className='flex-shrink-0 card-img-top-wrapper';
        imgWrapper.innerHTML = r.bild
          ? `<img src="${r.bild}" class="card-img-top">`
          : `<span class="material-symbols-outlined" style="font-size:48px;color:#815BE5;">restaurant</span>`;
        if (r.vegetarisch==='ja') imgWrapper.insertAdjacentHTML('beforeend','<span class="material-symbols-outlined vegan-icon">eco</span>');

        imgWrapper.onclick = function(e) {
          e.stopPropagation();
          showPreviewModal(r.slug, r.bild);
        };

        const content = document.createElement('div'); content.className='kachel-content ms-3';
        const titleC=document.createElement('div'); titleC.className='kachel-title-container';
        const title=document.createElement('h5'); title.className='card-title'; title.textContent=r.titel;
        titleC.appendChild(title);

        const btnStack = document.createElement('div');
        btnStack.className = 'kachel-btn-stack';

        content.append(titleC, btnStack);
        card.append(imgWrapper, content);

        card.onclick = function(e) {
          if (e.target.closest('.btn')) return;
          toggleAuswahl(r.slug);
        };

        col.appendChild(card);
        container.appendChild(col);
      });
    }

    renderAuswahlListe();
    document.getElementById('anzahlRezepte').textContent = selectedSlugs.length;
  }

  ladeRezeptwahl();

  function applyFilters() {
    const vegOn = document.getElementById('vegFilterIcon').classList.contains('active');
    const hauptgerichtOn = document.getElementById('hauptgerichtFilterIcon').classList.contains('active');

    recipeCards.forEach(r => {
      r.visible = true;
      if (vegOn && r.vegetarisch !== 'ja') r.visible = false;
      if (hauptgerichtOn && r.hauptgericht === 'nein') r.visible = false;
    });

    // Wenn Random aktiv ist, neu auf die aktuell sichtbaren Rezepte anwenden
    if (isRandomOrder) {
      const nowVisible = recipeCards.filter(r => r.visible).map(r => r.slug);

      // Wenn wir noch keine randomOrder haben: initialisieren
      if (!Array.isArray(randomOrderSlugs)) {
        randomOrderSlugs = [...nowVisible];
        shuffleInPlace(randomOrderSlugs);
      } else {
        // bestehende Reihenfolge beibehalten, nur unsichtbare entfernen und neue anhängen
        const setNow = new Set(nowVisible);
        randomOrderSlugs = randomOrderSlugs.filter(s => setNow.has(s));
        nowVisible.forEach(s => {
          if (!randomOrderSlugs.includes(s)) randomOrderSlugs.push(s);
        });
      }
    }

    renderRecipes();
  }

  function toggleAuswahl(slug) {
    if (selectedSlugs.includes(slug)) {
      selectedSlugs = selectedSlugs.filter(s => s !== slug);
    } else {
      selectedSlugs.push(slug);
    }
    renderRecipes();
    speichereRezeptwahl();
  }

  function speichereRezeptwahl() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(selectedSlugs));
  }

  const suchfeld = document.getElementById('suchfeld');
  suchfeld.addEventListener('input', () => {
    const term = suchfeld.value.trim().toLowerCase();
    recipeCards.forEach(r => r.visible = r.titel.toLowerCase().includes(term));

    if (isRandomOrder) {
      const nowVisible = recipeCards.filter(r => r.visible).map(r => r.slug);
      const setNow = new Set(nowVisible);
      if (!Array.isArray(randomOrderSlugs)) {
        randomOrderSlugs = [...nowVisible];
        shuffleInPlace(randomOrderSlugs);
      } else {
        randomOrderSlugs = randomOrderSlugs.filter(s => setNow.has(s));
        nowVisible.forEach(s => {
          if (!randomOrderSlugs.includes(s)) randomOrderSlugs.push(s);
        });
      }
    }

    renderRecipes();
  });

  // Änderung: Warn-Dialog beim Klick auf den Home-Button
  if (document.getElementById('homeBtn')) {
    document.getElementById('homeBtn').addEventListener('click', function(e) {
      e.preventDefault();
      if (confirm('Achtung: Alle Änderungen gehen verloren. Wirklich zurück zur Startseite?')) {
        window.location.href = this.href;
      }
    });
  }
  // Änderung: Warn-Dialog beim Klick auf den Home-Button im Footer
  if (document.getElementById('footerHomeBtn')) {
    document.getElementById('footerHomeBtn').addEventListener('click', function(e) {
      e.preventDefault();
      if (confirm('Achtung: Alle Änderungen gehen verloren. Wirklich zurück zur Startseite?')) {
        window.location.href = this.href;
      }
    });
  }

  // Änderung: Bei Klick auf Übernehmen LocalStorage korrekt befüllen (Navigation nach 02_pruefen.php)
  document.getElementById('uebernehmenBtn').addEventListener('click', function(e) {
    localStorage.setItem('wochenrezepte', JSON.stringify(selectedSlugs));
  });
</script>
</body>
</html>