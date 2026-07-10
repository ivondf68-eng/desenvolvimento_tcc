<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    // ----------------------------------------------------------------
    // CRUD completo para a tabela usuario
    // ----------------------------------------------------------------

    public function listar(): array
    {
        $sql = 'SELECT id_usuario, email, cpf, data_nascimento FROM usuario ORDER BY id_usuario DESC';
        return $this->conexao->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql  = 'SELECT id_usuario, email, cpf, data_nascimento FROM usuario WHERE id_usuario = :id';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function salvar(Usuario $usuario): int|false
    {
        $sql  = 'INSERT INTO usuario (email, senha, cpf, data_nascimento)
                 VALUES (:email, :senha, :cpf, :data_nascimento)';
        $stmt = $this->conexao->prepare($sql);

        if (!$stmt->execute([
            'email'           => $usuario->getEmail(),
            'senha'           => password_hash($usuario->getSenha(), PASSWORD_DEFAULT),
            'cpf'             => $usuario->getCpf(),
            'data_nascimento' => $usuario->getDataNascimento(),
        ])) {
            return false;
        }

        $id = $this->conexao->lastInsertId();
        return $id !== false ? (int) $id : false;
    }

    public function atualizar(Usuario $usuario): bool
    {
        // Se a senha for informada, atualiza também; caso contrário, preserva a atual.
        if ($usuario->getSenha() !== '') {
            $sql  = 'UPDATE usuario
                     SET email = :email, senha = :senha, cpf = :cpf, data_nascimento = :data_nascimento
                     WHERE id_usuario = :id';
            $stmt = $this->conexao->prepare($sql);

            return $stmt->execute([
                'id'              => $usuario->getId(),
                'email'           => $usuario->getEmail(),
                'senha'           => password_hash($usuario->getSenha(), PASSWORD_DEFAULT),
                'cpf'             => $usuario->getCpf(),
                'data_nascimento' => $usuario->getDataNascimento(),
            ]);
        }

        $sql  = 'UPDATE usuario
                 SET email = :email, cpf = :cpf, data_nascimento = :data_nascimento
                 WHERE id_usuario = :id';
        $stmt = $this->conexao->prepare($sql);

        return $stmt->execute([
            'id'              => $usuario->getId(),
            'email'           => $usuario->getEmail(),
            'cpf'             => $usuario->getCpf(),
            'data_nascimento' => $usuario->getDataNascimento(),
        ]);
    }

    public function excluir(int $id): bool
    {
        $sql  = 'DELETE FROM usuario WHERE id_usuario = :id';
        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->conexao->prepare(
            'SELECT 1 FROM usuario WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    // ----------------------------------------------------------------
    // Usado pelo AuthController no cadastro via UsuarioLogin
    // ----------------------------------------------------------------

    public function cadastrarUsuario(UsuarioLogin $usuario): ?int
    {
        $sql  = 'INSERT INTO usuario (email, senha, cpf, data_nascimento)
                 VALUES (:email, :senha, :cpf, :data_nascimento)';
        $stmt = $this->conexao->prepare($sql);

        if (!$stmt->execute([
            'email'           => $usuario->getEmail(),
            'senha'           => password_hash($usuario->getSenha(), PASSWORD_DEFAULT),
            'cpf'             => $usuario->getCpf(),
            'data_nascimento' => $usuario->getDataNascimento(),
        ])) {
            return null;
        }

        $insertId = $this->conexao->lastInsertId();
        return $insertId !== false ? (int) $insertId : null;
    }
}
