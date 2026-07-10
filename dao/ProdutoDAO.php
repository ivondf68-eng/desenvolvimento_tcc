<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Produto.php';

class ProdutoDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    private function mapear(array $row): Produto
    {
        return new Produto(
            (int) ($row['id_produto'] ?? 0),
            (string) ($row['nome'] ?? ''),
            (string) ($row['imagem'] ?? ''),
            (string) ($row['descricao'] ?? ''),
            (float) ($row['preco'] ?? 0),
            (int) ($row['estoque'] ?? 0),
            (bool) ($row['ativo'] ?? true),
            (int) ($row['id_categoria'] ?? 0),
            (int) ($row['id_produtor'] ?? 0)
        );
    }

    public function registrar(Produto $produto): bool
    {
        $sql = 'INSERT INTO produto (nome, imagem, descricao, preco, estoque, ativo, id_categoria, id_produtor) VALUES (:nome, :imagem, :descricao, :preco, :estoque, :ativo, :id_categoria, :id_produtor)';
        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute([
            'nome' => $produto->getNome(),
            'imagem' => $produto->getImagem(),
            'descricao' => $produto->getDescricao(),
            'preco' => $produto->getPreco(),
            'estoque' => $produto->getEstoque(),
            'ativo' => $produto->getAtivo() ? 1 : 0,
            'id_categoria' => $produto->getIdCategoria(),
            'id_produtor' => $produto->getIdProdutor(),
        ]);
    }

    public function editar(Produto $produto): bool
    {
        $sql = 'UPDATE produto SET nome = :nome, imagem = :imagem, descricao = :descricao, preco = :preco, estoque = :estoque, ativo = :ativo, id_categoria = :id_categoria, id_produtor = :id_produtor WHERE id_produto = :id_produto';
        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute([
            'nome' => $produto->getNome(),
            'imagem' => $produto->getImagem(),
            'descricao' => $produto->getDescricao(),
            'preco' => $produto->getPreco(),
            'estoque' => $produto->getEstoque(),
            'ativo' => $produto->getAtivo() ? 1 : 0,
            'id_categoria' => $produto->getIdCategoria(),
            'id_produtor' => $produto->getIdProdutor(),
            'id_produto' => $produto->getId(),
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->conexao->prepare('DELETE FROM produto WHERE id_produto = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function buscarPorId(int $id): ?Produto
    {
        $stmt = $this->conexao->prepare('SELECT * FROM produto WHERE id_produto = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapear($row) : null;
    }

    public function listar(): array
    {
        $stmt = $this->conexao->query('SELECT * FROM produto ORDER BY id_produto DESC');
        return array_map(fn($row) => $this->mapear($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listarPorProdutor(int $idProdutor): array
    {
        $stmt = $this->conexao->prepare('SELECT * FROM produto WHERE id_produtor = :id_produtor ORDER BY id_produto DESC');
        $stmt->execute(['id_produtor' => $idProdutor]);
        return array_map(fn($row) => $this->mapear($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}