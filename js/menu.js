/*
js/menu.js

code javascript pour le menu (menu.php)
gère les événements onclick des bouttons
Le php a fourni une variable userId qui est un token unique pour chaque user, à utiliser pour les requetes fetch.
*/

// Référemces DOM:
const createGameBtn = document.querySelector('#create-game-btn');
const joinGameBtn = document.querySelector('#join-game-btn');

// Event listeners:
createGameBtn.addEventListener('click', () => {
    const div = document.createElement('div');
    div.innerText = 'En attante de votre code d\'accès...';

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
        div.innerText = `Code d'acces: ${token}`;
    });

    div.classList.add('access-code-div');
        
    const crossBtn = document.createElement('button');
    crossBtn.innerText = 'x';
    crossBtn.classList.add('cross-btn');
    crossBtn.addEventListener('click', () => {
        document.body.removeChild(div);
        document.body.removeChild(crossBtn);
    });
    div.appendChild(crossBtn);
    document.body.appendChild(div);
    document.body.appendChild(crossBtn);
});

joinGameBtn.addEventListener('click', () => {
    // TODO: demander le token
    // faire la requete api grace au token
})