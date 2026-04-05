<?php
// Datenbankverbindung (einmalig zentral speichern)

$host = 'daniel-a-becker.de.mysql';
$db   = 'daniel_a_becker_de';
$user = 'daniel_a_becker_de';
$pass = 'o$Mb?9a93#tR';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Verbindung fehlgeschlagen: " . $e->getMessage();
    exit;
}
