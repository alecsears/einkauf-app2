<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

define('PASSWORT', 'Rezepte123!');
define('COOKIE_NAME', 'superapp_loggedin');
define('COOKIE_VALUE', hash('sha256', PASSWORT));
define('COOKIE_LIFETIME', 60*60*24*30); // 30 Tage

// Logout-Mechanismus
if (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    setcookie(COOKIE_NAME, '', time()-3600, "/");
    header('Location: login.php');
    exit;
}

// Prüfe auf Cookie, falls Session nicht gesetzt
if (empty($_SESSION['logged_in']) && !empty($_COOKIE[COOKIE_NAME])) {
    if ($_COOKIE[COOKIE_NAME] === COOKIE_VALUE) {
        $_SESSION['logged_in'] = true;
        header('Location: start.php');
        exit;
    }
}

// Login-Formular abgeschickt?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pwd'])) {
    if ($_POST['pwd'] === PASSWORT) {
        $_SESSION['logged_in'] = true;
        // "Angemeldet bleiben" aktiviert?
        if (!empty($_POST['remember'])) {
            setcookie(COOKIE_NAME, COOKIE_VALUE, time()+COOKIE_LIFETIME, "/");
        }
        header('Location: start.php');
        exit;
    } else {
        $error = 'Falsches Passwort!';
    }
}

// Zugriffsprüfung: wenn nicht eingeloggt, Login-Formular anzeigen
if (empty($_SESSION['logged_in'])):
?>
<!DOCTYPE html>
<html lang="de">
<head>
      <meta charset="UTF-8">
  <title>Login | Super-Einkaufs App</title>
  
  <!-- Icons -->
  <link rel="icon" type="image/png" href="/einkauf-app/assets/icons/icon-16.png">
  <link rel="apple-touch-icon" sizes="57x57" href="/einkauf-app/assets/icons/icon-57.png">
<link rel="apple-touch-icon" sizes="60x60" href="/einkauf-app/assets/icons/icon-60.png">
<link rel="apple-touch-icon" sizes="72x72" href="/einkauf-app/assets/icons/icon-72.png">
<link rel="apple-touch-icon" sizes="76x76" href="/einkauf-app/assets/icons/icon-76.png">
<link rel="apple-touch-icon" sizes="114x114" href="/einkauf-app/assets/icons/icon-114.png">
<link rel="apple-touch-icon" sizes="120x120" href="/einkauf-app/assets/icons/icon-120.png">
<link rel="apple-touch-icon" sizes="144x144" href="/einkauf-app/assets/icons/icon-144.png">
<link rel="apple-touch-icon" sizes="152x152" href="/einkauf-app/assets/icons/icon-152.png">
<link rel="apple-touch-icon" sizes="180x180" href="/einkauf-app/assets/icons/icon-180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="/einkauf-app/assets/icons/icon-192.png">
<link rel="icon" type="image/png" sizes="32x32" href="/einkauf-app/assets/icons/icon-32.png">
<link rel="icon" type="image/png" sizes="96x96" href="/einkauf-app/assets/icons/icon-96.png">
<link rel="icon" type="image/png" sizes="16x16" href="/einkauf-app/assets/icons/icon-x16.png">
<meta name="msapplication-TileImage" content="/einkauf-app/assets/icons/icon-144.png">
<link rel="manifest" href="/einkauf-app/assets/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="theme-color" content="#388e3c">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    body { font-family: 'Roboto', sans-serif; background-color: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center;}
    .card-md { transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out; border: none; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); max-width: 400px; width: 100%;}
    .card-md:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0,0,0,0.2);}
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48; font-size: 48px; color: #2196F3; margin-bottom: 8px;}
    .error { color: #c00; font-size: 1rem; margin-bottom: 1rem;}
    password-toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 28px;
  color: #666;
  user-select: none;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 26px;        /* überschreibt die 48px */
  color: #666;
  user-select: none;
  margin-bottom: 0;       /* überschreibt die 8px */
  line-height: 1;
}

.password-toggle:hover {
  color: #000;
}

  </style>
</head>
<body>
  <div class="card card-md p-4 bg-white mx-auto text-center">
    <span class="material-symbols-outlined">lock</span>
    <h2 class="mb-3 text-success">Super-Einkaufs App</h2>
    <h5 class="mb-3 text-dark">Login</h5>
    <?php if (!empty($error)): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <div class="mb-3 position-relative">
  <input
    type="password"
    class="form-control pe-5"
    id="password"
    name="pwd"
    placeholder="Passwort"
    autofocus
    required
  >
  <span
    class="material-symbols-outlined password-toggle"
    id="togglePassword"
    title="Passwort anzeigen"
  >
    visibility
  </span>
</div>

      <div class="mb-3 text-start">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Angemeldet bleiben</label>
      </div>
      <button type="submit" class="btn btn-success w-100 mb-2">Anmelden</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const pwdInput = document.getElementById('password');
  const toggle = document.getElementById('togglePassword');

  toggle.addEventListener('click', () => {
    const isPassword = pwdInput.type === 'password';
    pwdInput.type = isPassword ? 'text' : 'password';
    toggle.textContent = isPassword ? 'visibility_off' : 'visibility';
  });
</script>

</body>
</html>
<?php
exit;
endif;

// Falls eingeloggt, weiterleitung zu start.php
header('Location: start.php');
exit;
?>