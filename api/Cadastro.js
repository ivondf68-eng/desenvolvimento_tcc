// api/Cadastro.js — Cadastro de nova conta
const form     = document.getElementById('form-login');
const mensagem = document.getElementById('mensagem');

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    mensagem.textContent = 'Criando conta...';
    mensagem.style.color = '#78716c';

    const dados = {
      email:           document.getElementById('email').value, 
      senha:           document.getElementById('senha').value,
      cpf:             document.getElementById('cpf').value,
      data_nascimento: document.getElementById('data_nascimento').value,
      tipo_perfil:     document.getElementById('tipo_perfil').value,
    };

    try {
      const resp    = await fetch('../index.php?acao=cadastrar_conta', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(dados),
      });
      const retorno = await resp.json();

      if (!resp.ok) {
        mensagem.style.color  = '#dc2626';
        mensagem.textContent  = retorno.erro || 'Erro ao cadastrar.';
        return;
      }

      mensagem.style.color = '#16a34a';
      mensagem.textContent = retorno.mensagem;

      const tipo = retorno.tipo_perfil;
      const id   = retorno.id_usuario;

      // Redireciona para o cadastro de perfil correto
      if (tipo === 'produtor') {
        window.location.href = `../index.php?acao=novo_produtor&id_usuario=${id}`;
      } else {
        // consumidor ou ambos — começa pelo consumidor
        window.location.href = `../index.php?acao=novo_consumidor&id_usuario=${id}`;
      }

    } catch {
      mensagem.style.color = '#dc2626';
      mensagem.textContent = 'Erro de conexão. Tente novamente.';
    }
  });
}
