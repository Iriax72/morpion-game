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
    // le paramètre content doit etre un tableau d'éléments DOM ou strings ou la valeur 'CROSS_BTN'
    const div = document.createElement('div');
    div.classList.add('popup');
    content.forEach(element => {
        div.appendChild(element); // TODO: sécuriser en cas d'injection malveillante
    });

    if ('CROSS_BTN' in content) {
        const crossBtn = document.createElement('btn');
        crossBtn.innerText = 'x';
        crossBtn.classList.add('cross-Btn');
        crossBtn.addEventListener('click', () => {
            document.body.removeChild(div);
        });
        div.appendChild(crossBtn);
    }
    return div;
}

// Event listeners:
createGameBtn.addEventListener('click', () => {
    const popup = createPopup(['En attente de votre code d\'accès...']);
    document.body.appendChild(popup);
    
    // damande un token au serveur
    fetch(`/api.php?action=create_game_token&user_id=${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const token = data.token;
        popup.innerText = `Code d'acces: ${token}`;
    });
});

joinGameBtn.addEventListener('click', () => {
    // demander le token au user
    const label = document.createElement('label');
    label.setAttribute('for', 'game-token');
    label.innerText = 'Coded\'accès:';
    label.classList.add('popup-label')

    const input = document.createElement('input');
    input.setAttribute('type', 'text');
    input.setAttribute('id', 'game-token');
    input.setAttribute('name', 'game-token');
    input.classList.add('popup-input');

    const btn = document.createElement('button');
    btn.innerText = 'Rejoindre';
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