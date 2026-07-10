// Painel do produtor: produtos e pedidos recebidos

async function carregarProdutosProdutor() {
  const grid = document.getElementById('produtos-produtor-grid');
  if (!grid) return;

  try {
    const resp    = await fetch('../index.php?acao=meus_produtos_produtor');
    const produtos = await resp.json();

    if (!produtos.length) {
      grid.innerHTML = '<p class="mensagem-aviso">Você ainda não cadastrou produtos. <a href="formularioProduto.html">Adicionar produto</a></p>';
      return;
    }

    grid.innerHTML = produtos.map(p => `
      <article class="card" style="max-width:260px">
        <div class="card__img-wrapper">
          ${!p.imagem || p.imagem === 'produto.png'
            ? `<div class="card-sem-imagem">${p.nome}</div>`
            : `<img src="../images/${p.imagem}" alt="Imagem do produto ${p.nome}" class="card__img">`
          }
        </div>
        <div class="card__corpo">
          <h3 class="card__nome">${p.nome}</h3>
          <p class="card__preco">R$ ${Number(p.preco).toFixed(2)}</p>
          <p class="card__estoque">Estoque: ${p.estoque} | ${p.ativo ? '<i class="bi bi-check2"></i> Ativo' : '<i class="bi bi-x"></i> Inativo'}</p>
          <button
            class="btn-adicionar"
            onclick="window.location.href='editarProduto.html?id=${p.id_produto}'">
            <i class="bi bi-pencil"></i> Editar
          </button>
        </div>
      </article>
    `).join('');
  } catch {
    grid.innerHTML = '<p class="mensagem-erro">Erro ao carregar produtos.</p>';
  }
}

async function carregarPedidosProdutor() {
  const grid     = document.getElementById('pedidos-grid');
  const countEl  = document.getElementById('resultado-count');
  const totalEl  = document.getElementById('resumo-total');
  const pendEl   = document.getElementById('resumo-pendentes');
  const entEl    = document.getElementById('resumo-entregues');

  if (!grid) return;

  grid.innerHTML = '<p>Carregando pedidos...</p>';

  let pedidos = [];
  try {
    const resp = await fetch('../index.php?acao=pedidos_produtor');
    pedidos    = await resp.json();
  } catch {
    grid.innerHTML = '<p class="mensagem-erro">Erro ao carregar pedidos.</p>';
    return;
  }

  if (totalEl)  totalEl.textContent  = pedidos.length;
  if (pendEl)   pendEl.textContent   = pedidos.filter(p => !p.status).length;
  if (entEl)    entEl.textContent    = pedidos.filter(p => p.status).length;

  function renderizar(lista) {
    if (countEl) countEl.textContent = `${lista.length} pedido(s)`;
    if (!lista.length) {
      grid.innerHTML = '<p class="mensagem-aviso">Nenhum pedido encontrado.</p>';
      return;
    }

    grid.innerHTML = lista.map(p => `
      <article class="card card__pedido">
        <div class="card__pedido-header">
          <h3 class="card__pedido-titulo">Pedido #${p.id_pedido}</h3>
          <span class="card__categoria">${p.status ? '✅ Entregue' : '⏳ Pendente'}</span>
        </div>
        <p class="card__pedido-agendamento">Consumidor: <strong>${p.nome_consumidor}</strong></p>
        <p class="card__pedido-agendamento">Agendado: ${new Date(p.agendamento).toLocaleString('pt-BR')}</p>
        <ul class="card__pedido-itens">
          ${(p.itens || []).map(i => `<li>${i.nome_produto} × ${i.quantidade}</li>`).join('')}
        </ul>
        <p class="card__preco">Total: R$ ${Number(p.total).toFixed(2)}</p>
        ${p.observacoes ? `<p class="card__pedido-observacao">Obs: ${p.observacoes}</p>` : ''}
      </article>
    `).join('');
  }

  renderizar(pedidos);

  // Filtros por status
  document.querySelectorAll('#filtros-status .filtro-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('#filtros-status .filtro-btn').forEach(b => { b.classList.remove('ativo'); b.setAttribute('aria-pressed','false'); });
      btn.classList.add('ativo');
      btn.setAttribute('aria-pressed','true');

      const status = btn.dataset.status;
      if (status === 'Todos') return renderizar(pedidos);
      renderizar(pedidos.filter(p => {
        if (status === 'pendente')   return !p.status;
        if (status === 'entregue' || status === 'cancelado')   return  p.status;
        
        return true;
      }));
    });
  });

  // Busca
  const busca = document.getElementById('busca-input');
  if (busca) {
    busca.addEventListener('input', () => {
      const t = busca.value.toLowerCase();
      renderizar(pedidos.filter(p =>
        (p.itens || []).some(i => i.nome_produto.toLowerCase().includes(t))
      ));
    });
  }

  // Sair
  document.getElementById('btn-sair')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.location.href = '../index.php?acao=sair';
  });
}

// Espera o <select> ter mais de uma opção (ou expirar o tempo limite), já que
// os estados/cidades são preenchidos de forma assíncrona pelo api_gov.js.
function aguardarOpcoes(select, timeoutMs = 8000) {
  return new Promise((resolve) => {
    if (!select) return resolve();
    if (select.options.length > 1) return resolve();

    const inicio = Date.now();
    const intervalo = setInterval(() => {
      if (select.options.length > 1 || Date.now() - inicio > timeoutMs) {
        clearInterval(intervalo);
        resolve();
      }
    }, 100);
  });
}

// Função para carregar os dados do produtor logado e preencher o formulário de edição
async function carregarDadosProdutor() {
  const campoIdUsuario = document.getElementById('id_usuario');
  const idUsuarioUrl = campoIdUsuario?.value || '';
  const mensagem = document.getElementById('mensagem');

  try {
    // Se não houver id_usuario na URL, o backend usa o usuário da sessão logada
    const url = idUsuarioUrl
      ? `../index.php?acao=buscar_produtor&id_usuario=${idUsuarioUrl}`
      : `../index.php?acao=buscar_produtor`;
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Erro na requisição');
    const produtor = await resp.json();
    if (produtor.erro) throw new Error(produtor.erro);

    // Garante que o campo oculto tenha o id_usuario correto para o envio do formulário
    if (campoIdUsuario) campoIdUsuario.value = produtor.id_usuario || idUsuarioUrl;

    // Preenche os campos simples do formulário
    document.getElementById('nome').value = produtor.nome || '';
    document.getElementById('telefone').value = produtor.telefone || '';
    document.getElementById('logradouro').value = produtor.logradouro || '';
    document.getElementById('numero').value = produtor.numero || '';
    document.getElementById('bairro').value = produtor.bairro || '';
    document.getElementById('complemento').value = produtor.complemento || '';

    // Estado e cidade são preenchidos via IBGE (api_gov.js) de forma assíncrona,
    // então é preciso esperar as opções carregarem antes de atribuir o value.
    const selectEstado = document.getElementById('estado');
    const selectCidade = document.getElementById('cidade');

    await aguardarOpcoes(selectEstado);
    if (selectEstado && produtor.estado) {
      selectEstado.value = produtor.estado;
      // Dispara o carregamento das cidades do estado selecionado
      selectEstado.dispatchEvent(new Event('change'));
      await aguardarOpcoes(selectCidade);
      if (selectCidade && produtor.cidade) {
        selectCidade.value = produtor.cidade;
      }
    }

    if (mensagem) mensagem.textContent = '';
  } catch (err) {
    if (mensagem) mensagem.textContent = 'Erro ao carregar dados do produtor.';
    console.error(err);
  }
}

// Função para salvar a edição do produtor
async function salvarEdicaoProdutor(event) {
  event.preventDefault();
  const form = document.getElementById('form-produtor');
  const mensagem = document.getElementById('mensagem');
  const botao = document.getElementById('btn-entrar');

  if (mensagem) {
    mensagem.style.color = '#57534e';
    mensagem.textContent = 'Salvando alterações...';
  }
  if (botao) botao.disabled = true;

  try {
    const formData = new FormData(form);
    const resp = await fetch('../index.php?acao=atualizar_produtor', {
      method: 'POST',
      body: formData
    });

    if (!resp.ok) throw new Error('Erro na requisição');
    const resultado = await resp.json();

    if (resultado.sucesso) {
      if (mensagem) {
        mensagem.className = 'mensagem-sucesso';
        mensagem.textContent = 'Perfil atualizado com sucesso!';
      }
      setTimeout(() => {
        window.location.href = 'produtorMain.html';
      }, 1200);
    } else if (mensagem) {
      mensagem.className = 'mensagem-erro';
      mensagem.textContent = resultado.mensagem || 'Erro ao atualizar perfil.';
    }
  } catch (err) {
    if (mensagem) {
      mensagem.className = 'mensagem-erro';
      mensagem.textContent = 'Erro ao salvar alterações.';
    }
    console.error(err);
  } finally {
    if (botao) botao.disabled = false;
  }
}

// Função para excluir o perfil do produtor logado
async function excluirProdutor() {
  const confirmar = confirm(
    'Tem certeza que deseja excluir seu perfil de produtor? ' +
    'Essa ação é permanente e também removerá seus produtos e pedidos cadastrados.'
  );
  if (!confirmar) return;

  const idUsuario = document.getElementById('id_usuario')?.value || '';
  const mensagem = document.getElementById('mensagem');
  const botaoDeletar = document.getElementById('btn-deletar');
  const botaoEditar = document.getElementById('btn-entrar');

  if (mensagem) {
    mensagem.className = 'mensagem-aviso';
    mensagem.textContent = 'Excluindo perfil...';
  }
  if (botaoDeletar) botaoDeletar.disabled = true;
  if (botaoEditar) botaoEditar.disabled = true;

  try {
    const formData = new FormData();
    formData.append('id_usuario', idUsuario);

    const resp = await fetch('../index.php?acao=excluir_produtor', {
      method: 'POST',
      body: formData
    });

    const texto = await resp.text();
    let resultado;
    try {
      resultado = JSON.parse(texto);
    } catch (parseErr) {
      // O servidor não devolveu JSON válido (ex.: erro fatal do PHP / página de login)
      console.error('Resposta não era JSON válido:', resp.status, texto);
      throw new Error('Resposta inválida do servidor (status ' + resp.status + ')');
    }

    if (resultado.sucesso) {
      if (mensagem) {
        mensagem.style.color = 'green';
        mensagem.textContent = 'Perfil excluído com sucesso. Redirecionando...';
      }
      setTimeout(() => {
        window.location.href = '../index.html';
      }, 1200);
    } else {
      if (mensagem) {
        mensagem.style.color = '#dc2626';
        mensagem.textContent = resultado.mensagem || 'Erro ao excluir perfil.';
      }
      console.error('Falha ao excluir produtor:', resp.status, resultado);
      if (botaoDeletar) botaoDeletar.disabled = false;
      if (botaoEditar) botaoEditar.disabled = false;
    }
  } catch (err) {
    if (mensagem) {
      mensagem.style.color = '#dc2626';
      mensagem.textContent = 'Erro ao excluir perfil.';
    }
    console.error(err);
    if (botaoDeletar) botaoDeletar.disabled = false;
    if (botaoEditar) botaoEditar.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  carregarProdutosProdutor();
  carregarPedidosProdutor();

  // Página de edição de produtor (editarProdutor.html)
  const formProdutor = document.getElementById('form-produtor');
  if (formProdutor) {
    carregarDadosProdutor();
    formProdutor.addEventListener('submit', salvarEdicaoProdutor);
    document.getElementById('btn-deletar')?.addEventListener('click', excluirProdutor);
  }
});
