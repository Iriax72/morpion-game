/*
js/game.js

Code js pour la page de la partie (game.php)
Gère les interactions avec le plateau de jeu
Envoie des requêtes à l'api pour faire des actions de jeu
Écoute les notifications du serveur pour mettre à jour le plateau de jeu en temps reel
Le php a fourni une variable gameId qui indique l'id de la partie en cours
Il a aussi fourni userId qui est un token unique pour chaque user, à utiliser pour les requêtes fetch.
*/

// Références DOM:
const gameBoard = document.getElementById('game-board');
const cells = document.querySelectorAll('td[data-cell]');