<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';

class AuthDAO
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = Database::conectar();
    }

    public function autenticar(UsuarioLogin $usuario): ?array
    {
        // Busca pelo e-mail na tabela usuario
        $sql  = 'SELECT id_usuario, email, senha FROM usuario WHERE email = :email LIMIT 1';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['email' => $usuario->getEmail()]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $senhaInformada = $usuario->getSenha();
        $senhaSalva     = (string) $row['senha'];

        if(password_verify($usuario->getSenha(), (string) $row['senha'])) {
            return $row; // retorna id_usuario + email
        }

        return null;
    }
}
