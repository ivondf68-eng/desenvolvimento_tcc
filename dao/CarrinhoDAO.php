<?php
require_once __DIR__ . '/../config/Database.php';

class CarrinhoDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    public function listarItens(int $idConsumidor): array
    {
        $idCarrinho = $this->buscarOuCriarCarrinho($idConsumidor);

        $stmt = $this->conexao->prepare(
            "SELECT ci.id_carrinho_item, ci.id_produto, ci.quantidade, ci.preco_unidade,
                    p.nome, p.imagem, p.estoque, p.ativo, p.id_produtor
             FROM carrinho_item ci
             INNER JOIN produto p ON p.id_produto = ci.id_produto
             WHERE ci.id_carrinho = :id_carrinho
             ORDER BY ci.criado_em ASC"
        );
        $stmt->execute(['id_carrinho' => $idCarrinho]);

        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itens as &$item) {
            $item['subtotal'] = (float)$item['quantidade'] * (float)$item['preco_unidade'];
        }

        return $itens;
    }

    public function adicionarItem(int $idConsumidor, int $idProduto, int $quantidade): array
    {
        $this->validarProduto($idProduto);
        $idCarrinho = $this->buscarOuCriarCarrinho($idConsumidor);
        $quantidade = max(1, $quantidade);

        $this->conexao->beginTransaction();
        try {
            $stmt = $this->conexao->prepare(
                'SELECT id_carrinho_item, quantidade FROM carrinho_item
                 WHERE id_carrinho = :id_carrinho AND id_produto = :id_produto LIMIT 1'
            );
            $stmt->execute([
                'id_carrinho' => $idCarrinho,
                'id_produto'  => $idProduto,
            ]);
            $itemAtual = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($itemAtual) {
                $novaQuantidade = (int)$itemAtual['quantidade'] + $quantidade;
                $this->validarEstoque($idProduto, $novaQuantidade);

                $stmtUpdate = $this->conexao->prepare(
                    'UPDATE carrinho_item
                     SET quantidade = :quantidade, atualizado_em = NOW()
                     WHERE id_carrinho_item = :id_carrinho_item'
                );
                $stmtUpdate->execute([
                    'quantidade'        => $novaQuantidade,
                    'id_carrinho_item' => $itemAtual['id_carrinho_item'],
                ]);
            } else {
                $produto = $this->buscarProduto($idProduto);
                $this->validarEstoque($idProduto, $quantidade);

                $stmtInsert = $this->conexao->prepare(
                    'INSERT INTO carrinho_item (id_carrinho, id_produto, quantidade, preco_unidade, criado_em, atualizado_em)
                     VALUES (:id_carrinho, :id_produto, :quantidade, :preco_unidade, NOW(), NOW())'
                );
                $stmtInsert->execute([
                    'id_carrinho'    => $idCarrinho,
                    'id_produto'     => $idProduto,
                    'quantidade'     => $quantidade,
                    'preco_unidade'  => $produto['preco'],
                ]);
            }

            $this->conexao->commit();
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            throw $e;
        }

        return $this->listarItens($idConsumidor);
    }

    public function atualizarQuantidade(int $idConsumidor, int $idProduto, int $quantidade): array
    {
        $this->validarProduto($idProduto);
        $idCarrinho = $this->buscarOuCriarCarrinho($idConsumidor);
        $quantidade = max(1, $quantidade);
        $this->validarEstoque($idProduto, $quantidade);

        $stmt = $this->conexao->prepare(
            'UPDATE carrinho_item
             SET quantidade = :quantidade, atualizado_em = NOW()
             WHERE id_carrinho = :id_carrinho AND id_produto = :id_produto'
        );
        $stmt->execute([
            'quantidade'  => $quantidade,
            'id_carrinho' => $idCarrinho,
            'id_produto'  => $idProduto,
        ]);

        return $this->listarItens($idConsumidor);
    }

    public function removerItem(int $idConsumidor, int $idProduto): array
    {
        $idCarrinho = $this->buscarOuCriarCarrinho($idConsumidor);

        $stmt = $this->conexao->prepare(
            'DELETE FROM carrinho_item WHERE id_carrinho = :id_carrinho AND id_produto = :id_produto'
        );
        $stmt->execute([
            'id_carrinho' => $idCarrinho,
            'id_produto' => $idProduto,
        ]);

        return $this->listarItens($idConsumidor);
    }

    public function limpar(int $idConsumidor): void
    {
        $idCarrinho = $this->buscarOuCriarCarrinho($idConsumidor);

        $stmt = $this->conexao->prepare(
            'DELETE FROM carrinho_item WHERE id_carrinho = :id_carrinho'
        );
        $stmt->execute(['id_carrinho' => $idCarrinho]);
    }

    private function buscarOuCriarCarrinho(int $idConsumidor): int
    {
        $stmt = $this->conexao->prepare(
            'SELECT id_carrinho FROM carrinho WHERE id_consumidor = :id_consumidor LIMIT 1'
        );
        $stmt->execute(['id_consumidor' => $idConsumidor]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['id_carrinho'];
        }

        $stmtInsert = $this->conexao->prepare(
            'INSERT INTO carrinho (id_consumidor, criado_em, atualizado_em)
             VALUES (:id_consumidor, NOW(), NOW())'
        );
        $stmtInsert->execute(['id_consumidor' => $idConsumidor]);

        return (int)$this->conexao->lastInsertId();
    }

    private function buscarProduto(int $idProduto): ?array
    {
        $stmt = $this->conexao->prepare(
            'SELECT id_produto, preco, estoque, ativo FROM produto WHERE id_produto = :id_produto LIMIT 1'
        );
        $stmt->execute(['id_produto' => $idProduto]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function validarProduto(int $idProduto): void
    {
        $produto = $this->buscarProduto($idProduto);
        if (!$produto) {
            throw new RuntimeException('Produto não encontrado.');
        }

        if (empty($produto['ativo'])) {
            throw new RuntimeException('Este produto não está disponível no momento.');
        }
    }

    private function validarEstoque(int $idProduto, int $quantidade): void
    {
        $produto = $this->buscarProduto($idProduto);
        if (!$produto) {
            throw new RuntimeException('Produto não encontrado.');
        }

        if ((int)$produto['estoque'] < $quantidade) {
            throw new RuntimeException('Estoque insuficiente para esta quantidade.');
        }
    }
}
