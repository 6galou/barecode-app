<?php
// Configuration MySQL/PDO pour MAMP - Laserdiscs
define('DB_HOST', 'localhost');
define('DB_NAME', 'laserdisc_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// API EAN-Search
define('EAN_API_URL', 'https://api.eandata.com/validate');
define('EAN_API_KEY', ''); // À compléter avec ta clé API (optionnel pour version gratuite)

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=3306",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connexion échouée: " . $e->getMessage());
}
?>