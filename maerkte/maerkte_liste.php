<?php
// Gibt alle .json-Dateien aus dem Verzeichnis maerkte/lokationen/ als Array zurück
header('Content-Type: application/json');

$verzeichnis = __DIR__ . '/lokationen/';
if (!is_dir($verzeichnis)) {
    echo json_encode([]);
    exit;
}

$files = array_values(array_filter(
    scandir($verzeichnis),
    function($f) use ($verzeichnis) {
        return is_file($verzeichnis . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
    }
));

echo json_encode($files);