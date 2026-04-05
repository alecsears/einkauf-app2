<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Marktübersicht</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    /* Optional: etwas konsistenter und touchfreundlicher */
    .card-link { text-decoration: none; color: inherit; }
    .logo-wrapper { display:flex; align-items:center; justify-content:center; min-height:110px; }
    .card { border-radius: 18px; }
    .card-title { text-align:center; }
    .new-card {
      border: 2px dashed rgba(0,0,0,.25);
      background: rgba(0,0,0,.02);
      transition: transform .05s ease;
    }
    .new-card:active { transform: scale(0.99); }
  </style>
</head>
<body>

  <div class="sticky-header d-flex align-items-center">
    <a href="../edit.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined" style="font-size:24px;">arrow_back</span>
    </a>
  </div>

  <div class="container py-4">
    <div class="row g-4">

      <!-- NEU: Kachel "Markt anlegen" -->
      <div class="col-md-4 col-sm-6">
        <a href="markt_erstellen.php" class="card-link" title="Neuen Markt anlegen">
          <div class="card shadow-sm h-100 new-card">
            <div class="logo-wrapper">
              <span class="material-symbols-outlined" style="font-size:64px;color:#4B15DA;">add_business</span>
            </div>
            <h5 class="card-title mt-2">Neuen Markt anlegen</h5>
          </div>
        </a>
      </div>

      <?php
      function markt_logo_svg($name) {
        $name_lower = mb_strtolower($name);
        if (strpos($name_lower, 'aldi') !== false)
          return 'logos/aldi.svg';
        if (strpos($name_lower, 'rewe') !== false)
          return 'logos/rewe.svg';
        // Weitere Märkte hier ergänzen...
        return null;
      }

      foreach (glob('lokationen/*.json') as $file):
        $json = json_decode(file_get_contents($file), true);
        if (!$json || empty($json['name'])) continue;
        $name_raw = (string)$json['name'];
        $name = htmlspecialchars($name_raw, ENT_QUOTES, 'UTF-8');
        $slug = basename($file, '.json');
        $logo_svg = markt_logo_svg($name_raw);
      ?>
        <div class="col-md-4 col-sm-6">
          <a href="marktdetails.php?markt=<?= urlencode($slug) ?>" class="card-link">
            <div class="card shadow-sm h-100">
              <div class="logo-wrapper">
                <?php if ($logo_svg && file_exists(__DIR__ . '/' . $logo_svg)): ?>
                  <?php readfile(__DIR__ . '/' . $logo_svg); ?>
                <?php else: ?>
                  <span class="material-symbols-outlined" style="font-size:56px;color:#4B15DA;">storefront</span>
                <?php endif; ?>
              </div>
              <h5 class="card-title mt-2"><?= $name ?></h5>
            </div>
          </a>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
