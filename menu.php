<?php
// menu.php

// page du menu avec boutons
// btn 1: créer une game
// btn 2: rejoindre une game

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $_SESSION['user_id'] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouez au morpion !</title>
    <link rel="stylesheet" href="./css/menu.css">
    <script>
        const userId = '<?= $_SESSION['user_id'] ?>';
    </script>
    <script src="./js/menu.js" defer></script>
</head>
<body>
    <button id="create-game-btn" class="animated-btn">CRÉER UNE PARTIE</button>
    <button id="join-game-btn" class="animated-btn">REJOINDRE UNE PARTIE</button>
</body>
</html>