<?php
session_start();
require_once __DIR__ . '/config/config.php';
define('COOKIE_VALUE', hash('sha256', PASSWORT));

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
  <link rel="icon" type="image/png" href="assets/icons/icon-16.png">
  <link rel="apple-touch-icon" sizes="57x57" href="assets/icons/icon-57.png">
<link rel="apple-touch-icon" sizes="60x60" href="assets/icons/icon-60.png">
<link rel="apple-touch-icon" sizes="72x72" href="assets/icons/icon-72.png">
<link rel="apple-touch-icon" sizes="76x76" href="assets/icons/icon-76.png">
<link rel="apple-touch-icon" sizes="114x114" href="assets/icons/icon-114.png">
<link rel="apple-touch-icon" sizes="120x120" href="assets/icons/icon-120.png">
<link rel="apple-touch-icon" sizes="144x144" href="assets/icons/icon-144.png">
<link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/icons/icon-180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="assets/icons/icon-192.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/icons/icon-32.png">
<link rel="icon" type="image/png" sizes="96x96" href="assets/icons/icon-96.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/icons/icon-x16.png">
<meta name="msapplication-TileImage" content="assets/icons/icon-144.png">
<link rel="manifest" href="assets/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="theme-color" content="#388e3c">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="assets/tokens.css">
  <style>
    body { font-family: var(--font-family-base); background-color: var(--color-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center;}
    .card-md { transition: var(--transition-card); border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-card); max-width: 400px; width: 100%;}
    .card-md:hover { transform: translateY(-4px); box-shadow: var(--shadow-card-hover);}
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48; font-size: var(--font-size-icon-lg); color: var(--color-info); margin-bottom: 8px;}
    .error { color: #c00; font-size: var(--font-size-base); margin-bottom: var(--space-lg);}
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