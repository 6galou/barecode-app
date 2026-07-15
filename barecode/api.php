<?php
header('Content-Type: application/json');
require 'config.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
    $code = $_POST['code'] ?? '';
    $type = $_POST['type'] ?? '';
    $quantite = $_POST['quantite'] ?? 1;
    $description = $_POST['description'] ?? '';

    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Code vide']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO barcodes (code_barre, type_disque, quantite, description) 
                             VALUES (?, ?, ?, ?)');
        $stmt->execute([$code, $type, $quantite, $description]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
            echo json_encode(['success' => false, 'error' => 'Ce code-barre existe déjà']);
        } else {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

if ($action === 'list') {
    $stmt = $pdo->query('SELECT * FROM barcodes ORDER BY date_lecture DESC');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    $stmt = $pdo->prepare('DELETE FROM barcodes WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
?>