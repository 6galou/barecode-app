<?php
// Script d'installation - créer la base et les tables
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3306",
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Crée la base
    $pdo->exec("CREATE DATABASE IF NOT EXISTS laserdisc_db");
    echo "✅ Base de données 'laserdisc_db' créée!<br><br>";
    
    // Connecte-toi à la base et crée la table
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=laserdisc_db",
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $sql = "CREATE TABLE IF NOT EXISTS laserdiscs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ean VARCHAR(20) NOT NULL UNIQUE,
        titre VARCHAR(255),
        auteur VARCHAR(255),
        annee INT,
        resolution VARCHAR(20),
        son VARCHAR(50),
        edition VARCHAR(100),
        etat VARCHAR(50),
        notes TEXT,
        ext1 TEXT,
        ext2 TEXT,
        ext3 TEXT,
        date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_modif TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Table 'laserdiscs' créée avec succès!<br>";
    echo "🎉 Installation terminée! Accès à l'app: <a href='index.html'>ici</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>