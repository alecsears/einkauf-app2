<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Super-Einkaufs App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">


<!-- Icons-->
   <link rel="icon" type="image/png" href="/einkauf-app/assets/icons/icon-16.png">
     <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/einkauf-app/assets/icons/icon-192.png">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="manifest" href="/einkauf-app/assets/manifest.json">
<meta name="theme-color" content="#4B15DA">

  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f5f5f5;
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48;
      font-size: 48px;
      color: #2196F3;
    }
    .card-md {
      transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .card-md:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    .icon-gradient {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px; height: 64px;
  border-radius: 50%;
 background: #4B15DA;

  /* Passe die Farben ggf. an den tatsächlichen SVG-Verlauf an */
  box-shadow: 0 2px 8px rgba(40,40,40,0.12);
  margin-right: 1rem;
  flex-shrink: 0;
}
.icon-gradient .material-symbols-outlined {
  color: #23af64;
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
   <!--  <p class="text-muted mb-0">Die aktuelle Liste von Produkten</p> -->
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
                 <!--  <p class="text-muted mb-0">Aktuelle und verganene Menüs</p> -->
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
                 <!--  <p class="text-muted mb-0">Rezepte wählen und Einkaufsiste erstellen</p> -->
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
              <!--     <p class="text-muted mb-0">Produkte, Rezepte und Märkte bearbeiten</p> -->
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
             <!--      <p class="text-muted mb-0">Was wird am häufigsten gekocht?</p> -->
              </div>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Test -->
      <div class="col-12 col-md-6 col-lg-4">
        <a href="kartons/index.php" class="text-decoration-none">
          <div class="card card-md p-4 bg-white">
            <div class="d-flex align-items-center">
                  <span class="icon-gradient">
              <span class="material-symbols-outlined">package_2</span>
               </span>
              <div>
                <h5 class="text-dark mb-1">Karton-Labels</h5>
               <!--    <p class="text-muted mb-0">Kartons dokumentieren</p> -->
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
