<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Super-Einkaufs App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">


<!-- Icons-->
   <link rel="icon" type="image/png" href="assets/icons/icon-16.png">
     <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="assets/icons/icon-192.png">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="manifest" href="assets/manifest.json">
<meta name="theme-color" content="#4B15DA">
  <link rel="stylesheet" href="assets/tokens.css">

  <style>
    body {
      font-family: var(--font-family-base);
      background-color: var(--color-bg);
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48;
      font-size: var(--font-size-icon-lg);
      color: var(--color-info);
    }
    .card-md {
      transition: var(--transition-card);
      border: none;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-card);
    }
    .card-md:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-card-hover);
    }
    .icon-gradient {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--icon-circle-size); height: var(--icon-circle-size);
  border-radius: var(--radius-circle);
 background: var(--color-primary);

  /* Passe die Farben ggf. an den tatsächlichen SVG-Verlauf an */
  box-shadow: var(--shadow-icon);
  margin-right: var(--space-lg);
  flex-shrink: 0;
}
.icon-gradient .material-symbols-outlined {
  color: var(--color-accent);
  font-size: 28px;
}

  </style>
</head>
<body>
  <div class="container py-5">
  
    <h1 class="text-center mb-5">Willkommen!</h1>
    <div class="row g-4 justify-content-center">

      <!-- Aktuelle Einkaufsliste -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="wochenplanung/03_liste.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
  <span class="icon-gradient">
    <span class="material-symbols-outlined">receipt_long</span>
  </span>
  <div>
    <h5 class="text-dark mb-1">Einkaufsliste</h5>
  </div>
</div>
          </div>
        </a>
      </div>

 <!-- Wochenmenüs -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="wochenmenu.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
                  <span class="icon-gradient">
              <span class="material-symbols-outlined">menu_book</span>
               </span>
              <div>
                <h5 class="text-dark mb-1">Wochenmenüs</h5>
              </div>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Neuer Wocheneinkauf -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="wochenplanung/01_waehlen.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
                 <span class="icon-gradient">
             <span class="material-symbols-outlined">shopping_cart</span>
               </span>
              <div>
                <h5 class="text-dark mb-1">Neuer Wocheneinkauf</h5>
              </div>
            </div>
          </div>
        </a>
      </div>

     

      <!-- Bearbeiten -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="edit.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
                  <span class="icon-gradient">
               <span class="material-symbols-outlined">edit</span>
                </span>
              <div>
                <h5 class="text-dark mb-1">Bearbeiten</h5>
              </div>
            </div>
          </div>
        </a>
      </div>
       <!-- Stats -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="statistik.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
                  <span class="icon-gradient">
               <span class="material-symbols-outlined">bar_chart</span>
                </span>
              <div>
                <h5 class="text-dark mb-1">Statistiken</h5>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Design System -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="design-system.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
              <span class="icon-gradient">
                <span class="material-symbols-outlined">palette</span>
              </span>
              <div>
                <h5 class="text-dark mb-1">Design System</h5>
              </div>
            </div>
          </div>
        </a>
      </div>

    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
