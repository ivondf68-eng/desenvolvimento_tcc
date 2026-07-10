// api/Produto.js — Registro de produto pelo produtor (formularioProduto.html)
const form     = document.getElementById('form-produto');
const mensagem = document.getElementById('mensagem-produto');

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (mensagem) mensagem.textContent = 'Salvando...';

    const formData = new FormData();
    formData.append('nome', document.getElementById('produto-nome').value);
    formData.append('descricao', document.getElementById('produto-descricao').value);
    formData.append('preco', document.getElementById('produto-preco').value);
    formData.append('estoque', document.getElementById('produto-estoque')?.value ?? '0');
    formData.append('id_categoria', document.getElementById('produto-categoria-id')?.value ?? '1');

    const inputImagem = document.getElementById('produto-imagem');
    if (inputImagem?.files?.length) {
      formData.append('imagem', inputImagem.files[0]);
    }

    try {
      const resp = await fetch('../index.php?acao=registrar_produto', {
        method: 'POST',
        body: formData,
      });

      let retorno = {};
      try {
        retorno = await resp.json();
      } catch {
        retorno = {};
      }

      if (!resp.ok) {
        const textoErro = retorno.erro || retorno.mensagem || 'Erro ao salvar produto.';
        if (mensagem) mensagem.textContent = textoErro;
        return;
      }

      if (mensagem) mensagem.textContent = retorno.mensagem || 'Produto cadastrado com sucesso!';
      setTimeout(() => { window.location.href = 'produtorMain.html'; }, 1000);
    } catch (erro) {
      if (mensagem) mensagem.textContent = 'Erro de conexão. Faça login novamente e tente outra vez.';
      console.error('Erro ao salvar produto:', erro);
    }
  });
}

//Edição
document.addEventListener("DOMContentLoaded", async () => {
  const params = new URLSearchParams(window.location.search);
  const idProduto = params.get("id");

  // SELECT (carregar dados)
  try {
    if (idProduto) {
      const resp = await fetch(`../index.php?acao=editar_produto&id=${idProduto}`);
      if (!resp.ok) throw new Error("Falha ao buscar dados do produto");
      const dados = await resp.json();

      document.getElementById("produto-nome").value = dados.nome || "";
      document.getElementById("produto-categoria-id").value = dados.id_categoria || 1;
      document.getElementById("produto-preco").value = dados.preco || 0;
      document.getElementById("produto-estoque").value = dados.estoque || 0;
      document.getElementById("produto-descricao").value = dados.descricao || "";
    }
  } catch (err) {
    alert("Erro ao carregar produto: " + err.message);
  }

  // UPDATE
  document.getElementById("form-produto").addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const formData = new FormData(e.target);
      formData.append("id_produto", idProduto);

      const resp = await fetch("../index.php?acao=atualizar_produto", {
        method: "POST",
        body: formData
      });
      if (!resp.ok) throw new Error("Falha ao atualizar produto");
      alert("Produto atualizado com sucesso!");
      window.location.href = "produtorMain.html";
    } catch (err) {
      alert("Erro ao atualizar produto: " + err.message);
    }
  });

  // DELETE
  document.getElementById("btn-deletar").addEventListener("click", async () => {
    if (confirm("Tem certeza que deseja excluir este produto?")) {
      try {
        const resp = await fetch(`../index.php?acao=excluir_produto&id=${idProduto}`, { method: "POST" });
        if (!resp.ok) throw new Error("Falha ao excluir produto");
        alert("Produto excluído com sucesso!");
        window.location.href = "produtorMain.html";
      } catch (err) {
        alert("Erro ao excluir produto: " + err.message);
      }
    }
  });
});

