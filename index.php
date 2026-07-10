<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CadastroController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/consumidorController.php';
require_once __DIR__ . '/controllers/ProdutorController.php';
require_once __DIR__ . '/controllers/ProdutoController.php';
require_once __DIR__ . '/controllers/PedidoController.php';
require_once __DIR__ . '/config/Database.php';

$acao = $_GET['acao'] ?? 'login';

$authController       = new AuthController();
$cadastroController   = new CadastroController();
$usuarioController    = new UsuarioController();
$consumidorController = new ConsumidorController();
$produtorController   = new ProdutorController();
$produtoController    = new ProdutoController();
$pedidoController     = new PedidoController();

switch ($acao) {
    // ---- Auth ----
    case 'login':
        $authController->mostrarLogin();
        break;
    case 'entrar':
        $authController->entrar();
        break;
    case 'perfis_disponiveis':
        $authController->perfisDisponiveis();
        break;
    case 'sair':
        $authController->sair();
        break;

    // ---- Cadastro de conta (JSON) ----
    case 'cadastrar_conta':
        $cadastroController->cadastrar();
        break;

    // ---- Pessoa (legado) ----
    case 'listar':
    case 'listar_usuarios':
        $usuarioController->listar();
        break;
    case 'novo':
        $usuarioController->novo();
        break;
    case 'salvar_usuario':
        $usuarioController->salvar($_POST);
        break;
    case 'editar_usuario':
        $usuarioController->editar((int)($_GET['id'] ?? 0));
        break;
    case 'atualizar_usuario':
        $usuarioController->atualizar($_POST);
        break;
    case 'excluir_usuario':
        $usuarioController->excluir((int)($_GET['id'] ?? 0));
        break;

    // ---- Consumidor ----
    case 'consumidores':
        $consumidorController->listar();
        break;
    case 'novo_consumidor':
        $consumidorController->novo((int)($_GET['id_usuario'] ?? 0));
        break;
    case 'salvar_consumidor':
        $consumidorController->salvar($_POST);
        break;
    case 'buscar_consumidor':
        $consumidorController->buscar();
        break;
    case 'editar_consumidor':
        $consumidorController->editar((int)($_GET['id'] ?? 0));
        break;
    case 'atualizar_consumidor':
        $consumidorController->atualizar($_POST);
        break;
    case 'excluir_consumidor':
        $consumidorController->excluir((int)($_GET['id'] ?? 0));
        break;

    // ---- Produtor ----
    case 'novo_produtor':
        $produtorController->novo((int)($_GET['id_usuario'] ?? 0));
        break;
    case 'salvar_produtor':
        $produtorController->salvar($_POST);
        break;
    case 'buscar_produtor':
        $produtorController->buscar();
        break;
    case 'atualizar_produtor':
        $produtorController->atualizar($_POST);
        break;
    case 'excluir_produtor':
        $produtorController->excluir();
        break;

    // ---- Produto ----
    case 'produtos':
        $produtoController->listar();
        break;
    case 'novo_produto':
        $produtoController->novo();
        break;
    case 'salvar_produto':
        $produtoController->salvar($_POST);
        break;
    case 'editar_produto':
        $produtoController->editar((int)($_GET['id'] ?? 0));
        break;
    case 'atualizar_produto':
        $produtoController->atualizar($_POST);
        break;
    case 'excluir_produto':
        $produtoController->excluir((int)($_GET['id'] ?? 0));
        break;

    // ---- APIs JSON ----
    case 'listar_produtos_publicos':
        $pedidoController->listarProdutosPublicos();
        break;
    case 'listar_produtores_publicos':
        $pedidoController->listarProdutoresPublicos();
        break;
    case 'produtos_do_produtor':
        $pedidoController->produtosDoProdutor();
        break;
    case 'meus_produtos_produtor':
        $pedidoController->meusProdutosProdutor();
        break;
    case 'registrar_produto':
        $pedidoController->registrarProduto();
        break;
    case 'listar_carrinho':
        $pedidoController->listarCarrinho();
        break;
    case 'adicionar_ao_carrinho':
        $pedidoController->adicionarAoCarrinho();
        break;
    case 'atualizar_carrinho':
        $pedidoController->atualizarCarrinho();
        break;
    case 'remover_do_carrinho':
        $pedidoController->removerDoCarrinho();
        break;
    case 'finalizar_pedido':
        $pedidoController->finalizar();
        break;
    case 'meus_pedidos':
        $pedidoController->meusPedidos();
        break;
    case 'pedidos_produtor':
        $pedidoController->pedidosProdutor();
        break;

    default:
        $authController->mostrarLogin();
        break;
}