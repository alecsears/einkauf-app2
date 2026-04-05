<?php
// Produktliste laden
$jsonPath = __DIR__ . '/../produkte/produktliste.json';
$produkte = file_exists($jsonPath)
    ? json_decode(file_get_contents($jsonPath), true)
    : [];
$id2produkt = [];
$namelist = [];
foreach ($produkte as $pname => $pdata) {
    if (isset($pdata['id'])) {
        $id2produkt[$pdata['id']] = array_merge($pdata, ['_name' => $pname]);
        $namelist[] = $pname;
    }
}
sort($namelist, SORT_NATURAL | SORT_FLAG_CASE);

// Einheiten laden (ANPASSUNG: Klarname = label aus neuer Struktur)
$einheitenPfad = __DIR__ . '/../produkte/einheiten.json';
$einheitenJson = file_exists($einheitenPfad) ? json_decode(file_get_contents($einheitenPfad), true) : [];
$produkteinheiten = [];
if (isset($einheitenJson['Produkteinheiten'])) {
    foreach ($einheitenJson['Produkteinheiten'] as $code => $einheit) {
        $produkteinheiten[$code] = $einheit['label'];
    }
}

// Abteilungen laden (bleibt unverändert)
$abteilungenDatei = __DIR__ . '/../maerkte/abteilungen.txt';
$abteilungen = [];
if (file_exists($abteilungenDatei)) {
    foreach (file($abteilungenDatei) as $line) {
        $line = trim($line);
        if ($line !== '') $abteilungen[] = $line;
    }
    $abteilungen = array_unique($abteilungen);
    sort($abteilungen, SORT_STRING | SORT_FLAG_CASE);
}

// Rezept-Initialisierung
$rezeptname   = '';
$zutaten      = [];
$bildname     = '';
$bildUrl      = '';
$zubereitung  = '';
$kalorien     = '';
$vegetarisch  = 'nein';
$frequenz     = 0;

if (isset($_GET['edit'])) {
    $dateiname = basename($_GET['edit']);
    $pfad      = __DIR__ . "/rezepte/$dateiname";
    if (file_exists($pfad)) {
        $json = json_decode(file_get_contents($pfad), true);
        if ($json) {
            $rezeptname  = $json['name'] ?? '';
            $zubereitung = $json['zubereitung'] ?? '';
            $kalorien    = $json['kalorien'] ?? '';
            $vegetarisch = $json['vegetarisch'] ?? 'nein';
            $frequenz    = $json['frequenz'] ?? 0;
            foreach (($json['zutaten'] ?? []) as $z) {
                $id   = $z['id'] ?? null;
                $prod = $id && isset($id2produkt[$id]) ? $id2produkt[$id] : null;
                $rezepteinheit = '';
                if ($prod && isset($prod['rezepteinheit'][0])) {
                    if (isset($produkteinheiten[$prod['rezepteinheit'][0]])) {
                        $rezepteinheit = $prod['rezepteinheit'][0];
                    }
                }
                if (
                    isset($z['rezepteinheit']) &&
                    $z['rezepteinheit'] !== '' &&
                    isset($produkteinheiten[$z['rezepteinheit']])
                ) {
                    $rezepteinheit = $z['rezepteinheit'];
                } elseif (empty($rezepteinheit) && !empty($produkteinheiten)) {
                    reset($produkteinheiten);
                    $rezepteinheit = key($produkteinheiten);
                }
                $zutaten[] = [
                    'id'            => $id,
                    'rezeptmenge'   => $z['rezeptmenge'],
                    'name'          => $prod['_name'] ?? '',
                    'rezepteinheit' => $rezepteinheit
                ];
            }
            // ZUTATEN NACH MENGE (rezeptmenge) SORTIEREN (absteigend)
            usort($zutaten, function($a, $b) {
                // numerisch vergleichen, leere Werte als 0
                $ma = floatval(str_replace(',', '.', $a['rezeptmenge']));
                $mb = floatval(str_replace(',', '.', $b['rezeptmenge']));
                return $mb <=> $ma;
            });
        }

        // Bildpfade ermitteln: bevorzugt .webp, Fallback auf alte .jpg (für Bestandsdaten)
        $slug           = pathinfo($dateiname, PATHINFO_FILENAME);
        $bilderDir      = __DIR__ . "/bilder";
        $bildWebpPfad   = $bilderDir . "/" . $slug . ".webp";
        $bildJpgPfad    = $bilderDir . "/" . $slug . ".jpg";

        if (file_exists($bildWebpPfad)) {
            $bildname = $bildWebpPfad;
            $bildUrl  = 'bilder/' . $slug . '.webp';
        } elseif (file_exists($bildJpgPfad)) {
            // Nur noch lesen, keine neuen JPGs mehr erzeugen
            $bildname = $bildJpgPfad;
            $bildUrl  = 'bilder/' . $slug . '.jpg';
        }
    }
}

// Häufigkeit-Optionen (bleibt unverändert)
$haeufigkeitOptionen = [
    0 => 'Selten gekocht',
    1 => 'Regelmäßig gekocht',
    2 => 'Evergreen'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($rezeptname ?: (isset($_GET['edit']) ? 'Rezept bearbeiten' : 'Neues Rezept hinzufügen')) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    body { background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
    .zutat-zeile:hover { background-color: #eef; border-radius: 6px; }
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040;
      background: #fff; border-top: 1px solid #ddd; padding: 0.75rem 1.5rem;
      box-shadow: 0 -2px 8px rgba(0,0,0,0.04); }
    body { padding-bottom: 85px; }
    #modalZutatInputSuggestions {
        border: 1px solid #ced4da;
        max-height: 150px;
        overflow-y: auto;
        position: absolute;
        background-color: white;
        width: calc(100% - 2rem);
        z-index: 1055;
        display: none;
    }
    #modalZutatInputSuggestions div {
        padding: 0.375rem 0.75rem;
        cursor: pointer;
    }
    #modalZutatInputSuggestions div:hover {
        background-color: #e9ecef;
    }
    .format-toolbar button {
        margin-right: 0.25em;
        font-size: 1.1em;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <h1 class="mb-4"><?= isset($_GET['edit']) ? 'Rezept bearbeiten' : 'Neues Rezept hinzufügen' ?></h1>

    <form id="rezeptForm" action="speichere_rezept.php" method="post" enctype="multipart/form-data">
      <div class="mb-4">
        <label for="rezeptname" class="form-label">Rezeptname</label>
        <input type="text" name="rezeptname" id="rezeptname" class="form-control" required
               value="<?= htmlspecialchars($rezeptname) ?>" autofocus>
      </div>
      <label class="form-label">Zutaten</label>
      <div id="zutaten" class="mb-3">
        <?php foreach ($zutaten as $z): ?>
          <div class="row g-2 mb-2 align-items-center zutat-zeile" data-id="<?= htmlspecialchars($z['id']) ?>">
            <div class="col-6">
              <?= htmlspecialchars($z['name']) ?>
              <input type="hidden" name="zutat[]" value="<?= htmlspecialchars($z['name']) ?>">
              <input type="hidden" name="zutat_id[]" value="<?= htmlspecialchars($z['id']) ?>">
            </div>
            <div class="col-3">
              <input type="number" name="menge[]" class="form-control" required min="0" step="any" value="<?= htmlspecialchars($z['rezeptmenge']) ?>">
            </div>
            <div class="col-2">
              <select name="einheit[]" class="form-select" required>
                <?php foreach ($produkteinheiten as $code => $label): ?>
                  <option value="<?= htmlspecialchars($code) ?>" <?= ($z['rezepteinheit'] == $code) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-1 text-end">
              <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.zutat-zeile').remove()">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </div>
            <hr class="my-1">
          </div>
        <?php endforeach; ?>
      </div>

      <button type="button" class="btn btn-outline-dark mb-4" onclick="showAddZutatModal()">
        <span class="material-symbols-outlined">add</span> Zutat hinzufügen
      </button>

      <!-- ZUBEREITUNG -->
      <div class="mb-4">
        <label for="zubereitung" class="form-label">Zubereitung</label>
        <div class="mb-2 format-toolbar">
          <button type="button" class="btn btn-light btn-sm" title="Fett" onclick="formatZubereitung('bold')"><b>B</b></button>
          <button type="button" class="btn btn-light btn-sm" title="Kursiv" onclick="formatZubereitung('italic')"><i>I</i></button>
          <button type="button" class="btn btn-light btn-sm" title="Aufzählung" onclick="formatZubereitung('ul')">&bull; Liste</button>
          <button type="button" class="btn btn-light btn-sm" title="Nummeriert" onclick="formatZubereitung('ol')">1. Liste</button>
        </div>
        <textarea name="zubereitung" id="zubereitung" class="form-control" rows="6" placeholder="Beschreibe hier die Zubereitung..."><?= htmlspecialchars($zubereitung) ?></textarea>
        <div class="form-text">
          <strong>Formatierungshilfen:</strong> <b>B</b> = fett, <i>I</i> = kursiv, &bull; Liste = Aufzählung, 1. Liste = Nummerierung.
        </div>
      </div>

      <!-- Bild-Upload -->
      <div class="mb-4">
        <label for="bild" class="form-label">Bild (optional)</label>
        <div class="d-flex align-items-center">
          <input type="file" id="bild" class="d-none" accept="image/*">
          <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('bild').click();">
            <span class="material-symbols-outlined">add_photo_alternate</span> Bild hinzufügen
          </button>
        </div>
        <!-- Hidden field: relative path of the already-uploaded WebP image -->
        <input type="hidden" name="bild_gespeichert" id="bild_gespeichert"
               value="<?= htmlspecialchars($bildUrl) ?>">
        <div id="bildPreview" class="mt-2">
          <?php if ($bildname && file_exists($bildname) && $bildUrl): ?>
            <img src="<?= htmlspecialchars($bildUrl) ?>?t=<?= time() ?>" class="img-thumbnail mt-3" style="max-width:200px;" alt="Rezeptbild">
          <?php endif; ?>
        </div>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" value="ja" id="vegetarisch" name="vegetarisch"
          <?= ($vegetarisch === 'ja') ? 'checked' : '' ?>>
        <label class="form-check-label" for="vegetarisch">Vegetarisch</label>
      </div>

      <div class="mb-4">
        <label for="frequenz" class="form-label">Häufigkeit</label>
        <select class="form-select" id="frequenz" name="frequenz">
          <?php foreach ($haeufigkeitOptionen as $k => $v): ?>
            <option value="<?= $k ?>" <?= ((string)$frequenz === (string)$k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (isset($_GET['edit'])): ?>
        <input type="hidden" name="editdatei" value="<?= htmlspecialchars($_GET['edit']) ?>">
      <?php endif; ?>
    </form>

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
              <datalist id="zutatenVorschlagsliste">
                <?php foreach ($namelist as $z): ?><option value="<?= htmlspecialchars($z) ?>"></option><?php endforeach; ?>
              </datalist>
            </div>
            <div class="mb-3">
              <label for="modalMengeInput" class="form-label">Menge</label>
              <input type="number" id="modalMengeInput" class="form-control" min="0" step="any">
            </div>
            <div class="mb-3">
              <label for="modalEinheitInput" class="form-label">Einheit</label>
              <select id="modalEinheitInput" class="form-select" required>
                <?php foreach ($produkteinheiten as $code => $label): ?>
                  <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modalAbbrechenBtn" data-bs-dismiss="modal">Abbrechen</button>
            <button type="button" id="modalOkBtn" class="btn btn-primary">OK</button>
          </div>
        </div></div>
    </div>

    <?php if (isset($_GET['edit'])): ?>
      <div class="mt-4 text-end">
        <form action="loesche_rezept.php" method="post" class="d-inline"
              onsubmit="return confirm('Rezept wirklich löschen?');" style="margin:0;">
          <input type="hidden" name="deletefile" value="<?= htmlspecialchars($_GET['edit']) ?>">
          <button type="submit" class="btn btn-danger">
            <span class="material-symbols-outlined">delete_forever</span> Rezept löschen
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div class="sticky-footer d-flex justify-content-between align-items-center">
    <button type="button" class="btn btn-secondary" onclick="window.location.href='rezeptauswahl.php'">
      <span class="material-symbols-outlined">close</span> Abbrechen
    </button>

    <button type="button" class="btn btn-primary btn-icon" onclick="document.getElementById('rezeptForm').requestSubmit();">
      <span class="material-symbols-outlined">save</span> Speichern
    </button>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="crop-modal.js"></script>

  <script>
    // JS slugify (mirrors PHP slugify for generating the image filename)
    function slugify(text) {
      const map = {
        'ä':'ae','ö':'oe','ü':'ue','Ä':'ae','Ö':'oe','Ü':'ue','ß':'ss'
      };
      text = text.replace(/[äöüÄÖÜß]/g, m => map[m] || m);
      text = text.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
      text = text.toLowerCase().replace(/[^a-z0-9]+/g, '-')
                 .replace(/^-+|-+$/g, '').replace(/-+/g, '-');
      return text || 'rezept-' + Date.now();
    }

    // Image upload: open crop modal → upload to backend → store URL
    document.getElementById('bild').addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (!file) return;
      this.value = ''; // reset so the same file can be re-selected

      const slug = slugify(document.getElementById('rezeptname').value.trim())
                   || 'rezept-' + Date.now();

      if (typeof CropModal === 'undefined') {
        alert('Crop-Modul konnte nicht geladen werden.');
        return;
      }

      CropModal.open(file, function (blob, dataURL) {
        // POST base64 PNG to upload_bild.php for WebP conversion
        fetch('upload_bild.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bildData: dataURL, slug: slug })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok) {
            const preview = document.getElementById('bildPreview');
            const ts = Date.now();
            preview.innerHTML =
              '<img src="' + data.url + '?t=' + ts + '" ' +
              'class="img-thumbnail mt-3" style="max-width:200px;" alt="Rezeptbild">';
            document.getElementById('bild_gespeichert').value = data.url;
          } else {
            alert('Fehler beim Hochladen: ' + (data.error || 'Unbekannt'));
          }
        })
        .catch(function (err) {
          alert('Upload-Fehler: ' + (err.message || String(err)));
        });
      });
    });

    // Formatierungshilfen für Zubereitung
    function formatZubereitung(action) {
      const textarea = document.getElementById('zubereitung');
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      let text = textarea.value;
      let before = text.slice(0, start);
      let selected = text.slice(start, end);
      let after = text.slice(end);

      if (action === 'bold') {
        textarea.value = before + '**' + selected + '**' + after;
        textarea.setSelectionRange(start + 2, end + 2);
      } else if (action === 'italic') {
        textarea.value = before + '*' + selected + '*' + after;
        textarea.setSelectionRange(start + 1, end + 1);
      } else if (action === 'ul') {
        let lines = selected || 'Element';
        if (!selected) {
          textarea.value = before + '- ' + lines + after;
          textarea.setSelectionRange(start + 2, start + 2 + lines.length);
        } else {
          let formatted = lines.split('\n').map(l => l.startsWith('- ') ? l : '- ' + l).join('\n');
          textarea.value = before + formatted + after;
          textarea.setSelectionRange(start, start + formatted.length);
        }
      } else if (action === 'ol') {
        let lines = selected || 'Element';
        if (!selected) {
          textarea.value = before + '1. ' + lines + after;
          textarea.setSelectionRange(start + 3, start + 3 + lines.length);
        } else {
          let formatted = lines.split('\n').map((l, i) => /^\d+\.\s/.test(l) ? l : (i + 1) + '. ' + l).join('\n');
          textarea.value = before + formatted + after;
          textarea.setSelectionRange(start, start + formatted.length);
        }
      }
      textarea.focus();
    }

    const id2produkt          = <?= json_encode($id2produkt) ?>;
    const produktNamenListe   = <?= json_encode($namelist) ?>;
    const produkteinheitenJS  = <?= json_encode($produkteinheiten) ?>;

    const modalZutatInput = document.getElementById('modalZutatInput');
    const suggestionsContainer = document.getElementById('modalZutatInputSuggestions');

    function updateEinheitenFuerZutat(zutatName) {
      const einheitSelect = document.getElementById('modalEinheitInput');
      einheitSelect.innerHTML = '';
      let prod = null;
      let preferredProdUnit = null;

      if (zutatName) {
        for (const id in id2produkt) {
          if (id2produkt[id]['_name'].toLowerCase() === zutatName.toLowerCase()) {
            prod = id2produkt[id];
            if (prod.rezepteinheit) {
              preferredProdUnit = Array.isArray(prod.rezepteinheit) ? prod.rezepteinheit[0] : prod.rezepteinheit;
            }
            break;
          }
        }
      }

      let firstUnitInList = null;
      for (const code in produkteinheitenJS) {
        const opt = document.createElement('option');
        opt.value = code;
        opt.textContent = produkteinheitenJS[code];
        einheitSelect.appendChild(opt);
        if (!firstUnitInList) firstUnitInList = code;
      }

      if (
        preferredProdUnit &&
        einheitSelect.querySelector(`option[value="${preferredProdUnit}"]`)
      ) {
        einheitSelect.value = preferredProdUnit;
      } else if (firstUnitInList) {
        einheitSelect.value = firstUnitInList;
      }
    }

    modalZutatInput.addEventListener('input', function() {
      const inputText = this.value.trim().toLowerCase();
      suggestionsContainer.innerHTML = '';
      if (inputText.length > 0) {
          const filteredNamen = produktNamenListe.filter(name => name.toLowerCase().includes(inputText));
          if (filteredNamen.length > 0) {
              filteredNamen.forEach(name => {
                  const div = document.createElement('div');
                  div.textContent = name;
                  div.addEventListener('click', function() {
                      modalZutatInput.value = name;
                      suggestionsContainer.style.display = 'none';
                      suggestionsContainer.innerHTML = '';
                      updateEinheitenFuerZutat(name);
                      document.getElementById('modalMengeInput').focus();
                  });
                  suggestionsContainer.appendChild(div);
              });
              suggestionsContainer.style.display = 'block';
          } else {
              suggestionsContainer.style.display = 'none';
          }
      } else {
          suggestionsContainer.style.display = 'none';
      }
      updateEinheitenFuerZutat(this.value.trim());
    });

    document.addEventListener('click', function(event) {
        if (!modalZutatInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });

    function showAddZutatModal() {
      modalZutatInput.value = "";
      document.getElementById('modalMengeInput').value = "";
      suggestionsContainer.style.display = 'none';
      suggestionsContainer.innerHTML = '';
      updateEinheitenFuerZutat("");
      const modal = new bootstrap.Modal(document.getElementById('addZutatModal'));
      modal.show();
      document.getElementById('addZutatModal').addEventListener('shown.bs.modal', function () {
          modalZutatInput.focus();
      }, { once: true });
    }

    document.getElementById('modalOkBtn').addEventListener('click', function() {
      const zutatName = modalZutatInput.value.trim();
      const menge = document.getElementById('modalMengeInput').value.trim();
      const einheitCode = document.getElementById('modalEinheitInput').value;
      if (!zutatName || !menge || !einheitCode) {
        alert("Bitte alle Felder ausfüllen!");
        return;
      }
      let found = null, zutatId = "";
      for (const id in id2produkt) {
        if (id2produkt[id]['_name'].toLowerCase() === zutatName.toLowerCase()) {
          found = id2produkt[id];
          zutatId = id;
          break;
        }
      }
      const einheitLabel = produkteinheitenJS[einheitCode] || einheitCode;
      addZutatRow(zutatName, menge, einheitCode, einheitLabel, zutatId);
      bootstrap.Modal.getInstance(document.getElementById('addZutatModal')).hide();
    });

    function addZutatRow(zutatName, menge, rezepteinheitCode, einheitLabel, zutatId) {
      const wrapper = document.createElement('div');
      wrapper.className = 'row g-2 mb-2 align-items-center zutat-zeile';
      if (zutatId) wrapper.dataset.id = zutatId;

      let einheitOptions = '';
      for (const code in produkteinheitenJS) {
        einheitOptions += `<option value="${code}" ${code === rezepteinheitCode ? 'selected' : ''}>${produkteinheitenJS[code]}</option>`;
      }

      wrapper.innerHTML = `
        <div class="col-6">
          ${zutatName}
          <input type="hidden" name="zutat[]" value="${zutatName}">
          <input type="hidden" name="zutat_id[]" value="${zutatId}">
        </div>
        <div class="col-3">
          <input type="text" name="menge[]" class="form-control" required value="${menge}">
        </div>
        <div class="col-2">
          <select name="einheit[]" class="form-select" required>
            ${einheitOptions}
          </select>
        </div>
        <div class="col-1 text-end">
          <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.zutat-zeile').remove()">
            <span class="material-symbols-outlined">delete</span>
          </button>
        </div>
        <hr class="my-1">
      `;
      document.getElementById('zutaten').appendChild(wrapper);
    }
  </script>
</body>
</html>
```
