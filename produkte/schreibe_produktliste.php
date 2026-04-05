<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

$produktlistePfad = __DIR__ . '/produktliste.json';

function json_error(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function norm_name(string $s): string {
    $s = trim($s);
    // Normalisieren (Mehrfachspaces reduzieren)
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return $s;
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    json_error('Leerer Request-Body');
}

$in = json_decode($raw, true);
if (!is_array($in)) {
    json_error('Ungültiges JSON');
}

/**
 * Pflichtfelder (dein aktuelles Modal)
 * vorratsort ist optional (wird aber gespeichert)
 */
$required = [
    'name',
    'rezepteinheit',
    'grundeinheit',
    'verpackungseinheit',
    'supermarktmenge',
    'abteilung',
    'standard',
    'produktart'
];

foreach ($required as $key) {
    if (!array_key_exists($key, $in)) json_error("Feld fehlt: $key");
    if (is_string($in[$key]) && trim($in[$key]) === '') json_error("Feld leer: $key");
}

$name = norm_name((string)$in['name']);
if ($name === '') json_error('Feld leer: name');

// Produktliste laden (falls nicht vorhanden -> leeres Objekt)
$liste = [];
if (is_file($produktlistePfad) && is_readable($produktlistePfad)) {
    $rawFile = file_get_contents($produktlistePfad);
    if ($rawFile !== false && trim($rawFile) !== '') {
        $decoded = json_decode($rawFile, true);
        if (is_array($decoded)) {
            $liste = $decoded;
        }
    }
}

// Falls Datei aus Versehen als Liste (numerisch) vorliegt -> versuchen zu mappen
if ($liste !== [] && array_keys($liste) === range(0, count($liste) - 1)) {
    $mapped = [];
    foreach ($liste as $row) {
        if (is_array($row) && isset($row['name'])) {
            $k = norm_name((string)$row['name']);
            if ($k !== '') $mapped[$k] = $row;
        }
    }
    $liste = $mapped;
}

// a) Name muss einzigartig sein (case-insensitive)
foreach ($liste as $existName => $_) {
    if (mb_strtolower((string)$existName) === mb_strtolower($name)) {
        json_error('Produktname existiert bereits', 409);
    }
}

// b) freie ID finden (kleinste positive Integer, der nicht genutzt ist)
$usedIds = [];
foreach ($liste as $prod) {
    if (is_array($prod) && isset($prod['id'])) {
        $idNum = (int)$prod['id'];
        if ($idNum > 0) $usedIds[$idNum] = true;
    }
}
$id = 1;
while (isset($usedIds[$id])) $id++;

// standard: "ja"/"nein" normalisieren
$standard = (string)$in['standard'];
$standard = trim($standard);

if ($standard !== 'ja' && $standard !== 'nein') {
    // Checkbox/boolean mapping
    if ($standard === '1' || $standard === 'true' || $standard === 'TRUE' || $standard === 'on') {
        $standard = 'ja';
    } else {
        $standard = 'nein';
    }
}

// Optional: vorratsort (wenn nicht gesendet -> leerer String)
$vorratsort = '';
if (array_key_exists('vorratsort', $in)) {
    $vorratsort = (string)$in['vorratsort'];
    $vorratsort = trim($vorratsort);
}

// Datensatz bauen (entspricht deiner Struktur)
$produkt = [
    'id'                 => $id,
    'rezepteinheit'      => (string)$in['rezepteinheit'],
    'grundeinheit'       => (string)$in['grundeinheit'],
    'supermarktmenge'    => (string)$in['supermarktmenge'],  // als String wie in deinem Beispiel
    'abteilung'          => (string)$in['abteilung'],
    'produktart'         => (string)$in['produktart'],       // "Food" / "Non-Food"
    'standard'           => $standard,                       // "ja" | "nein"
    'verpackungseinheit' => (string)$in['verpackungseinheit'],
    'name'               => $name,
    'vorratsort'         => $vorratsort,
];

// In Liste einsetzen: Key = Produktname
$liste[$name] = $produkt;

// Schreiben
$out = json_encode($liste, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($out === false) json_error('JSON-Serialisierung fehlgeschlagen', 500);

if (file_put_contents($produktlistePfad, $out, LOCK_EX) === false) {
    json_error('Schreiben der produktliste.json fehlgeschlagen', 500);
}

echo json_encode(
    ['ok' => true, 'produkt' => $produkt, 'name' => $name],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
