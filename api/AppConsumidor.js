// Carrega produtos na tela do consumidor

async function carregarProdutos() {
  const grid = document.getElementById('produtos-grid');
  if (!grid) return;

  grid.innerHTML = '<p class="carregando">Carregando produtos...</p>';

  let todos = [];
  try {
    const resp = await fetch('../index.php?acao=listar_produtos_publicos');
    todos = await resp.json();
  } catch {
    grid.innerHTML = '<p class="mensagem-erro">Erro ao carregar produtos.</p>';
    return;
  }

  // Filtra apenas ativos
  todos = todos.filter(p => p.ativo);

  const countEl = document.getElementById('resultado-count');

  function renderizar(lista) {
    if (countEl) countEl.textContent = `${lista.length} produto(s) encontrado(s)`;
    if (!lista.length) {
      grid.innerHTML = '<p class="carregando">Nenhum produto disponível no momento.</p>';
      return;
    }

    grid.innerHTML = lista.map(p => `
      <article class="card">
        <div class="card__img-wrapper">
          ${!p.imagem || p.imagem === 'produto.png'
            ? `<div class="card-sem-imagem">${p.nome}</div>`
            : `<img src="../images/${p.imagem}" alt="Imagem do produto ${p.nome}" class="card__img">`
          }
        </div>
        <div class="card__corpo">
          <p class="card__categoria">Categoria #${p.id_categoria}</p>
          <h2 class="card__nome">${p.nome}</h2>
          <p class="card__descricao">${p.descricao || ''}</p>
          <p class="card__preco">
            R$ ${Number(p.preco).toFixed(2)}
          </p>
          <p class="card__estoque">Estoque: ${p.estoque}</p>
          <button
            class="btn-adicionar"
            onclick='adicionarAoCarrinho(${JSON.stringify({
              id_produto: p.id_produto,
              nome: p.nome,
              preco: p.preco,
              id_produtor: p.id_produtor,
            })})'
            ${p.estoque <= 0 ? 'disabled style="opacity:.5"' : ''}>
            <i class="bi bi-cart-plus"></i> Adicionar
          </button>
        </div>
      </article>
    `).join('');
  }

  renderizar(todos);

  // Busca
  const buscaInput = document.getElementById('busca-input');
  if (buscaInput) {
    buscaInput.addEventListener('input', () => {
      const t = buscaInput.value.toLowerCase();
      renderizar(todos.filter(p => p.nome.toLowerCase().includes(t) || (p.descricao || '').toLowerCase().includes(t)));
    });
  }

  // Ordenação
  const ordenar = document.getElementById('ordenar');
  if (ordenar) {
    ordenar.addEventListener('change', () => {
      let lista = [...todos];
      if (ordenar.value === 'preco-asc')  lista.sort((a, b) => a.preco - b.preco);
      if (ordenar.value === 'preco-desc') lista.sort((a, b) => b.preco - a.preco);
      if (ordenar.value === 'nome')       lista.sort((a, b) => a.nome.localeCompare(b.nome));
      renderizar(lista);
    });
  }
}

document.addEventListener('DOMContentLoaded', carregarProdutos);

//Edição
document.addEventListener("DOMContentLoaded", async () => {
  const params = new URLSearchParams(window.location.search);
  const idUsuario = params.get("id_usuario");

  // SELECT (carregar dados)
  try {
    if (idUsuario) {
      const resp = await fetch(`../index.php?acao=editar_consumidor&id=${idUsuario}`);
      if (!resp.ok) throw new Error("Falha ao buscar dados do consumidor");
      const dados = await resp.json();

      document.getElementById("nome").value = dados.nome || "";
      document.getElementById("telefone").value = dados.telefone || "";
      document.getElementById("logradouro").value = dados.endereco.logradouro || "";
      document.getElementById("numero").value = dados.endereco.numero || "";
      document.getElementById("bairro").value = dados.endereco.bairro || "";
      document.getElementById("complemento").value = dados.endereco.complemento || "";
      document.getElementById("estado").value = dados.endereco.estado || "";
      document.getElementById("cidade").value = dados.endereco.cidade || "";
    }
  } catch (err) {
    alert("Erro ao carregar consumidor: " + err.message);
  }

  // UPDATE
  document.getElementById("form-consumidor").addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const formData = new FormData(e.target);
      const resp = await fetch("../index.php?acao=atualizar_consumidor", {
        method: "POST",
        body: formData
      });
      if (!resp.ok) throw new Error("Falha ao atualizar consumidor");
      alert("Perfil atualizado com sucesso!");
      window.location.href = "consumidorMain.html";
    } catch (err) {
      alert("Erro ao atualizar consumidor: " + err.message);
    }
  });

  // DELETE
  document.getElementById("btn-deletar").addEventListener("click", async () => {
    if (confirm("Tem certeza que deseja excluir seu perfil de consumidor?")) {
      try {
        const resp = await fetch(`../index.php?acao=excluir_consumidor&id=${idUsuario}`, { method: "POST" });
        if (!resp.ok) throw new Error("Falha ao excluir consumidor");
        alert("Perfil excluído com sucesso!");
        window.location.href = "../index.html";
      } catch (err) {
        alert("Erro ao excluir consumidor: " + err.message);
      }
    }
  });
});

// Sair
document.addEventListener('DOMContentLoaded', () => {
  const btnSair = document.getElementById('btn-sair');

  if (btnSair) {
    btnSair.addEventListener('click', async (event) => {
      event.preventDefault();

      try {
        await fetch('../index.php?acao=sair', {
          method: 'GET',
          credentials: 'same-origin'
        });
      } catch (erro) {
        console.error('Erro ao sair:', erro);
      } finally {
        window.location.href = '../index.html';
      }
    });
  }
});