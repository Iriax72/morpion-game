<?php
/*
stream.php

Ce fichier envoie les notifications en temps réel aux clients
Il s'occupe des SSE 
*/

// enlever les warning qui pourraient corrompre le json
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

// Inclure le config pour la db
require_once __DIR__ . '/config.php';
$pdo = get_db_connection();

// les headers nécessaires aux SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// désacitver la limite de temps d'execution du script
set_time_limit(0);

console_log('Sream SSE démarré !');
// boucle infinie pour envoyer les notifs en temps réel
while (true) {
    // Tout le commentaire qui suit est un ancien essait de code. Il ne sera plus mais est conservé en cas de nelcrsité de revenir en arriere.
    /*
    $games = $pdo->query('SELECT * FROM games WHERE player2 IS NOT NULL');

    // passer la boucle si aucune partie n'est en cours
    if ($games->rowCount() === 0) {
        sleep(15);
        continue;
    }
    foreach ($games as $game) {
        // Récupérer les notifications relatives à la partie
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE game_id = ?');
        $stmt->execute([$game['id']]);
        $relative_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // passer la boucle s'il n'y a pas de notif pour cette partie
        if (count($relative_notifications) === 0) {
            continue;
        }
        
        //envoyer les notifs aux clients
        foreach ($relative_notifications as $notification) {
            if ($notification['notification_type'] === 'game_start') {
                echo "event: game_start\n";
            }
            $data = array(
                'timestamp' => time(),
                'message' => $notification['notification_type'],
                'data' => json_decode($notification['notification_data'], true),
                'notified_by' => $notification['notified_by'],
                'notified_to' => isset($notification['notified_to']) ? json_decode($notification['notified_to'], true) : null,
            );
            echo "data: " . json_encode($data) . "\n\n";
        }

        // supprimer les notif de la db pour ne pas la surcharger
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE game_id = ?');
        $stmt->execute([$game['id']]);
    }
    */

    //Récuperer toutes le notifs
    $stmt = $pdo->query('SELECT * FROM notifications');
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notifications as $notification) {
        if ($notification['notification_type'] === 'game_start') {
            echo "event: game_start\n";
        }
        $data = array(
            'timestamp' => time(),
            'data' => json_decode($notification['notification_data'], true),
            'notified_by' => $notification['notified_by'],
            'notified_to' => json_decode($notification['notified_to'], true)
        );
        echo "data: " . json_encode($data) . "\n\n";
        console_log('Notif envoyée: ' . json_encode($data));
    }
    // supprimer les notifs de la db pour ne pas la surcharger
    $pdo->query('DELETE FROM notifications');

    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();

    // laisser respirer le CPU
    sleep(4);
}
?>