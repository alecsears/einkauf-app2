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
    /* ── Drei-Frame-Layout ─────────────────────────── */
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
      height: 100dvh;
    }
    #app-header {
      flex-shrink: 0;
      background-color: var(--color-surface);
      border-bottom: 1px solid var(--color-border);
      z-index: 1000;
    }
    #app-content {
      flex: 1 1 0;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }
    #app-footer {
      flex-shrink: 0;
      background: var(--color-surface);
      border-top: 1px solid var(--color-border);
      box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
      z-index: 1040;
    }

    /* ── Komponenten ───────────────────────────────── */
    .card-img-top-wrapper {
      border-radius: 10px;
      position: relative;
      height: 100px;
      width: 100px;
      flex-shrink: 0;
      cursor: pointer;
      background: var(--color-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: box-shadow 0.2s;
    }
    .card-img-top {
      border-radius: 10px;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .material-symbols-outlined { font-size: 24px; vertical-align: middle; }

    .buchstabe {
      font-weight: bold;
      font-size: 1.5rem;
      margin-top: 2rem;
      border-bottom: 1px solid var(--color-dot-inactive);
    }
    .card {
      border-radius: 16px;
      transition: border-color 0.2s, box-shadow 0.2s;
      border: 2px solid var(--color-border);
      height: 100%;
    }
    .selected-card {
      border: 1px solid var(--color-primary) !important;
      background: var(--color-primary-light) !important;
      box-shadow: 0 0 0 3px var(--color-primary) !important;
    }
    .card-title {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 0;
    }
    .vegan-icon {
      position: absolute;
      top: 8px;
      left: 8px;
      background-color: #23af64cc;
      border-radius: 50%;
      padding: 2px;
      font-size: 25px;
      color: var(--color-surface);
      z-index: 3;
    }
    .auswahl-badge {
      background: var(--color-primary);
      color: var(--color-surface);
      border-radius: 1rem;
      padding: 0.3em 0.6em;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      gap: 0.2em;
      cursor: pointer;
    }

    /* ── Filter / Action Buttons ───────────────────── */
    .pill-btn {
      display: inline-flex;
      align-items: center;
      height: 42px;
      padding: 0 16px;
      border-radius: 50px;
      border: 1.5px solid #e0e0e0;
      background: #f6f7fa;
      color: var(--color-primary);
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s, color 0.2s, border-color 0.2s;
      text-decoration: none;
      white-space: nowrap;
      box-shadow: 0 2px 10px #815be50a;
      gap: 4px;
    }
    .pill-btn .material-symbols-outlined {
      color: var(--color-primary);
      transition: color 0.2s;
    }
    .pill-btn.active {
      background: var(--color-primary-icon);
      color: #fff;
      border-color: var(--color-primary);
    }
    .pill-btn.active .material-symbols-outlined { color: #fff; }
    .pill-btn:hover:not(.active),
    .pill-btn:focus:not(.active) {
      background: var(--color-primary-light);
      border-color: var(--color-primary);
      color: var(--color-primary-icon);
      text-decoration: none;
    }

    .card-img-top-wrapper .material-symbols-outlined {
      user-select: none;
      -webkit-user-select: none;
      -webkit-touch-callout: none;
      -webkit-tap-highlight-color: transparent;
    }
  </style>
</head>
<body>

<!-- ① HEADER -->
<header id="app-header">
  <div class="container py-2">

    <!-- Zeile 1: Home + Suche -->
    <div class="row g-2 align-items-center mb-2">
      <div class="col-auto">
        <a href="../start.php" class="btn btn-outline-dark" title="Zur Startseite">
          <span class="material-symbols-outlined">home</span>
        </a>
      </div>
      <div class="col">
        <div class="input-group">
          <input type="text" id="suchfeld" class="form-control" placeholder="Suchen…">
          <button class="btn btn-outline-secondary" type="button"
                  onclick="document.getElementById('suchfeld').value=''; applyFilters();">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Zeile 2: Filter-Buttons -->
    <div class="row g-2 align-items-center mb-2">
      <div class="col">
        <div class="d-flex flex-wrap gap-2">
          <div id="vegFilterIcon"         class="pill-btn" onclick="toggleVegFilter()">
            <span class="material-symbols-outlined">eco</span>
          </div>
          <div id="hauptgerichtFilterIcon" class="pill-btn" onclick="toggleHauptgerichtFilter()">
            <span class="material-symbols-outlined">stockpot</span>
          </div>
          <div id="randomOrderBtn"         class="pill-btn" onclick="toggleRandomOrder()">
            <span class="material-symbols-outlined">casino</span>
          </div>
          <div id="frequenzOrderBtn"       class="pill-btn" onclick="toggleFrequenzOrder()">
            <span class="material-symbols-outlined">bar_chart</span>
          </div>
        </div>
      </div>
      
    </div>

    <!-- Zeile 3: Auswahl-Badges -->
    <div class="row">
      <div class="col">
        <div id="auswahlListe" class="d-flex flex-wrap gap-1"></div>
      </div>
    </div>

  </div>
</header>

<!-- ② CONTENT -->
<main id="app-content">
  <div class="container py-3">
    <div class="row mb-3">
      <div class="col">
        <h3 class="mb-0"><span id="rezeptCounter">0</span> köstliche Rezepte</h3>
      </div>
    </div>
    <div id="rezepteContainer" class="row g-3"></div>
  </div>
</main>

<?php include '../assets/rezept-modal.html'; ?>

<!-- ③ FOOTER -->
<footer id="app-footer">
  <div class="container py-2">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-8 col-md-6 col-lg-4 d-grid">
        <a href="02_pruefen.php" class="pill-btn justify-content-center" id="uebernehmenBtn">
          <span id="anzahlRezepte" class="fw-bold me-1">0</span>
          übernehmen
        </a>
      </div>
    </div>
  </div>
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
      $bild = file_exists("../rezeptkasten/bilder/{$slug}.webp")
        ? "../rezeptkasten/bilder/{$slug}.webp" : null;
      $rezepte[] = [
        'slug'         => $slug,
        'titel'        => $json['name'],
        'bild'         => $bild,
        'vegetarisch'  => $json['vegetarisch'],
        'hauptgericht' => $json['hauptgericht'] ?? null,
        'kalorien'     => $json['kalorien'],
        'frequenz'     => $json['frequenz'],
      ];
    }
    usort($rezepte, fn($a,$b) => strnatcasecmp($a['titel'], $b['titel']));
    echo json_encode($rezepte);
  ?>;

  window.produktListe = {};
  async function ladeProduktListe() {
    try {
      const res = await fetch("../produkte/produktliste.json");
      if (res.ok) {
        const obj = await res.json();
        Object.entries(obj).forEach(([name, data]) => {
          if (data.id !== undefined)
            window.produktListe[data.id] = { ...data, _name: name };
        });
      }
    } catch(e) { window.produktListe = {}; }
  }
  ladeProduktListe();

  const container = document.getElementById('rezepteContainer');

  let selectedSlugs    = [];
  let recipeCards      = rezepte.map(r => ({ ...r, visible: true }));
  let isRandomOrder    = false;
  let prevOrderSlugs   = null;
  let randomOrderSlugs = null;
  let isFrequenzOrder  = false;

  const statsData = <?php echo json_encode($statsRaw, JSON_UNESCAPED_UNICODE); ?>;
  const statsDataLower = Object.fromEntries(
    Object.entries(statsData).map(([k,v]) => [k.toLowerCase(), v])
  );

  /* ── Hilfsfunktionen ─────────────────────────────── */
  function shuffleInPlace(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
  }
  function strnatcmp(a, b) {
    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
  }
  function getFrequenz(titel) {
    return statsDataLower[titel.toLowerCase()] || 0;
  }
  function visibleSlugsDefault() {
    return recipeCards.filter(r => r.visible)
      .sort((a,b) => strnatcmp(a.titel, b.titel))
      .map(r => r.slug);
  }

  /* ── Toggle-Funktionen ───────────────────────────── */
  function toggleVegFilter() {
    document.getElementById('vegFilterIcon').classList.toggle('active');
    applyFilters();
  }
  function toggleHauptgerichtFilter() {
    document.getElementById('hauptgerichtFilterIcon').classList.toggle('active');
    applyFilters();
  }
  function toggleRandomOrder() {
    isRandomOrder = !isRandomOrder;
    document.getElementById('randomOrderBtn').classList.toggle('active', isRandomOrder);
    if (isRandomOrder) {
      if (isFrequenzOrder) {
        isFrequenzOrder = false;
        document.getElementById('frequenzOrderBtn').classList.remove('active');
      }
      randomOrderSlugs = visibleSlugsDefault();
      shuffleInPlace(randomOrderSlugs);
    } else {
      randomOrderSlugs = null;
    }
    renderRecipes();
  }
  function toggleFrequenzOrder() {
    isFrequenzOrder = !isFrequenzOrder;
    document.getElementById('frequenzOrderBtn').classList.toggle('active', isFrequenzOrder);
    if (isFrequenzOrder && isRandomOrder) {
      isRandomOrder = false;
      randomOrderSlugs = null;
      document.getElementById('randomOrderBtn').classList.remove('active');
    }
    renderRecipes();
  }
  function resetAuswahlUndFilter() {
    selectedSlugs = [];
    speichereRezeptwahl();
    ['vegFilterIcon','hauptgerichtFilterIcon','randomOrderBtn','frequenzOrderBtn']
      .forEach(id => document.getElementById(id).classList.remove('active'));
    isRandomOrder = false; randomOrderSlugs = null;
    isFrequenzOrder = false;
    document.getElementById('suchfeld').value = '';
    applyFilters();
  }

  /* ── Karte bauen ──────────────────────────────────── */
  function buildCard(r) {
    // Bootstrap col
    const col = document.createElement('div');
    col.className = 'col-12 col-sm-6 col-lg-4';

    const card = document.createElement('div');
    card.className = 'card shadow-sm p-2';
    if (selectedSlugs.includes(r.slug)) card.classList.add('selected-card');
    card.onclick = e => { if (!e.target.closest('.btn')) toggleAuswahl(r.slug); };

    // Inneres Grid: Bild | Titel
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-3';

    // Bild-Spalte
    const imgWrapper = document.createElement('div');
    imgWrapper.className = 'card-img-top-wrapper';
    imgWrapper.innerHTML = r.bild
      ? `<img src="${r.bild}" class="card-img-top" alt="${r.titel}">`
      : `<span class="material-symbols-outlined" style="font-size:48px;color:#815BE5;">restaurant</span>`;
    if (r.vegetarisch === 'ja')
      imgWrapper.insertAdjacentHTML('beforeend',
        '<span class="material-symbols-outlined vegan-icon">eco</span>');
    imgWrapper.onclick = e => { e.stopPropagation(); showPreviewModal(r.slug, r.bild); };

    // Titel-Spalte (flex-grow)
    const titleCol = document.createElement('div');
    titleCol.className = 'flex-grow-1 min-w-0';   // min-w-0 erlaubt text-overflow
    titleCol.style.minWidth = '0';                 // BS setzt min-width nicht automatisch

    const title = document.createElement('h6');
    title.className = 'card-title mb-0';
    title.textContent = r.titel;
    titleCol.appendChild(title);

    row.append(imgWrapper, titleCol);
    card.appendChild(row);
    col.appendChild(card);
    return col;
  }

  /* ── Render ───────────────────────────────────────── */
  function renderAuswahlListe() {
    const div = document.getElementById('auswahlListe');
    div.innerHTML = '';
    selectedSlugs.forEach(slug => {
      const r = recipeCards.find(x => x.slug === slug);
      if (!r) return;
      const badge = document.createElement('span');
      badge.className = 'auswahl-badge';
      badge.innerHTML = r.vegetarisch === 'ja'
        ? '<span class="material-symbols-outlined" style="font-size:15px;">eco</span>' : '';
      badge.innerHTML += r.titel;
      badge.onclick = () => showPreviewModal(r.slug, r.bild);
      div.appendChild(badge);
    });
  }

  function sectionHeader(text) {
    const div = document.createElement('div');
    div.className = 'col-12 buchstabe';
    div.textContent = text;
    return div;
  }

  function renderRecipes() {
    container.innerHTML = '';
    const visible = recipeCards.filter(r => r.visible);
    document.getElementById('rezeptCounter').textContent = visible.length;

    if (isFrequenzOrder) {
      const maxFreq = visible.reduce((m,r) => Math.max(m, getFrequenz(r.titel)), 0);
      const gruppen = maxFreq === 0
        ? [{ min:0, max:0, label:'0' }]
        : Array.from({ length:5 }, (_,i) => {
            const upper = Math.round(maxFreq - i * (maxFreq / 5));
            const lower = i === 4 ? 0 : Math.round(maxFreq - (i+1) * (maxFreq / 5)) + 1;
            return {
              min: lower, max: upper,
              label: lower === upper ? `${lower}` : `${lower}–${upper}`
            };
          });

      gruppen.forEach(g => {
        const inG = visible
          .filter(r => { const f=getFrequenz(r.titel); return f>=g.min && f<=g.max; })
          .sort((a,b) => strnatcmp(a.titel, b.titel));
        if (!inG.length) return;
        container.appendChild(sectionHeader(`${g.label}×`));
        inG.forEach(r => container.appendChild(buildCard(r)));
      });

    } else if (isRandomOrder && Array.isArray(randomOrderSlugs)) {
      randomOrderSlugs.forEach(slug => {
        const r = recipeCards.find(x => x.slug === slug);
        if (r && r.visible) container.appendChild(buildCard(r));
      });

    } else {
      const grouped = {};
      visible.forEach(r => {
        const b = r.titel.charAt(0).toUpperCase();
        (grouped[b] = grouped[b] || []).push(r);
      });
      Object.keys(grouped).sort().forEach(buch => {
        container.appendChild(sectionHeader(buch));
        grouped[buch].forEach(r => container.appendChild(buildCard(r)));
      });
    }

    renderAuswahlListe();
    document.getElementById('anzahlRezepte').textContent = selectedSlugs.length;
  }

  /* ── Filter anwenden ──────────────────────────────── */
  function applyFilters() {
    const vegOn   = document.getElementById('vegFilterIcon').classList.contains('active');
    const hauptOn = document.getElementById('hauptgerichtFilterIcon').classList.contains('active');
    const term    = document.getElementById('suchfeld').value.trim().toLowerCase();

    recipeCards.forEach(r => {
      r.visible = true;
      if (vegOn   && r.vegetarisch   !== 'ja')   r.visible = false;
      if (hauptOn && r.hauptgericht  === 'nein') r.visible = false;
      if (term    && !r.titel.toLowerCase().includes(term)) r.visible = false;
    });

    if (isRandomOrder) {
      const nowVisible = new Set(recipeCards.filter(r=>r.visible).map(r=>r.slug));
      if (!Array.isArray(randomOrderSlugs)) {
        randomOrderSlugs = [...nowVisible]; shuffleInPlace(randomOrderSlugs);
      } else {
        randomOrderSlugs = randomOrderSlugs.filter(s => nowVisible.has(s));
        [...nowVisible].forEach(s => {
          if (!randomOrderSlugs.includes(s)) randomOrderSlugs.push(s);
        });
      }
    }
    renderRecipes();
  }

  /* ── Auswahl ──────────────────────────────────────── */
  function toggleAuswahl(slug) {
    selectedSlugs = selectedSlugs.includes(slug)
      ? selectedSlugs.filter(s => s !== slug)
      : [...selectedSlugs, slug];
    renderRecipes();
    speichereRezeptwahl();
  }
  function speichereRezeptwahl() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(selectedSlugs));
  }
  function ladeRezeptwahl() {
    selectedSlugs = [];
    speichereRezeptwahl();
    renderRecipes();
  }

  /* ── Events ───────────────────────────────────────── */
  document.getElementById('suchfeld').addEventListener('input', applyFilters);
  document.getElementById('uebernehmenBtn').addEventListener('click', () => {
    localStorage.setItem('wochenrezepte', JSON.stringify(selectedSlugs));
  });

  ladeRezeptwahl();
</script>
</body>
</html>
