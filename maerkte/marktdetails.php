<?php
// bearbeiten.php: Bearbeiten eines bestehenden Supermarkt-Abteilungsplans

$slug = $_GET['markt'] ?? '';
if ($slug === '') {
    die('Kein Markt ausgewählt.');
}
$marktFile = __DIR__ . "/lokationen/{$slug}.json";
$abtFile   = __DIR__ . '/abteilungen.txt';

// Lade alle möglichen Abteilungen
$options = [];
if (file_exists($abtFile)) {
    $options = file($abtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Lade existierende Marktdaten (Name + Abteilungen)
$marktname = '';
$abteilungen = [];
if (file_exists($marktFile)) {
    $json = json_decode(file_get_contents($marktFile), true);
    if ($json) {
        $marktname = $json['name'] ?? '';
        $abteilungen = $json['abteilungen'] ?? [];
        // Sortieren nach "order" (zur Sicherheit)
        usort($abteilungen, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    }
}

// Erstelle HTML-Options
$optionsHtml = '';
foreach ($options as $opt) {
    $optEsc = htmlspecialchars($opt, ENT_QUOTES);
    $optionsHtml .= "<option value=\"{$optEsc}\">{$optEsc}</option>";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Markt bearbeiten: <?= htmlspecialchars($marktname, ENT_QUOTES) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
     body { padding-top: 72px; background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
     .sticky-header { position: fixed; top: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #ddd; padding: 0.75rem 1.5rem; box-shadow: 0 -2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; z-index: 1040;  }
  
    }
    
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48;
      font-size: 48px;
      color: #4B15DA;
    } .drag-handle { cursor: grab; }
    .sticky-footer {
      position: fixed; bottom: 0; left: 0; right: 0;
      background: #fff; padding: .5rem 1rem;
      border-top: 1px solid #ddd;
      z-index: 1030;
    }
    tr.sortable-chosen { background: #f0f6ff !important; }
    tr.sortable-ghost { opacity: 0.5; }
  </style>
</head>
<body>
  <div class="container py-4">
       <div class="sticky-header d-flex justify-content-between align-items-center">
  <div class="container d-flex align-items-center">
    <a href="marktuebersicht.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined" style="font-size:24px;">arrow_back</span>
    </a>
  </div>
</div>
  
    <form id="abteilungsForm">
      <div class="mb-3">
        <label for="marktName" class="form-label">Name des Marktes:</label>
        <input type="text" id="marktName" name="marktName"
               class="form-control"
               value="<?= htmlspecialchars($marktname, ENT_QUOTES) ?>"
               required>
      </div>
      <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="abteilungen">
          <thead>
            <tr><th></th><th>Abteilung</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($abteilungen as $i => $abt): ?>
            <tr>
              <td class="text-muted drag-handle">≡</td>
              <td>
                <select class="form-select">
                  <option value="">-- Bitte wählen --</option>
                  <?php foreach ($options as $opt): ?>
                  <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"
                    <?= ($opt === ($abt['name'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($opt, ENT_QUOTES) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><button type="button" class="btn btn-danger btn-sm delete-row">×</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES) ?>">
    </form>
  </div>

  <div class="sticky-footer d-flex justify-content-between align-items-center">
    <button id="neueZeile" class="btn btn-outline-primary">
      <span class="material-symbols-outlined">add</span> Zeile
    </button>
    <button id="save" class="btn btn-outline-success">
      <span class="material-symbols-outlined">save</span> Speichern
    </button>
  </div>

  <!-- Bootstrap JS (Optional für Bootstrap-Komponenten) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SortableJS für Drag & Drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script>
    const tbody       = document.querySelector('#abteilungen tbody');
    const optionsHtml = <?= json_encode($optionsHtml) ?>;

    // SortableJS für Drag & Drop (funktioniert auch mit <table>)
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150
    });

    // Neue Zeile hinzufügen
    document.getElementById('neueZeile').addEventListener('click', () => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td class="text-muted drag-handle">≡</td>' +
        '<td>' +
          '<select class="form-select">' +
            '<option value="">-- Bitte wählen --</option>' +
            optionsHtml +
          '</select>' +
        '</td>' +
        '<td><button type="button" class="btn btn-danger btn-sm delete-row">×</button></td>';
      tbody.appendChild(tr);
    });

    // Zeile löschen
    tbody.addEventListener('click', e => {
      if (e.target.classList.contains('delete-row')) {
        e.target.closest('tr').remove();
      }
    });

    // Speichern (als JSON)
    document.getElementById('save').addEventListener('click', async () => {
      const marktName = document.getElementById('marktName').value.trim();
      if (!marktName) return alert('Bitte einen Markt-Namen eingeben!');
      const slug = document.querySelector('input[name=slug]').value;

      // Array von {name, order}
      const abteilungen = [...tbody.querySelectorAll('select')]
        .map((s, idx) => s.value ? { name: s.value, order: idx + 1 } : null)
        .filter(Boolean);

      const res = await fetch('speichere_supermarkt.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ slug, marktName, abteilungen })
      });
      const json = await res.json();
      if (json.success) {
        const filename = json.file || (json.slug + '.json');
        alert('Markt aktualisiert als „' + filename + '“');
        // window.location.href = 'bearbeiten.php?markt=' + encodeURIComponent(json.slug);
      }
    });
  </script>
</body>
</html>
