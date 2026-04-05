<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Inhalte editieren</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="assets/tokens.css">
  <link href="assets/style.css" rel="stylesheet">
  <style>
    /* Kacheln in edit.php: schmal & rechteckig, kein aspect-ratio */
    .card-md.card {
      aspect-ratio: unset !important;
      flex-direction: row !important;
      align-items: center !important;
      justify-content: flex-start !important;
      padding: 1rem 1.25rem !important;
    }
  </style>
</head>
<body>
  
  <div class="sticky-header d-flex align-items-center">
    <a href="start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
  </div>
  <div class="container py-5">
    <div class="row g-4 justify-content-center">
      <!-- Rezeptkasten -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="rezeptkasten/rezeptauswahl.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
              <span class="icon-gradient">
                <span class="material-symbols-outlined">chef_hat</span>
              </span>
              <div>
                <h5 class="text-dark mb-1">Rezeptkasten</h5>
              </div>
            </div>
          </div>
        </a>
      </div>
      <!-- Produktliste -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="produkte/produkte-editor.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
              <span class="icon-gradient">
                <span class="material-symbols-outlined">barcode</span>
              </span>
              <div>
                <h5 class="text-dark mb-1">Produktliste</h5>
              </div>
            </div>
          </div>
        </a>
      </div>
      <!-- Märkte -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="maerkte/marktuebersicht.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
              <span class="icon-gradient">
                <span class="material-symbols-outlined">storefront</span>
              </span>
              <div>
                <h5 class="text-dark mb-1">Märkte</h5>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>