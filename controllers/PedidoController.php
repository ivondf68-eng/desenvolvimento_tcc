<?php
require_once __DIR__ . '/../dao/PedidoDAO.php';
require_once __DIR__ . '/../dao/ProdutoDAO.php';
require_once __DIR__ . '/../dao/CarrinhoDAO.php';

class PedidoController
{
    private PedidoDAO  $dao;
    private ProdutoDAO $produtoDAO;
    private CarrinhoDAO $carrinhoDAO;

    public function __construct()
    {
        $this->dao        = new PedidoDAO();
        $this->produtoDAO = new ProdutoDAO();
        $this->carrinhoDAO = new CarrinhoDAO();
    }

    private function responder(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
        exit;
    }

    private function lerDados(): array
    {
        $json  = file_get_contents('php://input');
        $dados = json_decode($json, true);
        return is_array($dados) ? $dados : [];
    }

    private function lerDadosDaRequisicao(): array
    {
        if (!empty($_POST) || !empty($_FILES)) {
            return is_array($_POST) ? $_POST : [];
        }

        return $this->lerDados();
    }

    private function processarUploadProduto(array &$erros): string
    {
        $erros = [];
        $arquivo = $_FILES['imagem'] ?? null;

        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'produto.png';
        }
        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Falha no upload da imagem.';
            return 'produto.png';
        }
        if ((int) $arquivo['size'] > 2 * 1024 * 1024) {
            $erros[] = 'A imagem deve ter no máximo 2 MB.';
            return 'produto.png';
        }

        $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extensao, $extensoesPermitidas, true)) {
            $erros[] = 'Extensão inválida. Use jpg, jpeg, png, gif ou webp.';
            return 'produto.png';
        }

        $diretorioImagens = __DIR__ . '/../images';
        if (!is_dir($diretorioImagens)) {
            mkdir($diretorioImagens, 0777, true);
        }

        $nomeArquivo = uniqid('produto_', true) . '.' . $extensao;
        $destino     = $diretorioImagens . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (!move_uploaded_file((string) $arquivo['tmp_name'], $destino)) {
            $erros[] = 'Não foi possível salvar a imagem enviada.';
            return 'produto.png';
        }

        return $nomeArquivo;
    }

    private function idUsuarioSessao(): ?int
    {
        $id = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function idConsumidorSessao(): ?int
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            return null;
        }

        return $this->dao->idConsumidorPorUsuario($idUsuario);
    }

    public function listarCarrinho(): void
    {
        $idConsumidor = $this->idConsumidorSessao();
        if (!$idConsumidor) {
            $this->responder(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
            return;
        }

        try {
            $itens = $this->carrinhoDAO->listarItens($idConsumidor);
            $this->responder(['itens' => $itens]);
        } catch (Throwable $e) {
            $this->responder(['erro' => $e->getMessage()], 400);
        }
    }

    public function adicionarAoCarrinho(): void
    {
        $idConsumidor = $this->idConsumidorSessao();
        if (!$idConsumidor) {
            $this->responder(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
            return;
        }

        $dados = $this->lerDados();
        $idProduto = (int)($dados['id_produto'] ?? 0);
        $quantidade = max(1, (int)($dados['quantidade'] ?? 1));

        if ($idProduto <= 0) {
            $this->responder(['erro' => 'Produto inválido.'], 422);
            return;
        }

        try {
            $itens = $this->carrinhoDAO->adicionarItem($idConsumidor, $idProduto, $quantidade);
            $this->responder(['mensagem' => 'Produto adicionado ao carrinho.', 'itens' => $itens], 201);
        } catch (Throwable $e) {
            $this->responder(['erro' => $e->getMessage()], 400);
        }
    }

    public function atualizarCarrinho(): void
    {
        $idConsumidor = $this->idConsumidorSessao();
        if (!$idConsumidor) {
            $this->responder(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
            return;
        }

        $dados = $this->lerDados();
        $idProduto = (int)($dados['id_produto'] ?? 0);
        $quantidade = max(1, (int)($dados['quantidade'] ?? 1));

        if ($idProduto <= 0) {
            $this->responder(['erro' => 'Produto inválido.'], 422);
            return;
        }

        try {
            $itens = $this->carrinhoDAO->atualizarQuantidade($idConsumidor, $idProduto, $quantidade);
            $this->responder(['mensagem' => 'Quantidade atualizada.', 'itens' => $itens]);
        } catch (Throwable $e) {
            $this->responder(['erro' => $e->getMessage()], 400);
        }
    }

    public function removerDoCarrinho(): void
    {
        $idConsumidor = $this->idConsumidorSessao();
        if (!$idConsumidor) {
            $this->responder(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
            return;
        }

        $dados = $this->lerDados();
        $idProduto = (int)($dados['id_produto'] ?? 0);

        if ($idProduto <= 0) {
            $this->responder(['erro' => 'Produto inválido.'], 422);
            return;
        }

        try {
            $itens = $this->carrinhoDAO->removerItem($idConsumidor, $idProduto);
            $this->responder(['mensagem' => 'Produto removido do carrinho.', 'itens' => $itens]);
        } catch (Throwable $e) {
            $this->responder(['erro' => $e->getMessage()], 400);
        }
    }

    /** POST /index.php?acao=finalizar_pedido */
    public function finalizar(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            $this->responder(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
            return;
        }

        $dados = $this->lerDados();
        $itens       = $dados['itens']       ?? [];
        $agendamento = $dados['agendamento'] ?? '';
        $observacoes = $dados['observacoes'] ?? '';

        if (empty($itens)) {
            $this->responder(['erro' => 'O carrinho está vazio.'], 422);
            return;
        }

        $idConsumidor = $this->dao->idConsumidorPorUsuario($idUsuario);
        if (!$idConsumidor) {
            $this->responder(['erro' => 'Cadastre seu perfil de consumidor antes de fazer pedidos.'], 422);
            return;
        }

        $itens = array_values($itens);

        // Agrupar itens por produtor
        $porProdutor = [];
        foreach ($itens as $item) {
            $idProduto  = (int)($item['id_produto']  ?? 0);
            $quantidade = (int)($item['quantidade']  ?? 1);

            $produto = $this->produtoDAO->buscarPorId($idProduto);
            if (!$produto) continue;

            $idProdutor = $produto->getIdProdutor();
            $porProdutor[$idProdutor][] = [
                'id_produto'    => $idProduto,
                'quantidade'    => $quantidade,
                'preco_unidade' => $produto->getPreco(),
            ];
        }

        if (empty($porProdutor)) {
            $this->responder(['erro' => 'Nenhum produto válido encontrado.'], 422);
            return;
        }

        $agendamentoFmt = $agendamento
            ? date('Y-m-d H:i:s', strtotime($agendamento))
            : date('Y-m-d H:i:s', strtotime('+1 day'));

        $idsPedidos = [];
        foreach ($porProdutor as $idProdutor => $itensProd) {
            $id = $this->dao->criar($idConsumidor, $idProdutor, $agendamentoFmt, $observacoes, $itensProd);
            if ($id) $idsPedidos[] = $id;
        }

        if (empty($idsPedidos)) {
            $this->responder(['erro' => 'Erro ao registrar pedido. Tente novamente.'], 500);
            return;
        }

        $this->carrinhoDAO->limpar($idConsumidor);

        $this->responder([
            'mensagem'   => 'Pedido realizado com sucesso!',
            'ids_pedidos' => $idsPedidos,
        ], 201);
    }

    /** GET /index.php?acao=meus_pedidos */
    public function meusPedidos(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            $this->responder(['erro' => 'Sessão expirada.'], 401);
            return;
        }

        $idConsumidor = $this->dao->idConsumidorPorUsuario($idUsuario);
        if (!$idConsumidor) {
            $this->responder([], 200);
            return;
        }

        $pedidos = $this->dao->listarPorConsumidor($idConsumidor);
        $this->responder($pedidos);
    }

    /** GET /index.php?acao=pedidos_produtor */
    public function pedidosProdutor(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            $this->responder(['erro' => 'Sessão expirada.'], 401);
            return;
        }

        $idProdutor = $this->dao->idProdutorPorUsuario($idUsuario);
        if (!$idProdutor) {
            $this->responder([], 200);
            return;
        }

        $pedidos = $this->dao->listarPorProdutor($idProdutor);
        $this->responder($pedidos);
    }

    /** GET /index.php?acao=listar_produtos_publicos */
    public function listarProdutosPublicos(): void
    {
        $produtos = $this->produtoDAO->listar();
        $resultado = array_map(fn($p) => [
            'id_produto'  => $p->getId(),
            'nome'        => $p->getNome(),
            'imagem'      => $p->getImagem(),
            'descricao'   => $p->getDescricao(),
            'preco'       => $p->getPreco(),
            'estoque'     => $p->getEstoque(),
            'ativo'       => $p->getAtivo(),
            'id_categoria'=> $p->getIdCategoria(),
            'id_produtor' => $p->getIdProdutor(),
        ], $produtos);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
        exit;
    }

    /** GET /index.php?acao=listar_produtores_publicos */
    public function listarProdutoresPublicos(): void
    {
        $db   = Database::conectar();
        $stmt = $db->query(
            "SELECT p.id_produtor, p.nome, p.foto_perfil, p.telefone,
                    e.cidade, e.estado
             FROM produtor p
             INNER JOIN endereco e ON e.id_endereco = p.id_endereco
             ORDER BY p.nome ASC"
        );
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /** GET /index.php?acao=produtos_do_produtor&id=X */
    public function produtosDoProdutor(): void
    {
        $idProdutor = (int)($_GET['id'] ?? 0);
        if ($idProdutor <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }
        $produtos = $this->produtoDAO->listarPorProdutor($idProdutor);
        $resultado = array_map(fn($p) => [
            'id_produto'  => $p->getId(),
            'nome'        => $p->getNome(),
            'imagem'      => $p->getImagem(),
            'descricao'   => $p->getDescricao(),
            'preco'       => $p->getPreco(),
            'estoque'     => $p->getEstoque(),
            'ativo'       => $p->getAtivo(),
            'id_categoria'=> $p->getIdCategoria(),
            'id_produtor' => $p->getIdProdutor(),
        ], $produtos);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
        exit;
    }

    /** GET /index.php?acao=meus_produtos_produtor */
    public function meusProdutosProdutor(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }
        $idProdutor = $this->dao->idProdutorPorUsuario($idUsuario);
        if (!$idProdutor) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }
        $produtos = $this->produtoDAO->listarPorProdutor($idProdutor);
        $resultado = array_map(fn($p) => [
            'id_produto'  => $p->getId(),
            'nome'        => $p->getNome(),
            'imagem'      => $p->getImagem(),
            'descricao'   => $p->getDescricao(),
            'preco'       => $p->getPreco(),
            'estoque'     => $p->getEstoque(),
            'ativo'       => $p->getAtivo(),
        ], $produtos);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
        exit;
    }

    /** POST /index.php?acao=registrar_produto */
    public function registrarProduto(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if (!$idUsuario) {
            $this->responder(['erro' => 'Sessão expirada.'], 401);
            return;
        }

        $idProdutor = $this->dao->idProdutorPorUsuario($idUsuario);
        if (!$idProdutor) {
            $this->responder(['erro' => 'Cadastre seu perfil de produtor antes de registrar produtos.'], 422);
            return;
        }

        $dados = $this->lerDadosDaRequisicao();
        $nome      = trim($dados['nome']      ?? '');
        $descricao = trim($dados['descricao'] ?? '');
        $preco     = (float)($dados['preco']    ?? 0);
        $estoque   = (int)($dados['estoque']   ?? 0);
        $categoria = (int)($dados['id_categoria'] ?? 1);

        $erros = [];
        $imagem = trim($dados['imagem'] ?? 'produto.png');
        if (!empty($_FILES['imagem'])) {
            $imagem = $this->processarUploadProduto($erros);
        }
        if ($nome === '')     $erros[] = 'O nome do produto é obrigatório.';
        if ($preco < 0)       $erros[] = 'O preço não pode ser negativo.';
        if ($estoque < 0)     $erros[] = 'O estoque não pode ser negativo.';

        if ($erros) {
            $this->responder(['erro' => implode(' ', $erros)], 422);
            return;
        }

        $produto = new Produto(null, $nome, $imagem, $descricao, $preco, $estoque, true, $categoria, $idProdutor);
        $ok = $this->produtoDAO->registrar($produto);

        if (!$ok) {
            $this->responder(['erro' => 'Erro ao salvar produto.'], 500);
            return;
        }

        $this->responder(['mensagem' => 'Produto cadastrado com sucesso!'], 201);
    }
}
