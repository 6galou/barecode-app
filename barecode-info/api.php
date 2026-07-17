<?php
header('Content-Type: application/json');
require 'config.php';  // base Laserdisc

$action = $_REQUEST['action'] ?? '';

// === LOGGING ===
define('LOG_FILE', __DIR__ . '/api_logs.txt');

function logAPI($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= " | " . json_encode($data);
    }
    $logMessage .= "\n";
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

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
    
    // Nettoie le EAN
    $ean = preg_replace('/[^0-9]/', '', $ean);
    
    if (strlen($ean) < 8) {
        echo json_encode(['success' => false, 'error' => 'EAN invalide (minimum 8 chiffres)']);
        exit;
    }
    
    logAPI("Recherche EAN", ['ean' => $ean]);
    
    // Essaie plusieurs APIs EAN
    $data = searchEANFromAPIs($ean);
    
    if ($data) {
        logAPI("EAN trouvé", ['ean' => $ean, 'source' => $data['source']]);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        logAPI("EAN non trouvé", ['ean' => $ean]);
        echo json_encode(['success' => false, 'error' => 'Code EAN non trouvé dans les bases']);
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
        logAPI("Laserdisc sauvegardé", ['ean' => $ean, 'titre' => $titre]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        logAPI("Erreur save", ['ean' => $ean, 'error' => $e->getMessage()]);
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

if ($action === 'import_barcodes') {
    header('Content-Type: application/json; charset=utf-8');

    logAPI("Début import_barcodes");

    // Vérifie que la connexion principale ($pdo) provenant de config.php existe
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        logAPI("Erreur: Connexion principale manquante");
        echo json_encode(['error' => 'Connexion principale ($pdo) manquante. Vérifiez config.php.']);
        exit;
    }

    // --- Connexion à la base barecode-db (table `barcodes`) ---
    $barcodeHost = 'localhost';
    $barcodePort = '3306';
    $barcodeDb   = 'barcode_db';
    $barcodeUser = 'root';
    $barcodePass = 'root';

    $dsn_barcode = "mysql:host={$barcodeHost};port={$barcodePort};dbname={$barcodeDb};charset=utf8mb4";
    try {
        $pdo_barcode = new PDO($dsn_barcode, $barcodeUser, $barcodePass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        logAPI("Connexion à barcode_db réussie");
    } catch (PDOException $e) {
        http_response_code(500);
        logAPI("Erreur connexion barcode_db", ['error' => $e->getMessage()]);
        echo json_encode(['error' => 'Connexion à barecode-db échouée: ' . $e->getMessage()]);
        exit;
    }

    // Lecture des codes depuis la table barcodes
    try {
        $selSql = "SELECT DISTINCT TRIM(`code_barre`) AS ean
                   FROM `barcodes`
                   WHERE `code_barre` IS NOT NULL AND TRIM(`code_barre`) <> ''";
        $stmt = $pdo_barcode->query($selSql);
        logAPI("Requête barcodes exécutée");
    } catch (PDOException $e) {
        http_response_code(500);
        logAPI("Erreur lecture barcodes", ['error' => $e->getMessage()]);
        echo json_encode(['error' => 'Lecture des barcodes échouée: ' . $e->getMessage()]);
        exit;
    }

    // Prépare l'insert sur la table laserdiscs
    try {
        $insertStmt = $pdo->prepare("INSERT IGNORE INTO `laserdiscs` (`ean`) VALUES (:ean)");
    } catch (PDOException $e) {
        http_response_code(500);
        logAPI("Erreur préparation INSERT", ['error' => $e->getMessage()]);
        echo json_encode(['error' => 'Préparation INSERT échouée: ' . $e->getMessage()]);
        exit;
    }

    // Boucle d'insertion en transaction
    try {
        $pdo->beginTransaction();
        $inserted = 0;
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ean = $row['ean'];
            if ($ean === '') continue;
            $insertStmt->execute([':ean' => $ean]);
            $inserted += $insertStmt->rowCount();
            $count++;
        }
        $pdo->commit();

        logAPI("Import_barcodes complété", ['codes_traités' => $count, 'insérés' => $inserted]);
        echo json_encode(['status' => 'ok', 'inserted' => $inserted]);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        logAPI("Erreur import transaction", ['error' => $e->getMessage()]);
        echo json_encode(['error' => 'Import échoué: ' . $e->getMessage()]);
        exit;
    }
}

// === LOGS ===
if ($action === 'getLogs') {
    if (!file_exists(LOG_FILE)) {
        echo json_encode(['logs' => 'Aucun log disponible']);
        exit;
    }
    $logs = file_get_contents(LOG_FILE);
    $lines = array_reverse(explode("\n", $logs));
    echo json_encode(['logs' => implode("\n", array_slice($lines, 0, 50))]);
    exit;
}

echo json_encode(['error' => 'Action inconnue']);

// ===== FONCTIONS UTILITAIRES =====

/**
 * Recherche les infos EAN depuis plusieurs APIs
 */
function searchEANFromAPIs($ean) {
    // 1. Essaie api.eandata.com (gratuit)
    $result = searchEANData($ean);
    if ($result) return $result;
    
    // 2. Essaie api.barcodable.com (gratuit, pas de clé requise)
    $result = searchBarcodable($ean);
    if ($result) return $result;
    
    // 3. Essaie openfoodfacts.org (open data, pas de clé requise)
    $result = searchOpenFoodFacts($ean);
    if ($result) return $result;
    
    return null;
}

/**
 * API eandata.com
 */
function searchEANData($ean) {
    logAPI("Appel API eandata.com", ['ean' => $ean]);
    
    $url = 'https://api.eandata.com/validate/' . urlencode($ean);
    
    $response = makeRequest($url);
    if (!$response) {
        logAPI("eandata.com: pas de réponse");
        return null;
    }
    
    $data = json_decode($response, true);
    logAPI("eandata.com réponse reçue", array_keys($data ?? []));
    
    // Vérifie si la réponse est valide
    if (isset($data['isValid']) && $data['isValid']) {
        return [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'brand' => $data['brand'] ?? '',
            'category' => $data['category'] ?? '',
            'image' => $data['image'] ?? '',
            'source' => 'eandata.com'
        ];
    }
    
    logAPI("eandata.com: EAN invalide ou non trouvé");
    return null;
}

/**
 * API Barcodable.com (gratuit, pas de clé)
 */
function searchBarcodable($ean) {
    logAPI("Appel API barcodable.com", ['ean' => $ean]);
    
    $url = 'https://api.barcodable.com/api/lookup?barcode=' . urlencode($ean);
    
    $response = makeRequest($url);
    if (!$response) {
        logAPI("barcodable.com: pas de réponse");
        return null;
    }
    
    $data = json_decode($response, true);
    logAPI("barcodable.com réponse reçue", isset($data['success']) ? 'success' : 'fail');
    
    if (isset($data['success']) && $data['success'] && isset($data['products'])) {
        $product = $data['products'][0] ?? null;
        if ($product) {
            return [
                'name' => $product['name'] ?? $product['title'] ?? '',
                'description' => $product['description'] ?? '',
                'brand' => $product['brand'] ?? '',
                'category' => $product['category'] ?? '',
                'image' => $product['image'] ?? '',
                'manufacturer' => $product['manufacturer'] ?? '',
                'source' => 'barcodable.com'
            ];
        }
    }
    
    logAPI("barcodable.com: produit non trouvé");
    return null;
}

/**
 * API OpenFoodFacts.org (open data)
 */
function searchOpenFoodFacts($ean) {
    logAPI("Appel API openfoodfacts.org", ['ean' => $ean]);
    
    $url = 'https://world.openfoodfacts.org/api/v0/product/' . urlencode($ean) . '.json';
    
    $response = makeRequest($url);
    if (!$response) {
        logAPI("openfoodfacts.org: pas de réponse");
        return null;
    }
    
    $data = json_decode($response, true);
    logAPI("openfoodfacts.org réponse reçue", isset($data['product']) ? 'produit trouvé' : 'pas de produit');
    
    if (isset($data['product'])) {
        $product = $data['product'];
        
        // Récupère tous les champs disponibles
        return [
            'name' => $product['product_name'] ?? '',
            'description' => $product['generic_name'] ?? '',
            'brand' => $product['brands'] ?? '',
            'category' => $product['categories'] ?? '',
            'manufacturer' => $product['manufacturers'] ?? '',
            'image' => $product['image_front_url'] ?? '',
            'year' => isset($product['created_t']) ? date('Y', $product['created_t']) : '',
            'barcode' => $product['barcode'] ?? '',
            'quantity' => $product['quantity'] ?? '',
            'countries' => $product['countries'] ?? '',
            'source' => 'openfoodfacts.org'
        ];
    }
    
    logAPI("openfoodfacts.org: produit non trouvé");
    return null;
}

/**
 * Effectue une requête HTTP GET avec timeout et gestion d'erreur
 */
function makeRequest($url, $timeout = 5) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return $response;
    }
    
    if ($error) {
        logAPI("Erreur curl", ['url' => $url, 'error' => $error]);
    }
    
    return null;
}
?>
