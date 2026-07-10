const form = document.getElementById('form-login');
const mensagem = document.getElementById('mensagem');

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    mensagem.textContent = 'Entrando...';

    const dados = {
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value,
    };

    const resposta = await fetch('index.php?acao=entrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados),
    });

    const retorno = await resposta.json();

    if (!resposta.ok) {
        mensagem.textContent = retorno.erro;
        return;
    }

    window.location.href = 'views/escolhaPerfil.html';
});
