<?php
session_start();

// Der Hidden-Input im Formular heißt "deletefile"
$datei = isset($_POST['deletefile']) ? basename($_POST['deletefile']) : '';

$rezeptePfad = __DIR__ . '/rezepte/' . $datei;
$bildSlug    = pathinfo($datei, PATHINFO_FILENAME);
$bildWebp    = __DIR__ . '/bilder/' . $bildSlug . '.webp';
$bildJpg     = __DIR__ . '/bilder/' . $bildSlug . '.jpg';

if ($datei) {
    // Rezeptdatei löschen
    if (file_exists($rezeptePfad)) {
        unlink($rezeptePfad);
    }
    // WebP-Bild löschen
    if (file_exists($bildWebp)) {
        unlink($bildWebp);
    }
    // Altes JPG-Bild löschen (Bestandsdaten)
    if (file_exists($bildJpg)) {
        unlink($bildJpg);
    }
}

// Zurück zur Übersicht (jetzt rezeptkasten_j.php!)
header('Location: rezeptauswahl.php');
exit;