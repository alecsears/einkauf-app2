<?php
header('Content-Type: application/json');

// Hilfsfunktion: Zeitstempel für Dateinamen
function getTimestampFilename() {
   return 'rezeptwoche_' . date('d-m-y_H-i-s') . '.json';
}

// 1. Nur POST akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Nur POST-Anfragen erlaubt!']);
    exit;
}

// 2. JSON-Daten einlesen und prüfen
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['message' => 'Ungültige oder fehlende JSON-Daten!']);
    exit;
}

// Erwartet werden zwei Elemente: rezepte und einkaufsliste
if (!isset($data['rezepte']) || !isset($data['einkaufsliste'])) {
    http_response_code(400);
    
    echo json_encode(['message' => 'Strukturfehler: rezepte und einkaufsliste werden benötigt!']);
    exit;
}

// --- 1. Rezepte speichern --- //
$rezepte = $data['rezepte'];
$menuhistorieDir = __DIR__ . '/menuhistorie';
if (!is_dir($menuhistorieDir)) {
    http_response_code(500);
    echo json_encode(['message' => 'Verzeichnis menuhistorie fehlt!']);
    exit;
}
$rezepteFilename = $menuhistorieDir . '/' . getTimestampFilename();

if (file_put_contents($rezepteFilename, json_encode($rezepte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['message' => 'Fehler beim Speichern der Rezepte!']);
    exit;
}

// --- 2. Einkaufsliste speichern (altes "offen" beibehalten) --- //
$einkaufslisteFilename = __DIR__ . '/einkaufsliste.json';

// Neue Einträge aus Request
$neueOffen = array_values(array_filter(
    $data['einkaufsliste'],
    function($item) {
        return isset($item['einkaufslistenmenge']) && floatval($item['einkaufslistenmenge']) != 0;
    }
));

// Vorherige "offen"-Elemente laden
$alteOffen = [];
if (file_exists($einkaufslisteFilename)) {
    $alteJson = json_decode(file_get_contents($einkaufslisteFilename), true);
    if (is_array($alteJson) && isset($alteJson['offen']) && is_array($alteJson['offen'])) {
        $alteOffen = $alteJson['offen'];
    }
}

// Hilfsfunktion: Gibt einen eindeutigen Schlüssel für einen Eintrag zurück (z.B. nach "id" oder "name")
function getItemKey($item) {
    // Passe das an deine Struktur an! Beispiel: nach Name und ggf. Einheit oder ID
    return isset($item['id']) ? $item['id'] : (isset($item['name']) ? $item['name'] : md5(json_encode($item)));
}

// Neue Einträge als assoziatives Array (key => item)
$neueOffenAssoc = [];
foreach ($neueOffen as $item) {
    $neueOffenAssoc[getItemKey($item)] = $item;
}

// Alte, nicht mehr vorhandene Einträge beibehalten
foreach ($alteOffen as $item) {
    $key = getItemKey($item);
    if (!isset($neueOffenAssoc[$key])) {
        $neueOffenAssoc[$key] = $item;
    }
}

// Endgültiges "offen"-Array
$finalOffen = array_values($neueOffenAssoc);

$einkaufslisteJson = [
    'offen' => $finalOffen,
    'erledigt' => []
];

if (file_put_contents($einkaufslisteFilename, json_encode($einkaufslisteJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['message' => 'Fehler beim Speichern der Einkaufsliste!']);
    exit;
}
// --- 4. Statistik der gekochten Rezepte --- //
$statsFile = __DIR__ . '/stats.json';

// Laden oder Initialisieren der Statistik
$stats = [
    'rezepte' => [],
    'gesamt_anzahl' => 0,
    'anzahl_menues' => 0
];
if (file_exists($statsFile)) {
    $statsJson = json_decode(file_get_contents($statsFile), true);
    if (is_array($statsJson)) {
        $stats = $statsJson + $stats; // bewahrt ggf. neue Felder
    }
}

// Für jedes ausgewählte Rezept den Zähler hochzählen
$rezepteDir = __DIR__ . '/../rezeptkasten/rezepte/';
foreach ($rezepte as $slug) {
    $rezeptFile = $rezepteDir . $slug . '.json';
    if (file_exists($rezeptFile)) {
        $rjson = json_decode(file_get_contents($rezeptFile), true);
        $rezeptName = $rjson['name'] ?? $slug;
        if (!isset($stats['rezepte'][$rezeptName])) {
            $stats['rezepte'][$rezeptName] = ['anzahl' => 1];
        } else {
            $stats['rezepte'][$rezeptName]['anzahl'] += 1;
        }
        $stats['gesamt_anzahl'] += 1;
    }
}

// --- NEU: Gesamtzähler aller ausgewählten Rezepte ("anzahl_menues") hochzählen ---
$anzahlNeueRezepte = is_array($rezepte) ? count($rezepte) : 0;
$stats['anzahl_menues'] = (isset($stats['anzahl_menues']) ? (int)$stats['anzahl_menues'] : 0) + $anzahlNeueRezepte;

// Speichern der Statistik
file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
// --- 3. Signal fürs Frontend, den Local Storage zu löschen --- //
echo json_encode([
    'success' => true,
    'message' => 'Rezepte und Einkaufsliste erfolgreich gespeichert!',
    'localStorageClear' => true
]);
exit;
?>