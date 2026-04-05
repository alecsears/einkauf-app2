<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- HILFSFUNKTIONEN ---
function menu_datum_kw($filename, $fallbackTimestamp = null) {
    // Neues Namensschema: rezeptwoche_YYYY-MM-DD_HH-MM-SS.json
    if (preg_match('/^rezeptwoche_(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})\.json$/', $filename, $m)) {
        $jahr   = $m[1]; $monat  = $m[2]; $tag    = $m[3];
        $stunde = $m[4]; $minute = $m[5]; $sek    = $m[6];
        $ts = strtotime("$jahr-$monat-$tag $stunde:$minute:$sek");
    } elseif (preg_match('#rezeptwoche_(\d{2})\.(\d{2})\.(\d{2,4})\.json$#', $filename, $m)) {
        $tag = $m[1]; $mon = $m[2];
        $jahr = strlen($m[3]) == 2 ? ('20' . $m[3]) : $m[3];
        $ts = strtotime("$jahr-$mon-$tag 00:00:00");
    } else {
        $ts = $fallbackTimestamp ? $fallbackTimestamp : time();
    }
    $dateDisplay = "" . date('d.m.y', $ts) . " (" . date('H:i:s', $ts) . ")";
    return [$dateDisplay, date('W', $ts), $ts];
}

// Menüverzeichnis
$menudir = __DIR__ . '/daten/menuhistorie/';

/**
 * Liest eine Wochenmenü-Datei und gibt ein Array von Items zurück:
 * [
 *   ['slug' => 'abc', 'gegessen' => 'ja'|'nein'],
 *   ...
 * ]
 * Alte Struktur (nur Slugs) wird abwärtskompatibel auf 'gegessen' => 'nein' gemappt.
 */
function read_week_file_as_items(string $path): array {
    if (!file_exists($path)) return [];
    $raw = json_decode(file_get_contents($path), true);
    if (!is_array($raw)) return [];
    $items = [];
    foreach ($raw as $entry) {
        if (is_string($entry)) {
            $items[] = ['slug' => $entry, 'gegessen' => 'nein'];
        } elseif (is_array($entry)) {
            // Erlaubt sowohl ['slug' => 'x', 'gegessen' => 'ja'] als auch ['slug' => 'x'] oder ['x']
            if (isset($entry['slug'])) {
                $items[] = ['slug' => $entry['slug'], 'gegessen' => ($entry['gegessen'] ?? 'nein') === 'ja' ? 'ja' : 'nein'];
            } elseif (isset($entry[0]) && is_string($entry[0])) {
                $items[] = ['slug' => $entry[0], 'gegessen' => 'nein'];
            }
        }
    }
    return $items;
}

/**
 * Schreibt Items im neuen Format zurück.
 * Immer als Array von Objekten mit slug/gegessen (ja|nein).
 * Bewahrt den Dateizeitstempel (mtime), damit die Übersichtsliste stabil bleibt.
 */
function write_week_file_items(string $path, array $items): bool {
    // Alten mtime merken
    $oldMtime = file_exists($path) ? filemtime($path) : null;

    // Nur erlaubte Keys sichern
    $clean = array_values(array_map(function($it){
        return [
            'slug' => $it['slug'],
            'gegessen' => ($it['gegessen'] ?? 'nein') === 'ja' ? 'ja' : 'nein'
        ];
    }, $items));
    $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $ok = (bool)file_put_contents($path, $json);

    // mtime wiederherstellen, damit filemtime()-basierte Sortierungen stabil bleiben
    if ($ok && $oldMtime !== null) {
        @touch($path, $oldMtime);
    }

    return $ok;
}

// --- LÖSCH-FUNKTIONALITÄT ---
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    if (preg_match('/^rezeptwoche_[\d\-]+_\d{2}-\d{2}-\d{2}\.json$/', $file) ||
        preg_match('#^rezeptwoche_\d{2}\.\d{2}\.\d{2,4}\.json$#', $file)) {
        $filePath = $menudir . $file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    header("Location: wochenmenu.php");
    exit;
}

// --- AJAX: gegessen setzen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_gegessen') {
    header('Content-Type: application/json; charset=utf-8');
    $menu = isset($_POST['menu']) ? basename($_POST['menu']) : '';
    $slug = $_POST['slug'] ?? '';
    $value = ($_POST['value'] ?? '') === 'ja' ? 'ja' : 'nein';

    if (!$menu || !$slug) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Parameter fehlen']);
        exit;
    }
    if (!preg_match('/^(rezeptwoche_[\d\-]+_\d{2}-\d{2}-\d{2}\.json|rezeptwoche_\d{2}\.\d{2}\.\d{2,4}\.json)$/', $menu)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Ungültiger Dateiname']);
        exit;
    }
    $path = $menudir . $menu;
    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Menü nicht gefunden']);
        exit;
    }

    $items = read_week_file_as_items($path);
    $found = false;
    foreach ($items as &$it) {
        if ($it['slug'] === $slug) {
            $it['gegessen'] = $value;
            $found = true;
            break;
        }
    }
    unset($it);

    if (!$found) {
        // Falls der Eintrag fehlt, hänge ihn an (robust gegen inkonsistenzen)
        $items[] = ['slug' => $slug, 'gegessen' => $value];
    }

    if (!write_week_file_items($path, $items)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Speichern fehlgeschlagen']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

$menu_selected = isset($_GET['menu']) ? basename($_GET['menu']) : null;

// --- DETAILANSICHT EINER WOCHE ---
if ($menu_selected && preg_match('/^(rezeptwoche_[\d\-]+_\d{2}-\d{2}-\d{2}\.json|rezeptwoche_\d{2}\.\d{2}\.\d{2,4}\.json)$/', $menu_selected)) {
    $menupath = $menudir . $menu_selected;
    $wochenmenu_datum = '';
    $wochenmenu_kw = '';
    $timestamp = null;

    $items = [];
    if (file_exists($menupath)) {
        $items = read_week_file_as_items($menupath);
        list($wochenmenu_datum, $wochenmenu_kw, $timestamp) = menu_datum_kw($menu_selected);
        if (!$timestamp) $timestamp = filemtime($menupath);
    }

    // Produktliste laden (ID → Name)
    $produktliste_path = __DIR__ . '/produkte/produktliste.json';
    $produkte = [];
    if (file_exists($produktliste_path)) {
        $produkte_roh = json_decode(file_get_contents($produktliste_path), true);
        foreach ($produkte_roh as $k => $v) {
            if (is_array($v) && isset($v['id']) && isset($v['name'])) {
                $produkte[$v['id']] = $v['name'];
            } elseif (is_array($v) && isset($v['id'])) {
                $produkte[$v['id']] = $k;
            } elseif (isset($v['id'])) {
                $produkte[$v['id']] = $k;
            }
        }
    }

    // Rezeptdaten sammeln (+ gegessen-Flag)
    // Reihenfolge: erst alle "gegessen = nein", dann "gegessen = ja" (relative Originalreihenfolge bleibt je Gruppe erhalten)
    $rezepte = [];
    $bySlugGegessen = [];
    foreach ($items as $it) {
        $bySlugGegessen[$it['slug']] = ($it['gegessen'] ?? 'nein') === 'ja' ? 'ja' : 'nein';
    }
    // erst nicht gegessen
    foreach ($items as $it) {
        $slug = $it['slug'];
        $pfad = __DIR__ . "/rezeptkasten/rezepte/$slug.json";
        if (file_exists($pfad) && ($bySlugGegessen[$slug] ?? 'nein') === 'nein') {
            $json = json_decode(file_get_contents($pfad), true);
            $bild = file_exists(__DIR__ . "/rezeptkasten/bilder/{$slug}.webp") ? "rezeptkasten/bilder/{$slug}.webp" : null;
            $rezepte[] = [
                'slug' => $slug,
                'titel' => $json['name'] ?? $slug,
                'bild' => $bild,
                'vegetarisch' => $json['vegetarisch'] ?? '',
                'json' => $json,
                'gegessen' => 'nein',
            ];
        }
    }
    // dann gegessen
    foreach ($items as $it) {
        $slug = $it['slug'];
        $pfad = __DIR__ . "/rezeptkasten/rezepte/$slug.json";
        if (file_exists($pfad) && ($bySlugGegessen[$slug] ?? 'nein') === 'ja') {
            $json = json_decode(file_get_contents($pfad), true);
            $bild = file_exists(__DIR__ . "/rezeptkasten/bilder/{$slug}.webp") ? "rezeptkasten/bilder/{$slug}.webp" : null;
            $rezepte[] = [
                'slug' => $slug,
                'titel' => $json['name'] ?? $slug,
                'bild' => $bild,
                'vegetarisch' => $json['vegetarisch'] ?? '',
                'json' => $json,
                'gegessen' => 'ja',
            ];
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <script>
console.log("Produktlisten-Lader block wird ausgeführt");
window.produktListe = {};
async function ladeProduktListe() {
  console.log("ladeProduktListe wurde aufgerufen");
  try {
    const res = await fetch('/einkauf-app/produkte/produktliste.json?ts=' + Date.now());
    if (res.ok) {
      console.log("Produktliste wurde geladen");
      const obj = await res.json();
      window.produktListe = {};
      Object.entries(obj).forEach(([name, data]) => {
        if (data.id !== undefined) window.produktListe[data.id] = { ...data, _name: name };
      });
    } else {
      console.error("Produktliste nicht geladen, Status:", res.status);
    }
  } catch (e) {
    console.error("Fehler beim Laden der Produktliste:", e);
    window.produktListe = {};
  }
}
ladeProduktListe();
</script>
      <meta charset="UTF-8">
      <title>Wochenmenü</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
      <link href="/einkauf-app/assets/rezept-modal.css" rel="stylesheet">
      <link href="assets/style.css" rel="stylesheet">
      <style>
        body { padding-top: 72px; background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
        .cluster-title { font-size: 1.18rem; font-weight: 600; margin: 1.6rem 0 1rem 0; color: #333; letter-spacing: 0.02em; display: flex; align-items: center; gap: 0.4em; }
        .kachel { background: #fff; border-radius: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 1rem; display: flex; align-items: center; padding: 0.9rem 1.1rem; min-height: 80px; transition: box-shadow 0.2s, border 0.2s, opacity .2s, filter .2s; cursor: pointer; border: 2px solid #e0e0e0; }
        .kachel:hover { box-shadow: 0 4px 16px rgba(76,175,80,0.05); border: 2px solid #4caf50; }
        .kachel-img { width: 62px; height: 62px; border-radius: 10px; background: #e9ecef; object-fit: cover; margin-right: 1.1rem; display: flex; align-items: center; justify-content: center; font-size: 44px; color: #bbb; }
        .kachel-img img { width: 62px; height: 62px; object-fit: cover; border-radius: 10px; }
        .vegan-icon { color: #4caf50; font-size: 1.3rem; margin-left: 0.3em; vertical-align: middle; }
        .kachel-title { font-size: 1.15rem; font-weight: 500; margin-bottom: 0; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
        .kachel-actions { margin-left: auto; display: flex; align-items: center; gap: .6rem; }
        .check-wrap { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; border: 2px solid #e0e0e0; background: #fff; }
        .check-wrap input[type="checkbox"] { width: 22px; height: 22px; cursor: pointer; }
        .kachel.gegessen { opacity: .55; filter: grayscale(0.95); }
      </style>
    </head>
    <body>
      <div class="sticky-header d-flex align-items-center">
        <a href="wochenmenu.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
          <span class="material-symbols-outlined">arrow_back</span>
        </a>
      </div>

      <div class="container py-4">
        <h3 class="mb-4"><?=count($rezepte)?> Rezepte vom <?=$wochenmenu_datum?>
          <?php if($wochenmenu_kw): ?> <span class="text-secondary" style="font-size:.95em; font-weight:400;">(KW <?=$wochenmenu_kw?>)</span><?php endif; ?>
        </h3>

        <?php if (empty($rezepte)): ?>
          <div class="alert alert-light text-center">Es wurden noch keine Rezepte für diese Woche gewählt.</div>
        <?php else: ?>
          <div id="rezepteListe">
            <?php foreach ($rezepte as $r): ?>
              <div class="kachel <?= $r['gegessen']==='ja' ? 'gegessen' : '' ?>" data-slug="<?=htmlspecialchars($r['slug'])?>" data-gegessen="<?=$r['gegessen']==='ja' ? 'ja' : 'nein'?>">
                <?php if ($r['bild']): ?>
                  <span class="kachel-img"><img src="<?=htmlspecialchars($r['bild'])?>" alt=""></span>
                <?php else: ?>
                  <span class="kachel-img material-symbols-outlined">restaurant</span>
                <?php endif; ?>
                <div class="kachel-title">
                  <?=htmlspecialchars($r['titel'])?>
                  <?php if ($r['vegetarisch'] === 'ja'): ?>
                    <span class="vegan-icon material-symbols-outlined" title="Vegetarisch">eco</span>
                  <?php endif; ?>
                </div>
                <div class="kachel-actions">
                  <label class="check-wrap" title="Gegessen">
                    <input type="checkbox" class="gegessen-toggle" <?= $r['gegessen']==='ja' ? 'checked' : '' ?>>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Modal -->
      <?php include 'assets/rezept-modal.html'; ?>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
      <script src="assets/rezept-modal.js"></script>
      <script>
        // Produktliste als Map: id -> name
        const produktliste = <?php echo json_encode($produkte, JSON_UNESCAPED_UNICODE); ?>;
        const rezepte = <?php
          $rezepteJS = [];
          foreach ($rezepte as $r) {
              $r['json'] = $r['json'] ?? [];
              $rezepteJS[$r['slug']] = [
                  'titel' => $r['titel'],
                  'bild' => $r['bild'],
                  'vegetarisch' => $r['vegetarisch'],
                  'json' => $r['json'],
                  'gegessen' => $r['gegessen'],
              ];
          }
          echo json_encode($rezepteJS, JSON_UNESCAPED_UNICODE);
        ?>;
        const aktuellesMenuFile = <?= json_encode($menu_selected) ?>;

        function resolveZutat(zutat) {
            if (typeof zutat === 'string' || typeof zutat === 'number') {
                return produktliste[zutat] || zutat;
            }
            if (Array.isArray(zutat)) {
                let id = zutat[0]; let menge = zutat[1] || ''; let einheit = zutat[2] || '';
                let name = produktliste[id] || id;
                let text = name;
                if (menge || einheit) text = `${menge} ${einheit} ${name}`.trim();
                return text;
            }
            if (typeof zutat === 'object' && zutat !== null) {
                let id = zutat.id;
                let menge = zutat.rezeptmenge || '';
                let einheit = zutat.rezepteinheit || '';
                let name = produktliste[id] || id;
                let text = name;
                if (menge || einheit) text = `${menge} ${einheit} ${name}`.trim();
                return text;
            }
            return String(zutat);
        }


        // Interaktion
        document.addEventListener('DOMContentLoaded', function() {
          const list = document.getElementById('rezepteListe');

          // Klick auf Karte öffnet Details – aber NICHT, wenn Checkbox geklickt wurde
          list.querySelectorAll('.kachel[data-slug]').forEach(function(el){
              el.addEventListener('click', function(e){
                  if (e.target && (e.target.matches('input[type="checkbox"]') || e.target.closest('.check-wrap'))) return;
                  const slug = this.getAttribute('data-slug');
                  const bild = rezepte[slug] && rezepte[slug].bild ? rezepte[slug].bild : null;
                  showPreviewModal(slug, bild);
              });
          });

          // Toggle „gegessen"-Checkbox
          list.querySelectorAll('.gegessen-toggle').forEach(function(cb){
            cb.addEventListener('click', function(e){
              e.stopPropagation();
              const card = this.closest('.kachel');
              const slug = card.getAttribute('data-slug');
              const value = this.checked ? 'ja' : 'nein';

              // Sofortige UI-Reaktion
              card.classList.toggle('gegessen', value === 'ja');
              card.setAttribute('data-gegessen', value);

              // Sortierung: nicht gegessen nach oben, gegessen ans Ende (Stabilität innerhalb der Gruppen)
              const cards = Array.from(list.querySelectorAll('.kachel'));
              cards.sort((a,b) => {
                const av = a.getAttribute('data-gegessen') === 'ja' ? 1 : 0;
                const bv = b.getAttribute('data-gegessen') === 'ja' ? 1 : 0;
                return av - bv; // 0 vor 1
              });
              cards.forEach(c => list.appendChild(c));

              // Persistieren
              const form = new FormData();
              form.append('action', 'set_gegessen');
              form.append('menu', aktuellesMenuFile);
              form.append('slug', slug);
              form.append('value', value);

              fetch(location.pathname + location.search, {
                method: 'POST',
                body: form
              }).then(async (res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const j = await res.json();
                if (!j.ok) throw new Error(j.msg || 'Fehler beim Speichern');
              }).catch(err => {
                // Revert bei Fehler
                console.error('Speicherfehler:', err);
                this.checked = !this.checked;
                const back = this.checked ? 'ja' : 'nein';
                card.classList.toggle('gegessen', back === 'ja');
                card.setAttribute('data-gegessen', back);
                // Neu sortieren nach Revert
                const cards2 = Array.from(list.querySelectorAll('.kachel'));
                cards2.sort((a,b) => {
                  const av = a.getAttribute('data-gegessen') === 'ja' ? 1 : 0;
                  const bv = b.getAttribute('data-gegessen') === 'ja' ? 1 : 0;
                  return av - bv;
                });
                cards2.forEach(c => list.appendChild(c));
                alert('Konnte nicht speichern.');
              });
            });
          });
        });
      </script>
    </body>
    </html>
    <?php
    exit;
}

// --- ÜBERSICHT ALLER MENÜS ---
$menu_files = glob($menudir . 'rezeptwoche_*.json');
usort($menu_files, function($a, $b) {
    list(, , $tsA) = menu_datum_kw(basename($a), filemtime($a));
    list(, , $tsB) = menu_datum_kw(basename($b), filemtime($b));
    return $tsB - $tsA;
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Wochenmenü Übersicht</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <style>
    .menu-item-wrapper { display: flex; align-items: center; margin-bottom: 1.2rem; }
    .menu-kachel {
      background: #fff; border-radius: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      padding: 1.15rem 1.3rem; min-height: 80px; transition: box-shadow 0.2s, border 0.2s;
      cursor: pointer; border: 2px solid #e0e0e0; font-size: 1.12rem; flex-grow: 1;
      text-decoration: none; color: #333;
    }
    .menu-kachel:hover { box-shadow: 0 4px 16px rgba(76,175,80,0.05); border: 2px solid #4caf50; background: #f7fff6; }
    .menu-kachel-datum { font-weight: 600; font-size: 1.18em; min-width: 120px; }
    .menu-kachel-anzahl { margin-left: 1.2em; color: #333; border-radius: 1rem; font-weight: normal; padding: 0.18em 1.3em; font-size: 1em; }
    .delete-btn { background: transparent; border: none; cursor: pointer; color: #d9534f; font-size: 1.4rem; margin-left: 0.5rem; }
    .delete-btn:hover { color: #b52b27; }
  </style>
</head>
<body>
  <div class="sticky-header d-flex align-items-center">
    <a href="start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
  </div>
  <div class="container py-4">
    <h3 class="mb-4">Rezeptwochen</h3>
    <?php if (empty($menu_files)): ?>
      <div class="alert alert-light text-center">Keine Menüs gefunden.</div>
    <?php else: ?>
      <?php foreach ($menu_files as $file):
        $basename = basename($file);
        list($datum, $kw, $ts) = menu_datum_kw($basename, filemtime($file));
        $items = read_week_file_as_items($file);
      ?>
      <div class="menu-item-wrapper">
        <a href="?menu=<?=urlencode($basename)?>" class="menu-kachel d-flex justify-content-between align-items-center">
          <div class="flex-grow-1" style="min-width:0;">
            <div class="fw-bold" style="font-size:1.28em; color:#212529; line-height:1.15; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
              <?= "KW " . htmlspecialchars($kw ? $kw : "?") ?>
            </div>
            <div class="menu-kachel-sub" style="color:#868e96; font-size:0.99em; font-weight:400; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
              <?= htmlspecialchars($datum ? $datum : $basename) ?>
            </div>
          </div>
          <span class="badge bg-success ms-3 d-flex align-items-center justify-content-center"
                style="font-size:1.07em; height:2.2em; min-width:2.7em; border-radius:1.1em;">
            <?=count($items)?>
          </span>
        </a>
        <button class="delete-btn ms-2"
                onclick="if(confirm('Möchtest du das Menü wirklich löschen?')) { window.location.href='?delete=<?=urlencode($basename)?>'; }"
                title="Menü löschen" aria-label="Menü löschen">
          <span class="material-symbols-outlined">delete</span>
        </button>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script src="/einkauf-app/assets/rezept-modal.js"></script>
</body>
</html>