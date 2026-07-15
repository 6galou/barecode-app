<?php
// Configuration MySQL/PDO pour MAMP
define('DB_HOST', 'localhost');
define('DB_NAME', 'barcode_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

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
