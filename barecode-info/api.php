<?php
header('Content-Type: application/json');
require 'config.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'getPending') {
    // Récupère les codes-barres Laserdisc sans infos
    try {
        $stmt = $pdo->query('SELECT * FROM laserdiscs WHERE titre IS NULL OR titre = "" LIMIT 10');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'searchEAN') {
    $ean = $_POST['ean'] ?? '';
    
    if (empty($ean)) {
        echo json_encode(['success' => false, 'error' => 'EAN vide']);
        exit;
    }
    
    // Appel à l'API EAN-Search
    $url = 'https://api.eandata.com/validate/' . urlencode($ean);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Code EAN non trouvé']);
    }
    exit;
}

if ($action === 'save') {
    $ean = $_POST['ean'] ?? '';
    $titre = $_POST['titre'] ?? '';
    $auteur = $_POST['auteur'] ?? '';
    $annee = $_POST['annee'] ?? '';
    $resolution = $_POST['resolution'] ?? '';
    $son = $_POST['son'] ?? '';
    $edition = $_POST['edition'] ?? '';
    $etat = $_POST['etat'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $ext1 = $_POST['ext1'] ?? '';
    $ext2 = $_POST['ext2'] ?? '';
    $ext3 = $_POST['ext3'] ?? '';
    
    try {
        $stmt = $pdo->prepare('INSERT INTO laserdiscs 
                             (ean, titre, auteur, annee, resolution, son, edition, etat, notes, ext1, ext2, ext3) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                             titre=?, auteur=?, annee=?, resolution=?, son=?, edition=?, etat=?, notes=?, ext1=?, ext2=?, ext3=?');
        $stmt->execute([$ean, $titre, $auteur, $annee, $resolution, $son, $edition, $etat, $notes, $ext1, $ext2, $ext3,
                       $titre, $auteur, $annee, $resolution, $son, $edition, $etat, $notes, $ext1, $ext2, $ext3]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'list') {
    try {
        $stmt = $pdo->query('SELECT * FROM laserdiscs ORDER BY date_ajout DESC LIMIT 50');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
?>