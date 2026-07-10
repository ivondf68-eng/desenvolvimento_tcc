<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';
require_once __DIR__ . '/../models/Consumidor.php';
require_once __DIR__ . '/../models/Endereco.php';

class ConsumidorDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    public function listar(): array
    {
        $sql = '
            SELECT
                c.id_consumidor, c.nome, c.foto_perfil, c.telefone,
                u.id_usuario, u.email, u.cpf, u.data_nascimento,
                e.id_endereco, e.logradouro, e.numero, e.bairro,
                e.complemento, e.cidade, e.estado
            FROM consumidor c
            INNER JOIN usuario u ON u.id_usuario = c.id_usuario
            INNER JOIN endereco e ON e.id_endereco = c.id_endereco
            ORDER BY c.id_consumidor DESC
        ';
        return $this->conexao->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = '
            SELECT
                c.id_consumidor, c.nome, c.foto_perfil, c.telefone,
                u.id_usuario, u.email, u.cpf, u.data_nascimento,
                e.id_endereco, e.logradouro, e.numero, e.bairro,
                e.complemento, e.cidade, e.estado
            FROM consumidor c
            INNER JOIN usuario u ON u.id_usuario = c.id_usuario
            INNER JOIN endereco e ON e.id_endereco = c.id_endereco
            WHERE c.id_consumidor = :id
        ';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarPorUsuario(int $idUsuario): ?array
    {
        $sql = '
            SELECT
                c.id_consumidor, c.nome, c.foto_perfil, c.telefone,
                u.id_usuario, u.email, u.cpf, u.data_nascimento,
                e.id_endereco, e.logradouro, e.numero, e.bairro,
                e.complemento, e.cidade, e.estado
            FROM consumidor c
            INNER JOIN usuario u ON u.id_usuario = c.id_usuario
            INNER JOIN endereco e ON e.id_endereco = c.id_endereco
            WHERE c.id_usuario = :id_usuario
            LIMIT 1
        ';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id_usuario' => $idUsuario]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarIdPorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->conexao->prepare('SELECT id_consumidor FROM consumidor WHERE id_usuario = :id_usuario LIMIT 1');
        $stmt->execute(['id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_consumidor'] : null;
    }

    public function usuarioJaEConsumidor(int $idUsuario): bool
    {
        $sql = 'SELECT 1 FROM consumidor WHERE id_usuario = :id_usuario LIMIT 1';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id_usuario' => $idUsuario]);
        return (bool) $stmt->fetch();
    }

    public function cadastrar(Consumidor $consumidor): ?int
    {
        $this->conexao->beginTransaction();
        try {
            $sqlEndereco = '
                INSERT INTO endereco (cidade, estado, logradouro, numero, bairro, complemento)
                VALUES (:cidade, :estado, :logradouro, :numero, :bairro, :complemento)
            ';
            $stmtEndereco = $this->conexao->prepare($sqlEndereco);
            $end = $consumidor->getEndereco();
            $numero = $end->getNumero();
            $stmtEndereco->execute([
                'cidade'      => $end->getCidade(),
                'estado'      => $end->getEstado(),
                'logradouro'  => $end->getLogradouro(),
                'numero'      => $numero !== null ? (int) $numero : null,
                'bairro'      => $end->getBairro(),
                'complemento' => $end->getComplemento(),
            ]);
            $idEndereco = (int) $this->conexao->lastInsertId();

            $sqlConsumidor = '
                INSERT INTO consumidor (nome, foto_perfil, telefone, id_usuario, id_endereco)
                VALUES (:nome, :foto_perfil, :telefone, :id_usuario, :id_endereco)
            ';
            $stmtConsumidor = $this->conexao->prepare($sqlConsumidor);
            $usr = $consumidor->getUsuarioLogin();
            $stmtConsumidor->execute([
                'nome'        => $consumidor->getNome(),
                'foto_perfil' => $consumidor->getFotoPerfil(),
                'telefone'    => $consumidor->getTelefone(),
                'id_usuario'  => $usr->getId(),
                'id_endereco' => $idEndereco,
            ]);
            $idConsumidor = (int) $this->conexao->lastInsertId();

            $this->conexao->commit();
            return $idConsumidor;
        } catch (\Exception $e) {
            $this->conexao->rollBack();
            return null;
        }
    }

    public function atualizar(Consumidor $consumidor, int $idEndereco, bool $atualizarSenha): bool
    {
        $this->conexao->beginTransaction();
        try {
            $sqlEnd = '
                UPDATE endereco
                SET cidade = :cidade, estado = :estado, logradouro = :logradouro,
                    numero = :numero, bairro = :bairro, complemento = :complemento
                WHERE id_endereco = :id_endereco
            ';
            $stmtEnd = $this->conexao->prepare($sqlEnd);
            $end = $consumidor->getEndereco();
            $numero = $end->getNumero();
            $stmtEnd->execute([
                'cidade'      => $end->getCidade(),
                'estado'      => $end->getEstado(),
                'logradouro'  => $end->getLogradouro(),
                'numero'      => $numero !== null ? (int) $numero : null,
                'bairro'      => $end->getBairro(),
                'complemento' => $end->getComplemento(),
                'id_endereco' => $idEndereco,
            ]);

            $sqlCons = '
                UPDATE consumidor
                SET nome = :nome, foto_perfil = :foto_perfil, telefone = :telefone
                WHERE id_consumidor = :id_consumidor
            ';
            $stmtCons = $this->conexao->prepare($sqlCons);
            $stmtCons->execute([
                'nome'          => $consumidor->getNome(),
                'foto_perfil'   => $consumidor->getFotoPerfil(),
                'telefone'      => $consumidor->getTelefone(),
                'id_consumidor' => $consumidor->getId(),
            ]);

            $usr = $consumidor->getUsuarioLogin();
            if ($atualizarSenha && $usr->getSenha() !== '') {
                $sqlUsr = '
                    UPDATE usuario
                    SET email = :email, cpf = :cpf, data_nascimento = :data_nascimento, senha = :senha
                    WHERE id_usuario = :id_usuario
                ';
                $stmtUsr = $this->conexao->prepare($sqlUsr);
                $stmtUsr->execute([
                    'email'           => $usr->getEmail(),
                    'cpf'             => $usr->getCpf(),
                    'data_nascimento' => $usr->getDataNascimento(),
                    'senha'           => password_hash($usr->getSenha(), PASSWORD_DEFAULT),
                    'id_usuario'      => $usr->getId(),
                ]);
            } else {
                $sqlUsr = '
                    UPDATE usuario
                    SET email = :email, cpf = :cpf, data_nascimento = :data_nascimento
                    WHERE id_usuario = :id_usuario
                ';
                $stmtUsr = $this->conexao->prepare($sqlUsr);
                $stmtUsr->execute([
                    'email'           => $usr->getEmail(),
                    'cpf'             => $usr->getCpf(),
                    'data_nascimento' => $usr->getDataNascimento(),
                    'id_usuario'      => $usr->getId(),
                ]);
            }

            $this->conexao->commit();
            return true;
        } catch (\Exception $e) {
            $this->conexao->rollBack();
            return false;
        }
    }

    public function excluir(int $idConsumidor): bool
    {
        $this->conexao->beginTransaction();
        try {
            $stmtBusca = $this->conexao->prepare('SELECT id_endereco FROM consumidor WHERE id_consumidor = :id');
            $stmtBusca->execute(['id' => $idConsumidor]);
            $row = $stmtBusca->fetch();
            if (!$row) {
                $this->conexao->rollBack();
                return false;
            }
            $idEndereco = (int) $row['id_endereco'];

            $stmtCons = $this->conexao->prepare('DELETE FROM consumidor WHERE id_consumidor = :id');
            $stmtCons->execute(['id' => $idConsumidor]);

            $stmtEnd = $this->conexao->prepare('DELETE FROM endereco WHERE id_endereco = :id');
            $stmtEnd->execute(['id' => $idEndereco]);

            $this->conexao->commit();
            return true;
        } catch (\Exception $e) {
            $this->conexao->rollBack();
            return false;
        }
    }
}
