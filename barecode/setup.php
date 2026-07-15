<?php
// D'abord crée la base de données
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3306",
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Crée la base
    $pdo->exec("CREATE DATABASE IF NOT EXISTS barcode_db");
    echo "✅ Base de données 'barcode_db' créée!<br><br>";
    
    // Maintenant connecte-toi à la base et crée la table
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=barcode_db",
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $sql = "CREATE TABLE IF NOT EXISTS barcodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_barre VARCHAR(255) NOT NULL UNIQUE,
        type_disque VARCHAR(100),
        quantite INT DEFAULT 1,
        description TEXT,
        date_lecture TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Table 'barcodes' créée avec succès!<br>";
    echo "🎉 Installation terminée! Accès à l'app: <a href='index.html'>ici</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
