// api/crudConsumidor.js — edição e exclusão do perfil do consumidor

async function carregarDadosConsumidor() {
  const campoIdUsuario = document.getElementById('id_usuario');
  const idUsuarioUrl = campoIdUsuario?.value || '';
  const mensagem = document.getElementById('mensagem');

  try {
    const url = idUsuarioUrl
      ? `../index.php?acao=buscar_consumidor&id_usuario=${idUsuarioUrl}`
      : `../index.php?acao=buscar_consumidor`;

    const resp = await fetch(url);
    if (!resp.ok) {
      throw new Error(`Erro HTTP ${resp.status}`);
    }

    const consumidor = await resp.json();
    if (consumidor.erro) {
      throw new Error(consumidor.erro);
    }

    if (campoIdUsuario) campoIdUsuario.value = consumidor.id_usuario || idUsuarioUrl;

    document.getElementById('nome').value = consumidor.nome || '';
    document.getElementById('telefone').value = consumidor.telefone || '';
    document.getElementById('logradouro').value = consumidor.logradouro || '';
    document.getElementById('numero').value = consumidor.numero || '';
    document.getElementById('bairro').value = consumidor.bairro || '';
    document.getElementById('complemento').value = consumidor.complemento || '';

    const selectEstado = document.getElementById('estado');
    const selectCidade = document.getElementById('cidade');

    if (selectEstado && consumidor.estado) {
      selectEstado.value = consumidor.estado;
      selectEstado.dispatchEvent(new Event('change'));
    }

    if (selectCidade && consumidor.cidade) {
      const tentarPreencherCidade = () => {
        const opcoes = Array.from(selectCidade.options).map(opt => opt.value);
        if (opcoes.includes(consumidor.cidade)) {
          selectCidade.value = consumidor.cidade;
          return true;
        }
        return false;
      };

      if (!tentarPreencherCidade()) {
        let tentativas = 0;
        const intervalo = setInterval(() => {
          tentativas++;
          if (tentarPreencherCidade() || tentativas >= 20) {
            clearInterval(intervalo);
          }
        }, 250);
      }
    }

    if (mensagem) {
      mensagem.style.color = '#16a34a';
      mensagem.textContent = '';
    }
  } catch (err) {
    console.error('Erro ao carregar consumidor:', err);
    if (mensagem) {
      mensagem.style.color = '#dc2626';
      mensagem.textContent = 'Erro ao carregar dados do consumidor.';
    }
  }
}

async function salvarEdicaoConsumidor(event) {
  event.preventDefault();

  const form = document.getElementById('form-consumidor');
  const mensagem = document.getElementById('mensagem');
  const botaoSalvar = document.getElementById('btn-salvar');
  const botaoDeletar = document.getElementById('btn-deletar');

  if (mensagem) {
    mensagem.style.color = '#57534e';
    mensagem.textContent = 'Salvando alterações...';
  }

  if (botaoSalvar) botaoSalvar.disabled = true;
  if (botaoDeletar) botaoDeletar.disabled = true;

  try {
    const formData = new FormData(form);

    const resp = await fetch('../index.php?acao=atualizar_consumidor', {
      method: 'POST',
      body: formData
    });

    const texto = await resp.text();
    let resultado = {};
    try {
      resultado = JSON.parse(texto);
    } catch {
      console.error('Resposta inválida ao atualizar consumidor:', texto);
      throw new Error('Resposta inválida do servidor');
    }

    if (!resp.ok || !resultado.sucesso) {
      throw new Error(resultado.mensagem || 'Erro ao atualizar perfil.');
    }

    if (mensagem) {
      mensagem.style.color = '#16a34a';
      mensagem.textContent = resultado.mensagem || 'Perfil atualizado com sucesso!';
    }

    setTimeout(() => {
      window.location.href = 'consumidorMain.html';
    }, 1200);
  } catch (err) {
    console.error('Erro ao salvar consumidor:', err);
    if (mensagem) {
      mensagem.style.color = '#dc2626';
      mensagem.textContent = err.message || 'Erro ao salvar alterações.';
    }
    if (botaoSalvar) botaoSalvar.disabled = false;
    if (botaoDeletar) botaoDeletar.disabled = false;
  }
}

async function excluirConsumidor() {
  const confirmar = confirm(
    'Tem certeza que deseja excluir seu perfil de consumidor? Essa ação é permanente.'
  );
  if (!confirmar) return;

  const idUsuario = document.getElementById('id_usuario')?.value || '';
  const mensagem = document.getElementById('mensagem');
  const botaoDeletar = document.getElementById('btn-deletar');
  const botaoSalvar = document.getElementById('btn-salvar');

  if (mensagem) {
    mensagem.style.color = '#57534e';
    mensagem.textContent = 'Excluindo perfil...';
  }

  if (botaoDeletar) botaoDeletar.disabled = true;
  if (botaoSalvar) botaoSalvar.disabled = true;

  try {
    const formData = new FormData();
    formData.append('id_usuario', idUsuario);

    const resp = await fetch('../index.php?acao=excluir_consumidor', {
      method: 'POST',
      body: formData
    });

    const texto = await resp.text();
    let resultado = {};
    try {
      resultado = JSON.parse(texto);
    } catch {
      console.error('Resposta inválida ao excluir consumidor:', texto);
      throw new Error('Resposta inválida do servidor');
    }

    if (!resp.ok || !resultado.sucesso) {
      throw new Error(resultado.mensagem || 'Erro ao excluir perfil.');
    }

    if (mensagem) {
      mensagem.style.color = '#16a34a';
      mensagem.textContent = resultado.mensagem || 'Perfil excluído com sucesso.';
    }

    setTimeout(() => {
      window.location.href = '../index.html';
    }, 1200);
  } catch (err) {
    console.error('Erro ao excluir consumidor:', err);
    if (mensagem) {
      mensagem.style.color = '#dc2626';
      mensagem.textContent = err.message || 'Erro ao excluir perfil.';
    }
    if (botaoDeletar) botaoDeletar.disabled = false;
    if (botaoSalvar) botaoSalvar.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const formConsumidor = document.getElementById('form-consumidor');
  if (!formConsumidor) return;

  carregarDadosConsumidor();
  formConsumidor.addEventListener('submit', salvarEdicaoConsumidor);

  const btnDeletar = document.getElementById('btn-deletar');
  if (btnDeletar) {
    btnDeletar.addEventListener('click', excluirConsumidor);
  }
});