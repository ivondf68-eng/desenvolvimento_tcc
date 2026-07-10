const selectEstado = document.getElementById('estado');
    const selectCidade = document.getElementById('cidade');

    function preencherSelect(select, opcoes, textoPadrao) {
      select.innerHTML = '';

      const optionPadrao = document.createElement('option');
      optionPadrao.value = '';
      optionPadrao.textContent = textoPadrao;
      select.appendChild(optionPadrao);

      opcoes.forEach((opcao) => {
        const option = document.createElement('option');
        option.value = opcao.value;
        option.textContent = opcao.text;
        select.appendChild(option);
      });
    }

    async function carregarEstados() {
      try {
        const resposta = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome');
        if (!resposta.ok) {
          throw new Error('Não foi possível carregar os estados.');
        }

        const estados = await resposta.json();
        preencherSelect(
          selectEstado,
          estados.map((estado) => ({
            value: estado.sigla,
            text: `${estado.nome} (${estado.sigla})`
          })),
          'Selecione um estado'
        );
      } catch (erro) {
        preencherSelect(selectEstado, [], 'Erro ao carregar estados');
        selectEstado.disabled = true;
        selectCidade.disabled = true;
        selectCidade.innerHTML = '<option value="">Selecione um estado primeiro</option>';
        console.error(erro);
      }
    }

    async function carregarCidades(uf) {
      if (!uf) {
        selectCidade.disabled = true;
        selectCidade.innerHTML = '<option value="">Selecione um estado primeiro</option>';
        return;
      }

      selectCidade.disabled = true;
      selectCidade.innerHTML = '<option value="">Carregando cidades...</option>';

      try {
        const resposta = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`);
        if (!resposta.ok) {
          throw new Error('Não foi possível carregar as cidades.');
        }

        const cidades = await resposta.json();
        preencherSelect(
          selectCidade,
          cidades.map((cidade) => ({
            value: cidade.nome,
            text: cidade.nome
          })),
          'Selecione uma cidade'
        );
        selectCidade.disabled = false;
      } catch (erro) {
        selectCidade.innerHTML = '<option value="">Erro ao carregar cidades</option>';
        console.error(erro);
      }
    }

    if (selectEstado && selectCidade) {
      carregarEstados();

      selectEstado.addEventListener('change', (event) => {
        carregarCidades(event.target.value);
      });
    }