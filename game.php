<?php
/*
game.php

Page de la partie
game-board est la div qui contient le plateau de jeu où le js va injecter les X et les O.
Cette page envoie les requêtes à l'api pour faire des action de jeu, et écoute les notifications du stream pour mettre à jour le plateau
*/
require_once __DIR__ . '/config.php';
$pdo = get_db_connection();

if (session_status() === PHP_SESSION_NULL) {
    session_start();
    $_SESSION['user_id'] = bin2hex(random_bytes(16));

    try {
        $stmt = $pdo->prepare('INSERT  INTO users (id) VALUES (?)');
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        die('<p style="color: red;">Erreur lors de la création de l\'utilisateur: ' . $e->getMessage() . '</p>');
    }
}

require_once __DIR__ . '/stream.php';

if (!isset($_GET['game_id']) || !isset($_GET['user_id'])) {
    http_response_code(400);
    echo '<p style="color: red;">game_id ou user_id manquant</p>';
    exit;
}

// Récuperer la partie
$stmt = $pdo->prepare('SELECT * FROM games WHERE id = ?');
$stmt->execute([$_GET['game_id']]);
$game = $stmt->fetch();

// vérifier que la partie existe et que le client en fait partie
if (!game) {
    http_response_code(404);
    echo '<p style="color: red;">Partie non trouvée</p>';
    exit;
}
if ($game['player2'] !== $_GET['user_id'] && $game['created_by'] !== $_GET['user_id']) {
    http_response_code(403);
    echo '<p style="color: red;">Vous ne faites pas partie de cette partie</p>';
    exit;
}

// Numéro du joueur (1 ou 2)
$player_num = $game['created_by'] === $_GET['user_id'] ? 1 : 2;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partie de Morpiong</title>
    <link rel="stylesheet" href="./css/game.css">
    <script>
        // fournir le game_id au client
        const gameId = <?= $_GET['game_id']; ?>;
        const userId = '<?= $_GET['user_id']; ?>'; // TODO: sécuriser cela: n'importe qui peut se faire passer pour n'importe qui !
    </script>
    <script src="./js/game.js" defer></script>
</head>
<body>
    <h1>Partie de Morpiong</h1>
    <div id="score">
        <span id="player1-score">0</span> - <span id="player2-score">0</span>
    </div>
    <div id="game-board">
        <table>
            <tr>
                <td data-cell="0"></td>
                <td data-cell="1"></td>
                <td data-cell="2"></td>
            </tr>
            <tr>
                <td data-cell="3"></td>
                <td data-cell="4"></td>
                <td data-cell="5"></td>
            </tr>
            <tr>
                <td data-cell="6"></td>
                <td data-cell="7"></td>
                <td data-cell="8"></td>
            </tr>
        </table>
    </div>
    <div id="pieces">
        <?php 
        if ($player_num === 1) {
            echo '<image src="./assets/cross.png" alt="Cross" id="cross-piece" draggable="true">';
        } else {
            echo '<image src="./assets/circle.png" alt="Circle" id="cicle-piece" draggable="true">';
        }
        ?>
    </div>
</body>
</html>