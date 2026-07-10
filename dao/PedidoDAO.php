<?php
require_once __DIR__ . '/../config/Database.php';

class PedidoDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    /** Busca id_consumidor a partir do id_usuario */
    public function idConsumidorPorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->conexao->prepare(
            'SELECT id_consumidor FROM consumidor WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_consumidor'] : null;
    }

    /** Busca id_produtor a partir do id_usuario */
    public function idProdutorPorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->conexao->prepare(
            'SELECT id_produtor FROM produtor WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_produtor'] : null;
    }

    /** Cria pedido + itens dentro de uma transação. Retorna id_pedido ou null. */
    public function criar(
        int    $idConsumidor,
        int    $idProdutor,
        string $agendamento,
        string $observacoes,
        array  $itens          
    ): ?int {
        $this->conexao->beginTransaction();
        try {
            $total = array_sum(array_map(
                fn($i) => $i['quantidade'] * $i['preco_unidade'],
                $itens
            ));

            $stmt = $this->conexao->prepare(
                'INSERT INTO pedido (total, status, agendamento, criado, observacoes, id_consumidor, id_produtor)
                 VALUES (:total, false, :agendamento, NOW(), :observacoes, :id_consumidor, :id_produtor)'
            );
            $stmt->execute([
                'total'        => $total,
                'agendamento'  => $agendamento,
                'observacoes'  => $observacoes,
                'id_consumidor'=> $idConsumidor,
                'id_produtor'  => $idProdutor,
            ]);
            $idPedido = (int)$this->conexao->lastInsertId();

            $stmtItem = $this->conexao->prepare(
                'INSERT INTO pedido_produto (quantidade, preco_unidade, total, id_pedido, id_produto)
                 VALUES (:quantidade, :preco_unidade, :total_item, :id_pedido, :id_produto)'
            );
            foreach ($itens as $item) {
                $stmtItem->execute([
                    'quantidade'    => $item['quantidade'],
                    'preco_unidade' => $item['preco_unidade'],
                    'total_item'    => $item['quantidade'] * $item['preco_unidade'],
                    'id_pedido'     => $idPedido,
                    'id_produto'    => $item['id_produto'],
                ]);
            }

            $this->conexao->commit();
            return $idPedido;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            return null;
        }
    }

    /** Lista pedidos do consumidor com itens */
    public function listarPorConsumidor(int $idConsumidor): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT p.id_pedido, p.total, p.status, p.agendamento, p.criado, p.observacoes,
                    pr.nome AS nome_produtor
             FROM pedido p
             INNER JOIN produtor pr ON pr.id_produtor = p.id_produtor
             WHERE p.id_consumidor = :id
             ORDER BY p.criado DESC"
        );
        $stmt->execute(['id' => $idConsumidor]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$pedido) {
            $pedido['itens'] = $this->itensDoPedido((int)$pedido['id_pedido']);
        }
        return $pedidos;
    }

    /** Lista pedidos recebidos pelo produtor com itens */
    public function listarPorProdutor(int $idProdutor): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT p.id_pedido, p.total, p.status, p.agendamento, p.criado, p.observacoes,
                    c.nome AS nome_consumidor
             FROM pedido p
             INNER JOIN consumidor c ON c.id_consumidor = p.id_consumidor
             WHERE p.id_produtor = :id
             ORDER BY p.criado DESC"
        );
        $stmt->execute(['id' => $idProdutor]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$pedido) {
            $pedido['itens'] = $this->itensDoPedido((int)$pedido['id_pedido']);
        }
        return $pedidos;
    }

    private function itensDoPedido(int $idPedido): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT pp.quantidade, pp.preco_unidade, pp.total,
                    pr.nome AS nome_produto
             FROM pedido_produto pp
             INNER JOIN produto pr ON pr.id_produto = pp.id_produto
             WHERE pp.id_pedido = :id"
        );
        $stmt->execute(['id' => $idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
