<?php
// Daten empfangen
$data = json_decode(file_get_contents('php://input'), true);

$markt = $data['markt'] ?? '';
$abteilungen = $data['abteilungen'] ?? [];

// Datei-Pfad anpassen, falls nötig!
$filepath = "maerkte/abteilungen.txt";

// Optional: Marktname als erste Zeile wieder speichern (wie beim Einlesen)
$inhalt = $markt . "\n" . implode("\n", $abteilungen);

// Datei speichern
$erfolg = file_put_contents($filepath, $inhalt);

header('Content-Type: application/json');
echo json_encode(['success' => $erfolg !== false]);