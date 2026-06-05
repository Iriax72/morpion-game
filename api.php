<?php
/*
api.php

Ce fichier ne retourne pas de HTML, mais uniquement des données en json
Méthodes disponibles:
- POST /api.php?action=create_game_token&user_id=USER_ID
    => Crée une nouvelle partie et un token pour s'y connecter
! Faille de sécurité : n'inporte qui leur créer une partie au nom d'un autre user en indiquant son id.
TODO: régler ça

- POST /api.php?action=cancel_game&user_id=USER_ID&token=TOKEN
    => Annule la partie en cours correspondante au token, seulement si USER_ID en est le créateur
! Faille de sécurité : n'importe qui peut annuler n'importe quelle partie en se faisant passer pour son créateur (TODO)

- POST /api.php?action=join_game&user_id=USER_ID&token=TOKEN
    => Rejoindre la partie existante avec le TOKEN
! Faille de sécurité : n'importe qui peut rejoindre n'inoorte quelle partie en se faisant passer pour un autre user (TODO)

- POST /api.php?action=read_notification&user_id=USER_ID&notification_id=NOTIFICATION_ID
    => Marque la notif comme lue dans la db
! Faille de sécurité : n'importe qui peut marquer une notif comme lue en se faisant passer pour un autre user qui ne ressevra alors jamais sa notif (TODO)
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

    case 'cancel_game':
        if (!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id ou token manquant']);
            exit;
        }
        $user_id = $_REQUEST['user_id'];
        $token = $_REQUEST['token'];

        //récupère la pdo
        $pdo = get_db_connection();

        $stmt = $pdo->prepare('SELECT id, created_by FROM games WHERE token = ?');
        $stmt->execute([$token]);
        $game = $stmt->fetch();

        if (!$game) {
            http_response_code(404);
            echo json_encode(['error' => 'Partie non trouvée']);
            exit;
        }
        if ($game['created_by'] !== $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Vous n\'avez pas l\'autorisation d\'annuler cette partie']);
            exit;
        }
        // empecher de supprimer si il y a des notifs relatives à la partie (previent les SQLState[23000])
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE game_id = ?');
        $stmt->execute([$game['id']]);
        $notif_count = $stmt->fetchColumn();
        if ($notif_count > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Imossible de supprimer la partie, il y a des notifications associées']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM games WHERE id = ?');
            $stmt->execute([$game['id']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => true]);
        break;
    
    case 'join_game':
        if (!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id ou token non fourni']);
            exit;
        }
        $user2_id = $_REQUEST['user_id'];
        $token = $_REQUEST['token'];

        //récupère la pdo
        $pdo = get_db_connection();

        //cherche la partie correspondante au token
        $stmt = $pdo->prepare('SELECT id, created_by FROM games WHERE token = ?');
        $stmt->execute([$token]);
        $game = $stmt->fetch();

        if (!$game) {
            http_response_code(404);
            echo json_encode(['error' => 'Partie non trouvée']);
            exit;
        }

        // ajoute le joueur 2 à la partie
        try {
            $stmt = $pdo->prepare('UPDATE games SET player2 = ? WHERE id = ? AND player2 IS NULL');
            $stmt->execute([$user2_id, $game['id']]);
            if ($stmt->rowCount() === 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Impossible de rejoindre la partie, elle est déjà complète']);
                exit;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => true]);

        // commencer la partie entre les deux joueurs
        $user1_id = $game['created_by'];
        // $user2_id est déjà défini plus haut

        try{
            $stmt = $pdo->prepare('INSERT INTO notifications (game_id, notification_type, notification_data, notified_by, notified_to, read_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $game['id'],
                'game_start',
                json_encode(['game_id' => $game['id']]),
                $user2_id,
                json_encode([$user1_id, $user2_id]),
                json_encode([]) // personne n'a encore lu au début
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            console_log('Erreur lors de la création de la notification game_start: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
            exit;
        }
        break;
    
    case 'read_notification':
        if (!isset($_REQUEST['user_id']) || !isset($_REQUEST['notification_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id ou notification_id non fourni']);
            exit;
        }
        $user_id = $_REQUEST['user_id'];
        $notification_id = $_REQUEST['notification_id'];

        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id = ?');
        $stmt->execute([$notification_id]);
        $notif  = $stmt->fetch();

        if (!$notif) {
            http_response_code(404);
            echo json_encode(['error' => 'Notification non trouvée']);
            exit;
        }
        // Vérifier que l'user à la droit de lire la notif et ne l'a pas déjà lue
        if (!in_array($user_id, json_decode($notif['notified_to'], true))) {
            http_response_code(403);
            echo json_encode(['error' => 'Vous n\'avez pas l\'autorisation de lire cette notification']);
            exit;
        }
        if (in_array($user_id, json_decode($notification['read_by'], true))) {
            http_response_code(409);
            echo json_encode(['error' => 'Notification déjà lue']);
            exit;
        }

        // récupère la pdo
        $pdo = get_db_connection();

        try {
            $stmt = $pdo->prepare('UPDATE notifications SET read_by = read_by + ? WHERE id = ?');
            $stmt->execute([json_encode([$user_id]), $notification_id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
            exit;
        }
        echo json_encode(['success' => true]);
        break;
}
//attraper toutes les erreurs imprévues
} catch (Throwable $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}