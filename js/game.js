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
let piece = document.querySelector('#piece-img');

// état pour le drag tactile
let touchDragging = null;
let touchGhost = null;

// Fonctions utilitaires
function createNewPiece () {
    const img = document.createElement('img');
    if (playerNum !== 1 && playerNum !== 2) {
        console.error('playerNum contient une valeur non-autorisée: '.concat(playerNum));
    }
    const pieceType = playerNum === 1 ? 'cross' : 'circle';
    img.src = `./assets/${pieceType}.png`;
    img.alt = `pièce: ${pieceType}`;
    img.draggable = true;
    img.classList.add('piece-img');
    attachPieceEvents(img);

    return img;
    }

function attachPieceDragEvents(img) {
    img.addEventListener('dragstart', (event) => {
        console.log('dragstart triggered for piece', img);
        try {
            event.dataTransfer.setData('text/plain', 'piece');
            event.dataTransfer.effectAllowed = 'move';
        } catch (e) {
            console.warn('dataTransfer inaccessible:', e);
        }
    });

    // Touch fallback handlers
    img.addEventListener('touchstart', (event) => {
        if (event.touches.length > 1) return;
        event.preventDefault();
        const t = event.touches[0];
        touchDragging = img;
        // créer un fantôme (ghost) qui suit le doigt
        touchGhost = img.cloneNode(true);
        touchGhost.style.position = 'fixed';
        touchGhost.style.width = img.getBoundingClientRect().width + 'px';
        touchGhost.style.height = img.getBoundingClientRect().height + 'px';
        touchGhost.style.left = (t.clientX - touchGhost.offsetWidth / 2) + 'px';
        touchGhost.style.top = (t.clientY - touchGhost.offsetHeight / 2) + 'px';
        touchGhost.style.pointerEvents = 'none';
        touchGhost.style.opacity = '0.9';
        touchGhost.style.zIndex = 9999;
        document.body.appendChild(touchGhost);
    }, {passive: false});

    img.addEventListener('touchmove', (event) => {
        if (!touchDragging || !touchGhost) return;
        const t = event.touches[0];
        event.preventDefault();
        touchGhost.style.left = (t.clientX - touchGhost.offsetWidth / 2) + 'px';
        touchGhost.style.top = (t.clientY - touchGhost.offsetHeight / 2) + 'px';
    }, {passive: false});

    img.addEventListener('touchend', (event) => {
        if (!touchDragging) return;
        const touch = event.changedTouches[0];
        // déterminer la cellule sous le doigt
        const el = document.elementFromPoint(touch.clientX, touch.clientY);
        const targetCell = el ? el.closest('td[data-cell]') : null;
        if (targetCell) {
            playOnCell(targetCell.dataset.cell, targetCell, touchDragging);
        }
        // nettoyage
        if (touchGhost && touchGhost.parentNode) touchGhost.parentNode.removeChild(touchGhost);
        touchGhost = null;
        touchDragging = null;
    });
}

function attachPieceEvents(img) {
    attachPieceDragEvents(img);
}

function printError (error) {
    const p = document.createElement('p');
    p.innerText = error;
    p.classList.add('error-p');
    document.body.appendChild(p);
    setTimeout(() => {
        p.remove()
    }, 3000);
}

// Envoyer une requete au serveur quand l'user joue un coup.
cells.forEach(cell => {
    cell.addEventListener('dragover', (event) => {
        event.preventDefault();
    });

    cell.addEventListener('drop', (event) => {
        event.preventDefault();
        const cellId = cell.dataset.cell;

        fetch(`/api.php?action=play&user_id=${userId}&game_id=${gameId}&cell_id=${cellId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.succes) {
                cell.appendChild(piece);
                piece = createNewPiece();
                pieceContainer.appendChild(piece);
            } else if (data.error) {
                printError(data.error);
            } else {
                console.error('réponse de l\'api sans succes ni error');
            }
        })
        .catch((error) => {
            printError('Erreur réseau: ' + error.message);
        });
    });
});

attachPieceEvents(piece);

// playOnCell extrait pour réutilisation (touch + drop)
function playOnCell(cellId, cellElem, draggedPiece) {
    fetch(`/api.php?action=play&user_id=${userId}&game_id=${gameId}&cell_id=${cellId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.succes) {
            if (draggedPiece && cellElem) {
                cellElem.appendChild(draggedPiece);
            }
            piece = createNewPiece();
            pieceContainer.appendChild(piece);
        } else if (data.error) {
            printError(data.error);
        } else {
            console.error('réponse de l\'api sans succes ni error');
        }
    })
    .catch((error) => {
        printError('Erreur réseau: ' + error.message);
    });
}
