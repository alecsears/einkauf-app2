<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$input = json_decode(file_get_contents('php://input'), true);

// Bulk-Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['bulkUpdate'])) {
    try {
        $pdo->beginTransaction();
        foreach ($input['data'] as $row) {
            $feld = $row['field'];
            $wert = $row['value'];
            if (!preg_match('/^[a-z_]+$/', $feld)) throw new Exception("Ungültiges Feld: $feld");
            $sql = "UPDATE produkte SET `$feld` = :wert WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':wert' => $wert, ':id' => $row['id']]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Produkt speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($input['neu'])) {
        // Validierung
        $errors = [];

        // Pflichtfelder
        $name = trim($input['name'] ?? '');
        $rezepteinheit = $input['rezepteinheit'] ?? '';
        $grundeinheit = $input['grundeinheit'] ?? '';
        $verpackungseinheit = $input['verpackungseinheit'] ?? '';
        $supermarktmenge = isset($input['supermarktmenge']) ? intval($input['supermarktmenge']) : 0;
        $abteilung = $input['abteilung'] ?? '';
        $produktart = $input['produktart'] ?? '';
        $standard = (isset($input['standard']) && $input['standard'] === 'ja') ? 'ja' : 'nein';

        // Name eindeutig?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM produkte WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) $errors[] = ['field' => 'name', 'msg' => 'Name existiert bereits'];

        // Menge > 0
        if ($supermarktmenge <= 0) $errors[] = ['field' => 'supermarktmenge', 'msg' => 'Menge muss größer 0 sein'];

        // Dropdowns gewählt?
        if ($name === '') $errors[] = ['field' => 'name', 'msg' => 'Name darf nicht leer sein'];
        if ($rezepteinheit === '') $errors[] = ['field' => 'rezepteinheit', 'msg' => 'Rezepteinheit muss gewählt sein'];
        if ($grundeinheit === '') $errors[] = ['field' => 'grundeinheit', 'msg' => 'Grundeinheit muss gewählt sein'];
        if ($verpackungseinheit === '') $errors[] = ['field' => 'verpackungseinheit', 'msg' => 'Packungsart muss gewählt sein'];
        if ($abteilung === '') $errors[] = ['field' => 'abteilung', 'msg' => 'Abteilung muss gewählt sein'];
        if ($produktart === '') $errors[] = ['field' => 'produktart', 'msg' => 'Produktart muss gewählt sein'];

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Insert
        $stmt = $pdo->prepare("INSERT INTO produkte 
            (name, rezepteinheit, grundeinheit, verpackungseinheit, supermarktmenge, abteilung, standard, produktart) 
            VALUES (:name, :rez, :grd, :verp, :menge, :abt, :standard, :art)");
        $stmt->execute([
            ':name' => $name,
            ':rez' => $rezepteinheit,
            ':grd' => $grundeinheit,
            ':verp' => $verpackungseinheit,
            ':menge' => $supermarktmenge,
            ':abt' => $abteilung,
            ':standard' => $standard,
            ':art' => $produktart,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    } elseif (isset($input['id'], $input['field'])) {
        // Einzelnes Feld aktualisieren
        $feld = $input['field'];
        $wert = $input['value'];
        if (!preg_match('/^[a-z_]+$/', $feld)) {
            echo json_encode(['error' => 'Ungültiges Feld']);
            exit;
        }
        $sql = "UPDATE produkte SET `$feld` = :wert WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':wert' => $wert, ':id' => $input['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Produkt löschen
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($input['id'])) {
    $stmt = $pdo->prepare("DELETE FROM produkte WHERE id = ?");
    $stmt->execute([$input['id']]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Methode nicht erlaubt']);