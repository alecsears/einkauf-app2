<?php
header('Content-Type: application/json');

// Lese JSON-Payload
$data = json_decode(file_get_contents('php://input'), true);
$slug        = $data['slug'] ?? '';
$marktName   = trim($data['marktName'] ?? '');
$abteilungen = $data['abteilungen'] ?? [];

// Validierung
if (!$marktName || !is_array($abteilungen)) {
    echo json_encode(['success'=>false, 'error'=>'Ungültige Daten']);
    exit;
}

// Erzeuge slug, falls leer
if ($slug === '') {
    $slug = preg_replace('/[^a-z0-9\-]/i', '-', strtolower($marktName));
    $slug = trim($slug, '-');
}

// Speicherpfad (jetzt .json statt .txt)
$dir  = __DIR__ . '/lokationen';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$file = $dir . "/{$slug}.json";

// Schreibe Datei im neuen JSON-Format
$saveData = [
    'name'        => $marktName,
    'abteilungen' => $abteilungen, // das ist ein Array von Objekten (name, order)
];
if (file_put_contents($file, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    echo json_encode(['success'=>false, 'error'=>'Konnte Datei nicht schreiben']);
    exit;
}

// Rückmeldung
echo json_encode([
    'success' => true,
    'file'    => "{$slug}.json",
    'slug'    => $slug
]);
