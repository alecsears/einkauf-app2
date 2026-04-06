<?php
$heute = date('d.m.Y');

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="../assets/tokens.css">
  <link href="/einkauf-app/assets/rezept-modal.css" rel="stylesheet">
  <style>
    /* ── Drei-Frame-Layout ─────────────────────────────────── */
    html, body {
      height: 100%;
      margin: 0;
      overflow: hidden;
      background-color: var(--color-bg);
      font-family: var(--font-family-base);
    }
    body {
      display: flex;
      flex-direction: column;
      height: 100dvh; /* dynamic viewport height – korrekt auf Mobile */
    }
    #app-header {
      flex-shrink: 0;           /* nimmt exakt so viel Höhe wie nötig */
      background-color: var(--color-surface);
      border-bottom: 1px solid var(--color-border);
      padding: 0.5rem 0 0.5rem 0;
      z-index: 1000;
    }
    #app-content {
      flex: 1 1 0;              /* füllt den verbleibenden Raum */
      overflow-y: auto;         /* einzige scrollbare Zone */
      -webkit-overflow-scrolling: touch;
    }
    #app-footer {
      flex-shrink: 0;           /* niemals scrollbar, immer sichtbar */
      background: var(--color-surface);
      border-top: 1px solid var(--color-border);
      padding: 0.75rem 1.5rem;
      box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1040;
    }

    /* ── Bisherige Styles (unverändert) ────────────────────── */
    .card-img-top-wrapper {
      border-radius: 10px; position: relative; height: 100px; width: 100px;
      cursor: pointer; background: var(--color-primary);
      display: flex; align-items: center; justify-content: center;
      transition: box-shadow 0.2s;
    }
    .card-img-top {
      border-radius: 10px; width: 100%; height: 100%;
      object-fit: cover; display: block; transition: filter 0.2s;
    }
    .material-symbols-outlined { font-size: 24px; vertical-align: middle; }
    .btn-icon { padding: 0.375rem; line-height: 1; width: 38px; height: 38px; }
    .buchstabe {
      font-weight: bold; font-size: 1.5rem; margin-top: 3rem;
      border-bottom: 1px solid var(--color-dot-inactive);
    }
    .card {
      border-radius: 16px; min-height: auto;
      transition: border-color 0.2s, box-shadow 0.2s;
      border: 2px solid var(--color-border);
    }
    .selected-card {
      border: 1px solid var(--color-primary) !important;
      background: var(--color-primary-light) !important;
      box-shadow: 0 0 0 3px var(--color-primary) !important;
    }
    .card-title, .card h5 {
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 100%; display: block; margin-bottom: 0;
    }
    .kachel-title-container {
      min-width: 0; flex: 1 1 0%; margin-right: 1rem; display: flex; align-items: center;
    }
    .kachel-btn-stack {
      display: flex; flex-direction: column; align-items: flex-end;
      justify-content: center; gap: 0.3rem; height: 100%; min-width: 38px;
    }
    .kachel-content {
      display: flex; align-items: center; width: 100%;
      min-width: 0; justify-content: space-between;
    }
    .vegan-icon {
      position: absolute; top: 8px; left: 8px; background-color: #23af64cc;
      border-radius: 50%; padding: 2px; font-size: 25px;
      color: var(--color-surface); z-index: 3;
    }
    .auswahl-liste {
      display: flex; flex-wrap: wrap; gap: 0.2rem;
      margin-bottom: 0.5rem; margin-top: 0.5rem;
    }
    .auswahl-badge {
      background: var(--color-primary); color: var(--color-surface);
      border-radius: 1rem; padding: 0.3em 0.3em; font-size: 1rem;
      display: inline-flex; align-items: center; gap: 0.2em;
    }

    /* Filter-Buttons */
    .zufall-kachel.filter-btn {
      display: flex; height: 42px; align-items: center; gap: 0.1em;
      background: #f6f7fa; border-radius: 50px; border: 1.5px solid #e0e0e0;
      width: fit-content; box-shadow: 0 2px 10px #815be50a;
      color: var(--color-primary) !important; text-decoration: none !important;
      font-weight: 500; font-size: 1.13rem; padding: 0 18px;
      transition: background 0.2s, color 0.2s, border-color 0.2s; cursor: pointer;
    }
    .zufall-kachel.filter-btn .material-symbols-outlined {
      color: var(--color-primary); transition: color 0.2s;
    }
    .zufall-kachel.filter-btn.active {
      background: var(--color-primary-icon) !important;
      color: var(--color-surface) !important;
      border-color: var(--color-primary) !important;
    }
    .zufall-kachel.filter-btn.active .material-symbols-outlined { color: #fff !important; }

    /* Action-Buttons */
    .zufall-kachel.action-btn {
      display: flex; height: 42px; align-items: center; gap: 0.1em;
      background: #f6f7fa; border-radius: 50px; border: 1.5px solid #e0e0e0;
      width: fit-content; box-shadow: 0 2px 10px #815be50a;
      color: var(--color-primary) !important; text-decoration: none !important;
      font-weight: 500; font-size: 1.13rem; padding: 0 18px;
      transition: background 0.2s, color 0.2s, border-color 0.2s; cursor: pointer;
    }
    .zufall-kachel.action-btn .material-symbols-outlined {
      color: var(--color-primary); transition: color 0.2s;
    }
    .zufall-kachel.action-btn:hover,
    .zufall-kachel.action-btn:focus {
      background: var(--color-primary-light) !important;
      color: var(--color-primary-icon) !important;
      border-color: var(--color-primary) !important;
      text-decoration: none !important;
    }
    .zufall-kachel.action-btn:hover .material-symbols-outlined,
    .zufall-kachel.action-btn:focus .material-symbols-outlined {
      color: var(--color-primary-icon) !important;
    }

    .rezept-titel { color: green; font-weight: bold; }

    .card-img-top-wrapper .material-symbols-outlined {
      user-select: none; -webkit-user-select: none;
      -webkit-touch-callout: none; -webkit-tap-highlight-color: transparent;
    }
  </style>
</head>
<body>

<!-- ① HEADER (dynamische Höhe, kein Scroll) -->
<header id="app-header">
  <div class="container py-2">
    <div class="d-flex align-items-center mb-2">
      <a href="../start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
        <span class="material-symbols-outlined" style="font-size:24px;">home</span>
      </a>
      <div class="input-group flex-grow-1 mb-0">
        <input type="text" id="suchfeld" class="form-control" placeholder="Suchen...">
        <button class="btn btn-outline-secondary" type="button"
                onclick="suchfeld.value=''; applyFilters();">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center">
        <div id="vegFilterIcon" class="zufall-kachel filter-btn me-2" onclick="toggleVegFilter()">
          <span class="material-symbols-outlined">eco</span>
        </div>
        <div id="hauptgerichtFilterIcon" class="zufall-kachel filter-btn me-2" onclick="toggleHauptgerichtFilter()">
          <span class="material-symbols-outlined">stockpot</span>
        </div>
        <div id="randomOrderBtn" class="zufall-kachel filter-btn me-2" onclick="toggleRandomOrder()">
          <span class="material-symbols-outlined">casino</span>
        </div>
        <div id="frequenzOrderBtn" class="zufall-kachel filter-btn me-2" onclick="toggleFrequenzOrder()">
          <span class="material-symbols-outlined">bar_chart</span>
        </div>
      </div>
  
    </div>

    <div id="auswahlListe" class="auswahl-liste"></div>
  </div>
</header>

<!-- ② CONTENT (scrollbar) -->
<main id="app-content">
  <div class="container py-4">
    <h3 class="mb-4"><span id="rezeptCounter">0</span> köstliche Rezepte</h3>
    <div id="rezepteContainer" class="row g-4"></div>
  </div>
</main>

<?php include '../assets/rezept-modal.html'; ?>

<!-- ③ FOOTER (niemals scrollbar) -->
<footer id="app-footer">
  <a href="02_pruefen.php"
     class="zufall-kachel action-btn"
     id="uebernehmenBtn"
     style="max-width:340px;">
    <span id="anzahlRezepte" class="fw-bold me-2">0</span>
    übernehmen
  </a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/rezept-modal.js"></script>
<script>
  const STORAGE_KEY = 'wochenrezepte';

  const rezepte = <?php
    $rezepte = [];
    foreach (glob('../rezeptkasten/rezepte/*.json') as $datei) {
      $slug = pathinfo($datei, PATHINFO_FILENAME);
      $json = json_decode(file_get_contents($datei), true);
      if (empty($json['name'])) continue;
      $bild = file_exists("../rezeptkasten/bilder/{$slug}.webp") ? "../rezeptkasten/bilder/{$slug}.webp" : null;
      $rezepte[] = [
        'slug'        => $slug,
        'titel'       => $json['name'],
        'bild'        => $bild,
        'vegetarisch' => $json['vegetarisch'],
        'hauptgericht'=> isset($json['hauptgericht']) ? $json['hauptgericht'] : null,
        'kalorien'    => $json['kalorien'],
        'frequenz'    => $json['frequenz']
      ];
    }
    usort($rezepte, fn($a, $b) => strnatcasecmp($a['titel'], $b['titel']));
    echo json_encode($rezepte);
  ?>;

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
    } catch (e) { window.produktListe = {}; }
  }
  ladeProduktListe();

  const container = document.getElementById('rezepteContainer');
  document.getElementById('rezeptCounter').textContent = rezepte.length;

  let selectedSlugs = [];
  let recipeCards = [];
  rezepte.forEach(r => recipeCards.push({...r, visible: true}));

  let isRandomOrder    = false;
  let prevOrderSlugs   = null;
  let randomOrderSlugs = null;
  let isFrequenzOrder  = false;
  let frequenzOrderSlugs = null;

  const statsData = <?php echo json_encode($statsRaw, JSON_UNESCAPED_UNICODE); ?>;
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
    const visible = recipeCards.filter(r => r.visible);
    visible.sort((a, b) => strnatcmp(a.titel, b.titel));
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
      if (isRandomOrder) {
        isRandomOrder = false; prevOrderSlugs = null; randomOrderSlugs = null;
        document.getElementById('randomOrderBtn').classList.remove('active');
      }
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
      frequenzOrderSlugs = null;
    }
    renderRecipes();
  }

  function toggleRandomOrder() {
    const btn = document.getElementById('randomOrderBtn');
    isRandomOrder = !isRandomOrder;
    if (isRandomOrder) {
      btn.classList.add('active');
      if (isFrequenzOrder) {
        isFrequenzOrder = false; frequenzOrderSlugs = null;
        document.getElementById('frequenzOrderBtn').classList.remove('active');
      }
      prevOrderSlugs   = getCurrentVisibleOrderSlugsDefault();
      randomOrderSlugs = [...prevOrderSlugs];
      shuffleInPlace(randomOrderSlugs);
    } else {
      btn.classList.remove('active');
      prevOrderSlugs = null; randomOrderSlugs = null;
    }
    renderRecipes();
  }

  function ladeRezeptwahl() {
    selectedSlugs = [];
    speichereRezeptwahl();
    renderRecipes();
  }

  function toggleVegFilter() {
    document.getElementById('vegFilterIcon').classList.toggle('active');
    handleResponsiveFilterState();
    applyFilters();
  }
  function toggleHauptgerichtFilter() {
    document.getElementById('hauptgerichtFilterIcon').classList.toggle('active');
    handleResponsiveFilterState();
    applyFilters();
  }

  function resetAuswahlUndFilter() {
    selectedSlugs = [];
    speichereRezeptwahl();
    document.getElementById('vegFilterIcon').classList.remove('active');
    document.getElementById('hauptgerichtFilterIcon').classList.remove('active');
    handleResponsiveFilterState(true);
    isRandomOrder = false; prevOrderSlugs = null; randomOrderSlugs = null;
    document.getElementById('randomOrderBtn').classList.remove('active');
    isFrequenzOrder = false; frequenzOrderSlugs = null;
    document.getElementById('frequenzOrderBtn').classList.remove('active');
    applyFilters();
  }

  function handleResponsiveFilterState(forceReset = false) {
    if (window.innerWidth <= 600) {
      const vegBtn   = document.getElementById('vegFilterIcon');
      const hauptBtn = document.getElementById('hauptgerichtFilterIcon');
      if (forceReset ||
          (!vegBtn.classList.contains('active') && !hauptBtn.classList.contains('active'))) {
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
        badge.onclick = () => showPreviewModal(card.slug, card.bild);
        auswahlDiv.appendChild(badge);
      }
    });
  }

  function buildCard(r) {
    const col  = document.createElement('div'); col.className = 'col-md-4 col-sm-6';
    const card = document.createElement('div');
    card.className = 'card shadow-sm p-2 d-flex flex-row align-items-center';
    if (selectedSlugs.includes(r.slug)) card.classList.add('selected-card');

    const imgWrapper = document.createElement('div');
    imgWrapper.className = 'flex-shrink-0 card-img-top-wrapper';
    imgWrapper.innerHTML = r.bild
      ? `<img src="${r.bild}" class="card-img-top">`
      : `<span class="material-symbols-outlined" style="font-size:48px;color:#815BE5;">restaurant</span>`;
    if (r.vegetarisch === 'ja')
      imgWrapper.insertAdjacentHTML('beforeend',
        '<span class="material-symbols-outlined vegan-icon">eco</span>');
    imgWrapper.onclick = e => { e.stopPropagation(); showPreviewModal(r.slug, r.bild); };

    const content = document.createElement('div'); content.className = 'kachel-content ms-3';
    const titleC  = document.createElement('div'); titleC.className  = 'kachel-title-container';
    const title   = document.createElement('h5');  title.className   = 'card-title';
    title.textContent = r.titel;
    titleC.appendChild(title);
    const btnStack = document.createElement('div'); btnStack.className = 'kachel-btn-stack';
    content.append(titleC, btnStack);
    card.append(imgWrapper, content);
    card.onclick = e => { if (!e.target.closest('.btn')) toggleAuswahl(r.slug); };

    col.appendChild(card);
    return col;
  }

  function renderRecipes() {
    container.innerHTML = '';
    const visibleCount = recipeCards.reduce((acc, r) => acc + (r.visible ? 1 : 0), 0);
    document.getElementById('rezeptCounter').textContent = visibleCount;

    if (isFrequenzOrder) {
      const visible = recipeCards.filter(r => r.visible);
      const maxFreq = visible.reduce((m, r) => Math.max(m, getFrequenz(r.titel)), 0);
      const gruppen = [];
      if (maxFreq === 0) {
        gruppen.push({ min: 0, max: 0, label: '0' });
      } else {
        for (let i = 0; i < 5; i++) {
          const upper = Math.round(maxFreq - i * (maxFreq / 5));
          const lower = i === 4 ? 0 : Math.round(maxFreq - (i + 1) * (maxFreq / 5)) + 1;
          const label = lower === 0 && upper === 0 ? '0'
            : lower === upper ? `${lower}` : `${lower}–${upper}`;
          gruppen.push({ min: lower, max: upper, label });
        }
      }
      gruppen.forEach(gruppe => {
        const inGruppe = visible
          .filter(r => { const f = getFrequenz(r.titel); return f >= gruppe.min && f <= gruppe.max; })
          .sort((a, b) => strnatcmp(a.titel, b.titel));
        if (!inGruppe.length) return;
        const header = document.createElement('div');
        header.className = 'col-12 buchstabe';
       header.textContent = `${gruppe.label}×`;
        container.appendChild(header);
        inGruppe.forEach(r => container.appendChild(buildCard(r)));
      });

    } else if (isRandomOrder && Array.isArray(randomOrderSlugs)) {
      randomOrderSlugs.forEach(slug => {
        const r = recipeCards.find(x => x.slug === slug);
        if (r && r.visible) container.appendChild(buildCard(r));
      });

    } else {
      const grouped = {};
      recipeCards.forEach(r => {
        const b = r.titel.charAt(0).toUpperCase();
        if (!grouped[b]) grouped[b] = [];
        grouped[b].push(r);
      });
      for (const buch of Object.keys(grouped).sort()) {
        if (!grouped[buch].some(r => r.visible)) continue;
        const header = document.createElement('div');
        header.className = 'col-12 buchstabe';
        header.textContent = buch;
        container.appendChild(header);
        grouped[buch].forEach(r => { if (r.visible) container.appendChild(buildCard(r)); });
      }
    }

    renderAuswahlListe();
    document.getElementById('anzahlRezepte').textContent = selectedSlugs.length;
  }

  ladeRezeptwahl();

  function applyFilters() {
    const vegOn        = document.getElementById('vegFilterIcon').classList.contains('active');
    const hauptOn      = document.getElementById('hauptgerichtFilterIcon').classList.contains('active');
    const term         = document.getElementById('suchfeld').value.trim().toLowerCase();

    recipeCards.forEach(r => {
      r.visible = true;
      if (vegOn  && r.vegetarisch  !== 'ja')  r.visible = false;
      if (hauptOn && r.hauptgericht === 'nein') r.visible = false;
      if (term   && !r.titel.toLowerCase().includes(term)) r.visible = false;
    });

    if (isRandomOrder) {
      const nowVisible = recipeCards.filter(r => r.visible).map(r => r.slug);
      const setNow = new Set(nowVisible);
      if (!Array.isArray(randomOrderSlugs)) {
        randomOrderSlugs = [...nowVisible]; shuffleInPlace(randomOrderSlugs);
      } else {
        randomOrderSlugs = randomOrderSlugs.filter(s => setNow.has(s));
        nowVisible.forEach(s => { if (!randomOrderSlugs.includes(s)) randomOrderSlugs.push(s); });
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

  // Suchfeld – applyFilters() übernimmt jetzt auch den Term
  document.getElementById('suchfeld').addEventListener('input', applyFilters);

  document.getElementById('uebernehmenBtn').addEventListener('click', function() {
    localStorage.setItem('wochenrezepte', JSON.stringify(selectedSlugs));
  });
</script>
</body>
</html>
