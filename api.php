<?php
/*
api.php

Ce fichier ne retourne pas de HTML, mais uniquement des données en json
Méthodes disponibles:
- POST /api.php?action=create_game_token&user_id=USER_ID
    => Crée une nouvelle partie et un token pour s'y connecter
! Faille de sécurité : n'inporte qui leur créer une partie au nom d'un autre user en indiquant son id.
TODO: régler ça en utilisant le systeme de session

- POST /api.php?action=join_game&token=TOKEN
    => Rejoindre la partie existante avec le TOKEN
*/

//enlever les warning qui pourrait corrompre le json
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try{

switch($action) {
    
    case 'create_game_token':
        if (!isset($_REQUEST['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id manquant']);
            exit;
        }
        $user_id = $_REQUEST['user_id'];

        //recupère la pdo
        $pdo = get_db_connection();

        //récupere les token déjà existants:
        $stmt = $pdo->query('SELECT token FROM games');
        $existing_tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        //crée un nouveau token unique
        while (!$token || in_array($token, $existing_tokens)) {
            $token = bin2hex(random_bytes(6));
        }
        //insère le token dans la db
        try {
            $stmt = $pdo->prepare('INSERT INTO games (token, created_by) VALUES (?, ?)');
            $stmt->execute([$token, $user_id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
            exit;
        }
        echo json_encode(['success' => true, 'token' => $token]);
        break;
}
//attraper toutes les erreurs imprévues
} catch (Throwable $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}