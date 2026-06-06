/*
js/game.js

Code js pour la page de la partie (game.php)
Gère les interactions avec le plateau de jeu
Envoie des requêtes à l'api pour faire des actions de jeu
Écoute les notifications du serveur pour mettre à jour le plateau de jeu en temps reel
Le php a fourni une variable gameId qui indique l'id de la partie en cours
Il a aussi fourni userId qui est un token unique pour chaque user, à utiliser pour les requêtes fetch.
Il a aussi fourne playerNum qui indique si le joueur est le joueur 1 ou 2.
*/

// Références DOM:
const gameBoard = document.getElementById('game-board');
const cells = document.querySelectorAll('td[data-cell]');
const pieceContainer = document.querySelector('#piece-container');
const piece = document.querySelector('#piece-img');

// Fonctions utilitaires:
/*
function createNewPiece () {
    const img = document.createElement('img');
    if (playerNum === 1) {
        img.src = './assets/circle.png';
        img.alt = 'pièce: circle';
    } else if (playerNum === 2) {
        img.src = '/assets/cross.png';
        img.alt = 'pièce: cross';
    } else {
        console.error('playerNum contient une valeur non-autorisée: '.concat(playerNum));
    }
    img.draggable = true;
    img.classList.add('piece-img');

    return img;
}
*/
function printError (error) {
    const p = document.createElement('p');
    p.innerText = error;
    p.classList.add('error-p');
    document.body.appendChild(p);
}

//test pour voir si la fonction marche biengg
printError('Ceci est juste un test');


// Envoyer une requete au serveur quand l'user joue un coup.
cells.forEach(cell => {
    cell.addEventListener('dragover', (event) => {
        const cellId = cell.dataset.cell;
        fetch(`/api.php?action=play&user_id=${userId}&game_id=${gameId}&cell_id=${cellId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json)
        .then(data => {
            if (data.succes) {
                event.preventDefault();
                cell.appendChild(piece);
                piece = createNewPiece();
                pieceContainer.appendChild(piece);
            } else if (data.error) {
                printError(data.error);
            } else {
                console.error('réponse de l\'api sans succes ni error');
            }
        });
    });
});