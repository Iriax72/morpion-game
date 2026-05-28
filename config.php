<?php
/*
config.php

Ce fichier contient la configuration de la db.
Il doit etre inclus une fois avant toute utilisation de la db
*/

//variables de connexion à la db
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_USER', getenv('DB_USERNAME'));
define('DB_PASS', getenv('DB_PASSWORD'));
define('DB_CHARSET', 'utf8mb4');

//retourne un connexion partagée (singleton)
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

// fonction d'initialisation, appellée une fois au début de l'application
function init_db(): void {
    $pdo = get_db_connection();

    //table users (id, connected_at)
    $pdo->exec('
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY,
        connected_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );');

    //table games (id, created_at, created_by, player2)
    $pdo->exec('
    CREATE TABLE IF NOT EXISTS games (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(12) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by INT NOT NULL,
        player2 INT DEFAULT NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (player2) REFERENCES users(id)
    );');
}
?>