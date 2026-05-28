<?php
// index.php

// PAGE ROUTEUR
// pas de HTML
// Redirection de l'user vers la bonne password_get_info

session_start();
$_SESSION['user_id'] = bin2hex(random_bytes(16));

require_once __DIR__ . '/config.php';
try {
    init_db();
} catch (Throwable $e) {
    die('<p style="color: red;">Erreur d\'initialisation de la base de données: ' . $e->getMessage() . '</p>');
}

header('Location: ./menu.php');
exit;
?>