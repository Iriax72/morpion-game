/*
js/menu.js

Code javascript pour le menu (menu.php)
Gère les événements onclick des bouttons
Vérifie qu'on est pas appelé pour une partie et redirige vers la partie (/game.php) si c'est la cas.
Le php a fourni une variable userId qui est un token unique pour chaque user, à utiliser pour les requetes fetch.
*/

// Référemces DOM:
const createGameBtn = document.querySelector('#create-game-btn');
const joinGameBtn = document.querySelector('#join-game-btn');

// Fonctions utilitaires:
function createPopup(content = []) {
    // le paramètre content peut être un tableau d'éléments DOM, de strings, ou contenir 'CROSS_BTN'
    const div = document.createElement('div');
    div.classList.add('popup');
    let addCrossBtn = false;

    content.forEach(element => {
        // gérer la valeur 'CROS_BTN'
        if (element === 'CROSS_BTN') {
            addCrossBtn = true;
            return;
        }
        // gérer les string
        if (typeof element === 'string') {
            const textNode = document.createTextNode(element);
            div.appendChild(textNode);
            return;
        }
        // gérer les élements HTML
        if (element instanceof Node) {
            div.appendChild(element);
            return;
        }
        // gérer tout le reste
        console.warn('createPopup: élément non pris en charge', element);
    });

    if (addCrossBtn) {
        const crossBtn = document.createElement('button');
        crossBtn.innerText = 'x';
        crossBtn.classList.add('cross-btn');
        crossBtn.addEventListener('click', () => {
            document.body.removeChild(div);
        });
        div.appendChild(crossBtn);
    }

    return div;
}

// Event listeners:
createGameBtn.addEventListener('click', () => {
    // afficher message d'attente
    let popup = createPopup(['En attente de votre code d\'accès...']);
    document.body.appendChild(popup);

    // demander un token au serveur
    fetch(`/api.php?action=create_game_token&user_id=${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const token = data.token;

        // construire le bouton annuler qui utilisera le token reçu
        const annuler = document.createElement('button');
        annuler.innerText = 'Annuler';
        annuler.classList.add('popup-btn');
        annuler.addEventListener('click', () => {
            fetch(`/api.php?action=cancel_game&user_id=${userId}&token=${token}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (popup && popup.parentNode) popup.parentNode.removeChild(popup);
                } else if (result.error) {
                    console.error('Erreur lors de l\'annulation de la partie:', result.error);
                } else {
                    console.error('Réponse inatendue de l\'api:', result);
                }
            });
        });

        // remplacer le popup d'attente par le popup contenant le token et le bouton annuler
        if (popup && popup.parentNode) popup.parentNode.removeChild(popup);
        popup = createPopup([`Code d'acces: ${token}`, annuler]);
        document.body.appendChild(popup);
    })
    .catch(err => {
        console.error('Erreur lors de la création du token:', err);
        if (popup && popup.parentNode) popup.parentNode.removeChild(popup);
        const errPopup = createPopup(['Erreur lors de la création de la partie.']);
        document.body.appendChild(errPopup);
    });
});

joinGameBtn.addEventListener('click', () => {
    // demander le token au user
    const label = document.createElement('label');
    label.setAttribute('for', 'game-token');
    label.innerText = 'Code d\'accès:';
    label.classList.add('popup-label')

    const input = document.createElement('input');
    input.setAttribute('type', 'text');
    input.setAttribute('id', 'game-token');
    input.setAttribute('name', 'game-token');
    input.classList.add('popup-input');

    const btn = document.createElement('button');
    btn.innerText = 'Rejoindre';
    btn.classList.add('popup-btn');
    btn.addEventListener('click', () => {
        const token = input.value;

        // envoyer une requête pour rejoindre la partie
        fetch(`/api.php?action=join_game&user_id=${userId}&token=${token}`, {
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createPopup(['La partie va bientôt commencer...']);
            } else if (data.error){
                createPopup(['CROSS_BTN', data.error]);
            } else {
                console.error('Unexpected response from server:', data);
            }
        });
    });
    btn.classList.add('popup-btn');

    const popup = createPopup(['CROSS_BTN', label, input, btn]);
    document.body.appendChild(popup);
});

// Vérifier qu'on est pas appelé pour une partie
const eventSource = new EventSource('/stream.php');

eventSource.addEventListener('game_start', (event) => {
    window.location.href = '/game.php?game_id=' + JSON.parse(event.data).game_id;
})