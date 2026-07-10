# Feito A Mão

> Marketplace de produtos artesanais de microempreendedores locais.

Projeto final da disciplina **Desenvolvimento Front-End 1** — CSTSI - IFSul Pelotas - 2026.

---

## Motivação

Mesmo em cidades pequenas, pequenos produtores artesanais têm pouca visibilidade. Produtos caseiros — biscoitos, salgados, doces, chocolates — muitas vezes são vendidos apenas por indicação boca a boca, sem nenhum canal de divulgação ou alcance maior.

O **Feito A Mão** nasceu para dar a esses produtores uma vitrine digital simples, e para dar aos clientes um lugar único onde encontrar esse tipo de produto.

---

## Objetivo

Aplicar os conceitos de HTML5, CSS3 e JavaScript estudados na disciplina em uma aplicação web interativa, responsiva e acessível, integrada a dados dinâmicos (API própria e API pública), voltada para a divulgação de produtos de microempreendedores artesanais.

---

## Público-alvo

- **Produtores**: pequenos artesãos que trabalham de casa com produtos caseiros (biscoitos, salgados, doces, chocolates) e querem divulgar o que fazem.
- **Consumidores**: pessoas que buscam esse tipo de produto — para consumo próprio, eventos, festas, presentes, etc.

---

## Funcionalidades

- Login e cadastro de conta, com escolha de perfil (consumidor, produtor ou ambos)
- Vitrine de produtos renderizada dinamicamente via JavaScript
- Carrinho de compras com persistência via `localStorage`
- Fechamento de pedido e histórico de "Meus Pedidos"
- Painel do produtor: cadastro de produtos, acompanhamento de pedidos recebidos (pendentes/entregues)
- Preenchimento automático de estado/cidade a partir da API pública do IBGE 
- Toasts e mensagens de carregamento/erro/sucesso como feedback visual
- Menu responsivo com hambúrguer para mobile
- Acessibilidade: skip link, atributos `aria-*`, navegação por teclado, contraste adequado

---

## Tecnologias e fluxo de dados

- Estrutura (HTML5): Marcação semântica (`header`, `nav`, `main`, `article`, `footer`)
- Estilo (CSS3): Layout mobile-first com Flexbox + Grid, media queries, unidades relativas 
- Front-end (JavaScript): Manipulação do DOM, `fetch()`, `async/await`, `filter()`/`map()`/`reduce()` 
- Armazenamento local (`localStorage`): Persistência do carrinho de compras no navegador 
- API pública: [API do IBGE](https://servicodados.ibge.gov.br/api/docs/localidades) - Preenchimento de estado/cidade nos formulários de cadastro 
- Back-end (PHP 8.3): padrão MVC: `controllers/`, `dao/`, `models/` - API própria que expõe os dados da aplicação em JSON
- Banco de dados (PostgreSQL): Armazenamento de usuários, produtores, consumidores, produtos e pedidos 
- Infraestrutura (Docker + Docker Compose): Sobe o container PHP/Apache e o banco PostgreSQL juntos 

**Como os dados chegam ao front-end:**

1. As páginas HTML (em `views/` e na raiz) carregam os scripts correspondentes em `api/*.js`.
2. Cada script faz uma chamada `fetch()` para uma rota de `index.php?acao=...` (ex.: `listar_produtos_publicos`, `meus_pedidos`, `cadastrar_conta`).
3. O `index.php` direciona a requisição para o `Controller` correspondente (`ProdutoController`, `PedidoController`, etc.), que usa a camada `DAO` para consultar o PostgreSQL via PDO (`config/Database.php`).
4. O controller devolve a resposta em JSON.
5. O JavaScript recebe o JSON, aplica `filter()`/`map()`/`reduce()` quando necessário (buscas, ordenações, totais do carrinho) e injeta o HTML dinamicamente via `innerHTML`, exibindo mensagens de carregamento/erro/vazio conforme o estado da requisição.
6. Para estado e cidade, o mesmo padrão é usado, mas a origem dos dados é a **API pública do IBGE**, consumida diretamente do navegador em `api_gov/api_gov.js`.
7. O carrinho de compras é mantido inteiramente no front-end via `localStorage`, e só é enviado ao back-end (`finalizar_pedido`) no momento em que o consumidor fecha o pedido.

**Possíveis limitações:**
- A API do IBGE depende de conexão com a internet; se estiver fora do ar, o formulário de estado/cidade fica indisponível (há tratamento de erro exibindo mensagem ao usuário).
- O carrinho salvo em `localStorage` é salvo no navegador (comportamento esperado da API).
- A aplicação depende do back-end PHP/PostgreSQL estar em execução (via Docker) para funcionar.

---

## Estrutura de arquivos

```
projeto_consumidor/
├── index.html                # Login (estático)
├── index.php                 # Roteador principal da API (?acao=...)
├── views/                    # Demais páginas (cadastro, vitrine, painel do produtor, etc.)
├── api/                      # JavaScript por página (fetch, DOM, filtros, carrinho, nav/menu)
├── api_gov/                  # Integração com a API pública do IBGE
├── css/
│   └── style.css             # Estilos — mobile-first, Flexbox/Grid, media queries, keyframes
├── controllers/               # Camada de controle (PHP)
├── dao/                      # Camada de acesso a dados (PHP/PDO)
├── models/                   # Entidades da aplicação (PHP)
├── config/
│   └── Database.php          # Conexão PDO com o PostgreSQL
├── images/                   # Imagens enviadas de produtos e produtores
├── projeto.sql               # Script de criação do banco de dados
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Acessibilidade

- Texto alternativo em todas as imagens (estáticas e renderizadas dinamicamente)
- Labels descritivos em formulários e botões
- Contraste adequado entre texto e fundo
- Navegação funcional por teclado (Tab, Enter, Espaço, Escape)
- `aria-live` para contadores de resultado, totais do carrinho e toasts
- Skip link em todas as páginas para pular direto ao conteúdo principal

---

## Observações

- O carrinho é salvo no **`localStorage`** do navegador, atendendo ao requisito de uso de um recurso nativo da plataforma web/API HTML5.
- Foi utilizada uma API própria em PHP com banco de dados PostgreSQL, além da API pública do IBGE para estado/cidade.
