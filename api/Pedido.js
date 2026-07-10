// api/Pedido.js — Carrinho + finalizar pedido + listagem de pedidos do consumidor

let carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];

function atualizarBadge() {
  const badge = document.getElementById('carrinho-badge');
  if (badge) {
    badge.textContent = carrinho.reduce((s, i) => s + Number(i.quantidade || 0), 0);
  }
}

function salvarCarrinho() {
  localStorage.setItem('carrinho', JSON.stringify(carrinho));
}

document.addEventListener('DOMContentLoaded', atualizarBadge);

function normalizarItem(item) {
  const quantidade = Number(item.quantidade || 1);
  const precoUnitario = Number(item.preco_unidade ?? item.preco ?? 0);
  return {
    ...item,
    id_produto: Number(item.id_produto),
    quantidade,
    preco: precoUnitario,
    preco_unidade: precoUnitario,
    subtotal: precoUnitario * quantidade,
  };
}

async function carregarCarrinho() {
  try {
    const resp = await fetch('../index.php?acao=listar_carrinho');
    const dados = await resp.json();

    if (!resp.ok) {
      throw new Error(dados.erro || 'Não foi possível carregar o carrinho.');
    }

    carrinho = Array.isArray(dados.itens) ? dados.itens.map(normalizarItem) : [];
    salvarCarrinho();
  } catch (erro) {
    console.error(erro);
    carrinho = [];
    salvarCarrinho(); //Limpar o localStorage caso haja erro ao carregar o carrinho
  }

  atualizarBadge();
  renderizarCarrinho();
}

async function sincronizarCarrinho(acao, body = {}) {
  const resp = await fetch(`../index.php?acao=${acao}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  const dados = await resp.json().catch(() => ({}));
  if (!resp.ok) {
    throw new Error(dados.erro || 'Não foi possível atualizar o carrinho.');
  }

  carrinho = Array.isArray(dados.itens) ? dados.itens.map(normalizarItem) : [];
  salvarCarrinho();
  atualizarBadge();
  renderizarCarrinho();
  return dados;
}

// ─── Adicionar ao carrinho ─────────────────────────────────────────────────
window.adicionarAoCarrinho = async function(produto) {
  try {
    await sincronizarCarrinho('adicionar_ao_carrinho', {
      id_produto: produto.id_produto,
      quantidade: 1,
    });
    mostrarToast(`"${produto.nome}" adicionado ao carrinho!`);
  } catch (erro) {
    mostrarToast(erro.message || 'Erro ao adicionar ao carrinho.');
  }
};

// ─── Remover do carrinho ───────────────────────────────────────────────────
window.removerDoCarrinho = async function(idProduto) {
  try {
    await sincronizarCarrinho('remover_do_carrinho', { id_produto: idProduto });
  } catch (erro) {
    mostrarToast(erro.message || 'Erro ao remover do carrinho.');
  }
};

// ─── Renderizar carrinho no drawer ─────────────────────────────────────────
function renderizarCarrinho() {
  const grid    = document.getElementById('carrinho-grid');
  const resumo  = document.getElementById('carrinho-resumo');
  const totalEl = document.getElementById('carrinho-total');
  if (!grid) return;

  if (!carrinho.length) {
    grid.innerHTML = '<p class="mensagem-aviso">Seu carrinho está vazio.</p>';
    if (resumo)  resumo.textContent  = '0 itens';
    if (totalEl) totalEl.textContent = 'R$ 0,00';
    return;
  }

  grid.innerHTML = carrinho.map(item => `
    <div class="carrinho-item">
      <div style="flex:1">
        <strong>${item.nome}</strong><br>
        <small>R$ ${Number(item.preco_unidade ?? item.preco ?? 0).toFixed(2)} × ${item.quantidade} = R$ ${(Number(item.preco_unidade ?? item.preco ?? 0) * Number(item.quantidade || 1)).toFixed(2)}</small>
      </div>
      <div class="card-item-quantidade">
        <button onclick="alterarQtd(${item.id_produto}, -1)" class="btn-aumentar_diminuir-quantidade">−</button>
        <span>${item.quantidade}</span>
        <button onclick="alterarQtd(${item.id_produto}, +1)" class="btn-aumentar_diminuir-quantidade">+</button>
        <button onclick="removerDoCarrinho(${item.id_produto})" class="remover_quantidade">✕</button>
      </div>
    </div>
  `).join('');

  const total = carrinho.reduce((s, i) => s + (Number(i.preco_unidade ?? i.preco ?? 0) * Number(i.quantidade || 1)), 0);
  const qtd   = carrinho.reduce((s, i) => s + Number(i.quantidade || 0), 0);
  if (resumo)  resumo.textContent  = `${qtd} ${qtd === 1 ? 'item' : 'itens'}`;
  if (totalEl) totalEl.textContent = `R$ ${total.toFixed(2)}`;
}

window.alterarQtd = async function(idProduto, delta) {
  const item = carrinho.find(i => Number(i.id_produto) === Number(idProduto));
  if (!item) return;

  const novaQuantidade = Math.max(1, Number(item.quantidade || 1) + delta);
  try {
    await sincronizarCarrinho('atualizar_carrinho', {
      id_produto: idProduto,
      quantidade: novaQuantidade,
    });
  } catch (erro) {
    mostrarToast(erro.message || 'Erro ao atualizar a quantidade.');
  }
};

//  Abrir / fechar drawer 
function setupDrawer() {
  const btnAbrir  = document.getElementById('btn-abrir-carrinho');
  const btnFechar = document.getElementById('fechar-carrinho');
  const drawer    = document.getElementById('carrinho-drawer');

  if (btnAbrir  && drawer) btnAbrir.addEventListener('click',  () => drawer.classList.add('aberto'));
  if (btnFechar && drawer) btnFechar.addEventListener('click', () => drawer.classList.remove('aberto'));
}

// Finalizar pedido 
async function finalizarPedido() {
  if (!carrinho.length) {
    mostrarToast('Adicione produtos ao carrinho primeiro.');
    return;
  }

  const agendamento = document.getElementById('agendamento')?.value || '';
  const observacoes = document.getElementById('observacoes')?.value || '';

  const payload = {
    itens: carrinho.map(i => ({
      id_produto: Number(i.id_produto),
      quantidade: Number(i.quantidade || 1),
    })),
    agendamento,
    observacoes,
  };

  try {
    const resp    = await fetch('../index.php?acao=finalizar_pedido', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const retorno = await resp.json();

    if (!resp.ok) {
      mostrarToast(retorno.erro || 'Erro ao finalizar pedido.');
      return;
    }

    carrinho = [];
    salvarCarrinho();
    atualizarBadge();
    renderizarCarrinho();
    mostrarToast(retorno.mensagem || 'Pedido realizado!');
    document.getElementById('carrinho-drawer')?.classList.remove('aberto');
  } catch {
    mostrarToast('Erro de conexão.');
  }
}

// ─── Listagem de pedidos do consumidor
async function carregarMeusPedidos() {
  const container = document.getElementById('pedidos-consumidor');
  if (!container) return;

  container.innerHTML = '<p>Carregando pedidos...</p>';

  try {
    const resp   = await fetch('../index.php?acao=meus_pedidos');
    const pedidos = await resp.json();

    if (!pedidos.length) {
      container.innerHTML = '<p class="mensagem-aviso">Você ainda não fez nenhum pedido.</p>';
      return;
    }

    container.innerHTML = pedidos.map(p => `
      <article class="card" style="margin-bottom:1rem;padding:1rem">
        <div class="card__pedido-header">
          <h3 style="margin:0">Pedido #${p.id_pedido}</h3>
          <span class="card__categoria">${p.status ? 'Entregue' : 'Pendente'}</span>
        </div>
        <p class="card__pedido-agendamento">Produtor: <strong>${p.nome_produtor}</strong></p>
        <p class="card__pedido-agendamento">Agendado para: ${new Date(p.agendamento).toLocaleString('pt-BR')}</p>
        <ul class="card__pedido-itens">
          ${(p.itens || []).map(i => `<li>${i.nome_produto} × ${i.quantidade} — R$ ${Number(i.total).toFixed(2)}</li>`).join('')}
        </ul>
        <p class="paragrafo__preco">Total: R$ ${Number(p.total).toFixed(2)}</p>
        ${p.observacoes ? `<p class="card__pedido-observacao">Obs: ${p.observacoes}</p>` : ''}
      </article>
    `).join('');
  } catch {
    container.innerHTML = '<p class="mensagem-erro">Erro ao carregar pedidos.</p>';
  }
}

// ─── Toast ─────────────────────────────────────────────────────────────────
function mostrarToast(msg, tipo = '') {
  const el = document.getElementById('toast-container');
  if (!el) return;

  const t = document.createElement('div');

  // classe base do toast
  t.classList.add('toast');

  // classe opcional de tipo: sucesso ou erro
  if (tipo) {
    t.classList.add(tipo);
  }

  t.textContent = msg;
  el.appendChild(t);

  // remove o toast depois da animação
  setTimeout(() => {
    t.remove();
  }, 3000);
}

// ─── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  carregarCarrinho();
  setupDrawer();

  document.getElementById('finalizar-pedido')?.addEventListener('click', finalizarPedido);

  carregarMeusPedidos();
});
