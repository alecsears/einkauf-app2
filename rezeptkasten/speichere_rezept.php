<?php
function slugify($text) {
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  return strtolower($text ?: 'rezept-' . time());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rezeptauswahl.php');
    exit;
}

$rezeptname      = trim($_POST['rezeptname']      ?? '');
$zubereitung     = trim($_POST['zubereitung']     ?? '');
$kalorien        = $_POST['kalorien']             ?? 0;
$vegetarisch     = isset($_POST['vegetarisch'])   ? 'ja' : 'nein';
$frequenz        = (int)($_POST['frequenz']        ?? 0);
$bildGespeichert = trim($_POST['bild_gespeichert'] ?? '');
$editdatei       = basename($_POST['editdatei']   ?? '');

if ($rezeptname === '') {
    header('Location: rezepteditor.php');
    exit;
}

// Sanitise bild_gespeichert: only allow "bilder/<slug>.webp" or "bilder/<slug>.jpg"
if ($bildGespeichert !== '' && !preg_match('/^bilder\/[a-z0-9\-]+\.(webp|jpg)$/', $bildGespeichert)) {
    $bildGespeichert = '';
}

$slug = slugify($rezeptname);

$rezeptePfad = __DIR__ . '/rezepte/';
if (!is_dir($rezeptePfad)) {
    mkdir($rezeptePfad, 0775, true);
}

// Build zutaten array from parallel POST arrays
$zutatenNamen = $_POST['zutat']    ?? [];
$zutatenIds   = $_POST['zutat_id'] ?? [];
$mengen       = $_POST['menge']    ?? [];
$einheiten    = $_POST['einheit']  ?? [];

$zutaten = [];
$count   = count($zutatenNamen);
for ($i = 0; $i < $count; $i++) {
    $id      = $zutatenIds[$i] ?? '';
    $menge   = str_replace(',', '.', $mengen[$i]   ?? '0');
    $einheit = $einheiten[$i] ?? '';
    if ($id !== '') {
        $zutaten[] = [
            'id'            => is_numeric($id) ? (int)$id : $id,
            'rezeptmenge'   => is_numeric($menge) ? (float)$menge : 0,
            'rezepteinheit' => $einheit,
        ];
    }
}

// Load existing data when editing (to preserve fields not in the form)
$existingData = [];
$targetFile   = $slug . '.json';

if ($editdatei !== '') {
    $editPfad = $rezeptePfad . $editdatei;
    if (file_exists($editPfad)) {
        $existingData = json_decode(file_get_contents($editPfad), true) ?? [];
    }
    // Keep the original filename unless the slug changed
    $oldSlug = pathinfo($editdatei, PATHINFO_FILENAME);
    if ($slug === $oldSlug) {
        $targetFile = $editdatei;
    }
    // If slug changed the old file will be deleted below
}

// Build recipe JSON (match existing format)
$rezept = [
    'name'         => $rezeptname,
    'zutaten'      => $zutaten,
    'zubereitung'  => $zubereitung,
    'vegetarisch'  => $vegetarisch,
    'hauptgericht' => $existingData['hauptgericht'] ?? 'nein',
    'kalorien'     => is_numeric($kalorien) ? (int)$kalorien : 0,
    'frequenz'     => $frequenz,
    'bild'         => $bildGespeichert !== '' ? $bildGespeichert : ($existingData['bild'] ?? ''),
    'rezept-id'    => $existingData['rezept-id'] ?? uniqid('r', true),
];

$savePath = $rezeptePfad . $targetFile;
file_put_contents($savePath, json_encode($rezept, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Remove old file if the recipe was renamed
if ($editdatei !== '' && $targetFile !== $editdatei) {
    $oldPath = $rezeptePfad . $editdatei;
    if (file_exists($oldPath)) {
        unlink($oldPath);
    }
}

header('Location: rezeptauswahl.php');
exit;
