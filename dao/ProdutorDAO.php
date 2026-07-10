<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Produtor.php';

class ProdutorDAO
{
    private PDO $conexao;
    private ?string $ultimoErro = null;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    public function getUltimoErro(): ?string
    {
        return $this->ultimoErro;
    }

    public function buscarIdPorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->conexao->prepare('SELECT id_produtor FROM produtor WHERE id_usuario = :id_usuario LIMIT 1');
        $stmt->execute(['id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id_produtor'] : null;
    }

    public function buscarPorUsuario(int $idUsuario): ?array
    {
        $sql = '
            SELECT
                p.id_produtor, p.nome, p.foto_perfil, p.telefone,
                u.id_usuario, u.email, u.cpf, u.data_nascimento,
                e.id_endereco, e.logradouro, e.numero, e.bairro, e.complemento,
                e.cidade, e.estado
            FROM produtor p
            INNER JOIN usuario u ON u.id_usuario = p.id_usuario
            INNER JOIN endereco e ON e.id_endereco = p.id_endereco
            WHERE p.id_usuario = :id_usuario
            LIMIT 1
        ';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function atualizar(Produtor $produtor, int $idEndereco): bool
    {
        $this->conexao->beginTransaction();
        try {
            $end = $produtor->getEndereco();
            $stmtEnd = $this->conexao->prepare(
                'UPDATE endereco
                 SET cidade = :cidade, estado = :estado, logradouro = :logradouro,
                     numero = :numero, bairro = :bairro, complemento = :complemento
                 WHERE id_endereco = :id_endereco'
            );
            $stmtEnd->execute([
                'cidade'      => $end->getCidade(),
                'estado'      => $end->getEstado(),
                'logradouro'  => $end->getLogradouro(),
                'numero'      => $end->getNumero(),
                'bairro'      => $end->getBairro(),
                'complemento' => $end->getComplemento(),
                'id_endereco' => $idEndereco,
            ]);

            $stmtProd = $this->conexao->prepare(
                'UPDATE produtor
                 SET nome = :nome, foto_perfil = :foto_perfil, telefone = :telefone
                 WHERE id_produtor = :id_produtor'
            );
            $stmtProd->execute([
                'nome'         => $produtor->getNome(),
                'foto_perfil'  => $produtor->getFotoPerfil(),
                'telefone'     => $produtor->getTelefone(),
                'id_produtor'  => $produtor->getId(),
            ]);

            $this->conexao->commit();
            return true;
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            return false;
        }
    }

    public function cadastrar(Produtor $produtor): ?int
    {
        $this->conexao->beginTransaction();

        try {
            $end = $produtor->getEndereco();
            $stmtEnd = $this->conexao->prepare('INSERT INTO endereco (cidade, estado, logradouro, numero, bairro, complemento) VALUES (:cidade, :estado, :logradouro, :numero, :bairro, :complemento)');
            $stmtEnd->execute([
                'cidade'      => $end->getCidade(),
                'estado'      => $end->getEstado(),
                'logradouro'  => $end->getLogradouro(),
                'numero'      => $end->getNumero(),
                'bairro'      => $end->getBairro(),
                'complemento' => $end->getComplemento(),
            ]);
            $idEndereco = (int) $this->conexao->lastInsertId();

            $usr = $produtor->getUsuarioLogin();
            $stmtProd = $this->conexao->prepare('INSERT INTO produtor (nome, foto_perfil, telefone, id_usuario, id_endereco) VALUES (:nome, :foto_perfil, :telefone, :id_usuario, :id_endereco)');
            $stmtProd->execute([
                'nome'         => $produtor->getNome(),
                'foto_perfil'  => $produtor->getFotoPerfil(),
                'telefone'     => $produtor->getTelefone(),
                'id_usuario'   => $usr->getId(),
                'id_endereco'  => $idEndereco,
            ]);

            $this->conexao->commit();
            return (int) $this->conexao->lastInsertId();
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            return null;
        }
    }

    public function excluir(int $idProdutor): bool
    {
        $this->conexao->beginTransaction();
        try {
            $stmtBusca = $this->conexao->prepare('SELECT id_endereco FROM produtor WHERE id_produtor = :id');
            $stmtBusca->execute(['id' => $idProdutor]);
            $row = $stmtBusca->fetch();
            if (!$row) {
                $this->conexao->rollBack();
                return false;
            }
            $idEndereco = (int) $row['id_endereco'];

            // Remove itens de carrinho que referenciam produtos deste produtor
            $this->conexao->prepare(
                'DELETE FROM carrinho_item
                 WHERE id_produto IN (SELECT id_produto FROM produto WHERE id_produtor = :id)'
            )->execute(['id' => $idProdutor]);

            // Remove itens de pedido ligados a produtos ou a pedidos deste produtor
            $this->conexao->prepare(
                'DELETE FROM pedido_produto
                 WHERE id_produto IN (SELECT id_produto FROM produto WHERE id_produtor = :id1)
                    OR id_pedido IN (SELECT id_pedido FROM pedido WHERE id_produtor = :id2)'
            )->execute(['id1' => $idProdutor, 'id2' => $idProdutor]);

            // Remove os pedidos recebidos pelo produtor
            $this->conexao->prepare('DELETE FROM pedido WHERE id_produtor = :id')->execute(['id' => $idProdutor]);

            // Remove os produtos cadastrados pelo produtor
            $this->conexao->prepare('DELETE FROM produto WHERE id_produtor = :id')->execute(['id' => $idProdutor]);

            // Remove o produtor
            $this->conexao->prepare('DELETE FROM produtor WHERE id_produtor = :id')->execute(['id' => $idProdutor]);

            // Remove o endereço vinculado
            $this->conexao->prepare('DELETE FROM endereco WHERE id_endereco = :id')->execute(['id' => $idEndereco]);

            $this->conexao->commit();
            return true;
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            $this->ultimoErro = $e->getMessage();
            return false;
        }
    }
}
