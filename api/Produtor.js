// api/Produtor.js — Carrega lista de produtores (produtores.html)
async function carregarProdutores() {
  const grid   = document.getElementById('produtores-grid') || document.querySelector('.produtores-grid');
  const busca  = document.getElementById('busca-produtores');

  if (!grid) return;

  grid.innerHTML = '<p style="padding:1rem">Carregando produtores...</p>';

  let todos = [];
  try {
    const resp = await fetch('../index.php?acao=listar_produtores_publicos');
    todos      = await resp.json();
  } catch {
    grid.innerHTML = '<p style="padding:1rem">Erro ao carregar produtores.</p>';
    return;
  }

  function renderizar(lista) {
    if (!lista.length) {
      grid.innerHTML = '<p style="padding:1rem">Nenhum produtor cadastrado ainda.</p>';
      return;
    }
    grid.innerHTML = lista.map(p => `
      <article class="card produtor-card">
        <div class="card__img-wrapper">
          ${p.imagem
            ? `<img src="../images/${p.imagem}" alt="Imagem do produtor ${p.nome}" class="card__img">`
            : `<div class="card-sem-imagem">${p.nome}</div>`
          }
        </div>
        <div class="card__corpo">
          <h2 class="card__nome">${p.nome}</h2>
          <p class="card__descricao"><i class="bi bi-telephone-fill"></i> ${p.telefone || ''}</p>
        </div>
      </article>
    `).join('');
  }

  renderizar(todos);

  if (busca) {
    busca.addEventListener('input', () => {
      const termo = busca.value.toLowerCase();
      renderizar(todos.filter(p =>
        p.nome.toLowerCase().includes(termo) ||
        (p.cidade ?? '').toLowerCase().includes(termo)
      ));
    });
  }
}

document.addEventListener('DOMContentLoaded', carregarProdutores);
