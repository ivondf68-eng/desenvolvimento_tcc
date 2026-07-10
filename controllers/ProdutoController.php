<?php
require_once __DIR__ . '/../dao/ProdutoDAO.php';
require_once __DIR__ . '/../dao/ProdutorDAO.php';
require_once __DIR__ . '/../models/Produto.php';

class ProdutoController
{
    private ProdutoDAO $dao;
    private ProdutorDAO $produtorDAO;
    private string $diretorioImagens;
    private array $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $tamanhoMaximo = 2 * 1024 * 1024;

    public function __construct()
    {
        $this->dao = new ProdutoDAO();
        $this->produtorDAO = new ProdutorDAO();
        $this->diretorioImagens = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images';
    }

    private function proteger(): void
    {
        $usuarioValido = isset($_SESSION['usuario'])
            && is_array($_SESSION['usuario'])
            && !empty($_SESSION['usuario']['id_usuario']);

        if (!$usuarioValido) {
            unset($_SESSION['usuario']);
            $_SESSION['mensagem'] = 'Faça login para acessar seus produtos.';
            header('Location: index.php?acao=login');
            exit;
        }
    }

    private function idProdutorDaSessao(): ?int
    {
        $idUsuario = (int) ($_SESSION['usuario']['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return null;
        }

        return $this->produtorDAO->buscarIdPorUsuario($idUsuario);
    }

    public function listar(): void
    {
        $this->proteger();

        $idProdutor = $this->idProdutorDaSessao();
        if ($idProdutor === null) {
            $_SESSION['mensagem'] = 'Cadastre seu perfil de produtor antes de gerenciar produtos.';
            header('Location: index.php?acao=novo_produtor&id_usuario=' . (int) ($_SESSION['usuario']['id_usuario'] ?? 0));
            exit;
        }

        $produtos = $this->dao->listarPorProdutor($idProdutor);
        $mensagem = $_SESSION['mensagem'] ?? null;
        unset($_SESSION['mensagem']);

        require __DIR__ . '/../views/produtos/listar.php';
    }

    public function novo(): void
    {
        $this->proteger();

        $produto = [
            'id_produto' => null,
            'nome' => '',
            'imagem' => '',
            'descricao' => '',
            'preco' => '',
            'estoque' => 0,
            'ativo' => true,
            'id_categoria' => 1,
        ];
        $acaoFormulario = 'salvar_produto';
        $titulo = 'Novo produto';
        $erros = [];

        require __DIR__ . '/../views/produtos/formulario.php';
    }

    public function salvar(array $post): void
    {
        $this->proteger();

        $idProdutor = $this->idProdutorDaSessao();
        if ($idProdutor === null) {
            $_SESSION['mensagem'] = 'Produtor não encontrado para este usuário.';
            header('Location: index.php?acao=produtos');
            exit;
        }

        $produto = new Produto(
            null,
            $post['nome'] ?? '',
            $post['imagem'] ?? '',
            $post['descricao'] ?? '',
            (float) ($post['preco'] ?? 0),
            (int) ($post['estoque'] ?? 0),
            !empty($post['ativo']),
            (int) ($post['id_categoria'] ?? 1),
            $idProdutor
        );

        $erros = $this->validar($produto);
        if ($erros) {
            $produtoArray = [
                'id_produto' => null,
                'nome' => $post['nome'] ?? '',
                'imagem' => $post['imagem'] ?? '',
                'descricao' => $post['descricao'] ?? '',
                'preco' => $post['preco'] ?? '',
                'estoque' => $post['estoque'] ?? 0,
                'ativo' => !empty($post['ativo']),
                'id_categoria' => (int) ($post['id_categoria'] ?? 1),
            ];
            $acaoFormulario = 'salvar_produto';
            $titulo = 'Novo produto';
            require __DIR__ . '/../views/produtos/formulario.php';
            return;
        }

        $this->dao->registrar($produto);
        $_SESSION['mensagem'] = 'Produto cadastrado com sucesso.';
        header('Location: index.php?acao=produtos');
        exit;
    }

    public function editar(int $id): void
    {
        $this->proteger();

        $idProdutor = $this->idProdutorDaSessao();
        if ($idProdutor === null) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Produtor não encontrado para este usuário.']);
            return;
        }

        $produto = $this->dao->buscarPorId($id);
        if (!$produto || $produto->getIdProdutor() !== $idProdutor) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Produto não encontrado.']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'id_produto'   => $produto->getId(),
            'nome'         => $produto->getNome(),
            'imagem'       => $produto->getImagem(),
            'descricao'    => $produto->getDescricao(),
            'preco'        => $produto->getPreco(),
            'estoque'      => $produto->getEstoque(),
            'ativo'        => $produto->getAtivo(),
            'id_categoria' => $produto->getIdCategoria(),
        ]);
    }

    public function atualizar(array $post): void
    {
        $this->proteger();

        $idProdutor = $this->idProdutorDaSessao();
        if ($idProdutor === null) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produtor não encontrado para este usuário.']);
            return;
        }

        $idProduto = (int) ($post['id_produto'] ?? 0);
        $produtoAtual = $this->dao->buscarPorId($idProduto);

        if (!$produtoAtual || $produtoAtual->getIdProdutor() !== $idProdutor) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado.']);
            return;
        }

        $imagem = $produtoAtual->getImagem();
        $errosUpload = [];
        $imagemNova = $this->processarUploadImagem($errosUpload, $imagem);
        if ($imagemNova !== null) {
            $imagem = $imagemNova;
        }

        $ativo = array_key_exists('ativo', $post)
            ? !empty($post['ativo'])
            : $produtoAtual->getAtivo();

        $produto = new Produto(
            $produtoAtual->getId(),
            $post['nome'] ?? '',
            $imagem,
            $post['descricao'] ?? '',
            (float) ($post['preco'] ?? 0),
            (int) ($post['estoque'] ?? 0),
            $ativo,
            (int) ($post['id_categoria'] ?? $produtoAtual->getIdCategoria()),
            $idProdutor
        );

        $erros = array_merge($this->validar($produto), $errosUpload);
        if ($erros) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => implode(', ', $erros),
            ]);
            return;
        }

        $ok = $this->dao->editar($produto);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => $ok,
            'mensagem' => $ok ? 'Produto atualizado com sucesso.' : 'Erro ao atualizar produto.',
        ]);
    }

    public function excluir(int $id): void
    {
        $this->proteger();

        $idProdutor = $this->idProdutorDaSessao();
        if ($idProdutor === null) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produtor não encontrado para este usuário.']);
            return;
        }

        $produto = $this->dao->buscarPorId($id);
        if (!$produto || $produto->getIdProdutor() !== $idProdutor) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado.']);
            return;
        }

        $ok = $this->dao->excluir($id);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => $ok,
            'mensagem' => $ok ? 'Produto excluído com sucesso.' : 'Erro ao excluir produto.',
        ]);
    }

    private function validar(Produto $produto): array
    {
        $erros = [];

        if (trim($produto->getNome()) === '') {
            $erros[] = 'O nome do produto é obrigatório.';
        }

        if ((float) $produto->getPreco() < 0) {
            $erros[] = 'O preço não pode ser negativo.';
        }

        if ((int) $produto->getEstoque() < 0) {
            $erros[] = 'O estoque não pode ser negativo.';
        }

        if ((int) $produto->getIdCategoria() <= 0) {
            $erros[] = 'Informe uma categoria válida.';
        }

        return $erros;
    }

    private function processarUploadImagem(array &$erros, string $imagemAtual = ''): ?string
    {
        $erros = [];
        $arquivo = $_FILES['imagem'] ?? null;

        if ($arquivo === null || (int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int)$arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Falha no upload da imagem.';
            return null;
        }

        if ((int)$arquivo['size'] > $this->tamanhoMaximo) {
            $erros[] = 'A imagem deve ter no máximo 2 MB.';
            return null;
        }

        $extensao = strtolower(pathinfo((string)$arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $this->extensoesPermitidas, true)) {
            $erros[] = 'Extensão inválida da imagem.';
            return null;
        }

        if (!is_dir($this->diretorioImagens)) {
            mkdir($this->diretorioImagens, 0777, true);
        }

        $nomeArquivo = uniqid('produto_', true) . '.' . $extensao;
        $destino = $this->diretorioImagens . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (!move_uploaded_file((string)$arquivo['tmp_name'], $destino)) {
            $erros[] = 'Não foi possível salvar a imagem enviada.';
            return null;
        }

        return $nomeArquivo;
    }
}