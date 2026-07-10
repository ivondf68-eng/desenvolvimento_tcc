<?php
require_once __DIR__ . '/../dao/AuthDAO.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';

header('Content-Type: application/json; charset=utf-8');

class AuthController
{
    private AuthDAO $dao;

    public function __construct()
    {
        $this->dao = new AuthDAO();
    }

    public function responder (array $dados, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($dados);
        exit;
    }

    public function lerDados(): array
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);
        return is_array($dados) ? $dados : [];
    }

    public function logado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    public function entrar(): void
    {
        // Lê o JSON enviado pelo Auth.js
        $dados = $this->lerDados();
        // Para login só precisamos de email e senha; os outros campos ficam vazios.
        $usuario = new UsuarioLogin(null, $dados['email'] ?? '', $dados['senha'] ?? '', '', '');
        $erros   = $usuario->validar();

        if ($erros) {
            $this->responder(['erro' => implode(' ', $erros)], 422);
            return;
        }

        $pessoa = $this->dao->autenticar($usuario);

        if (!$pessoa) {
            $this->responder(['erro' => 'E-mail ou senha inválidos.'], 401);
            return;
        }

        $_SESSION['usuario'] = [
            'id_usuario' => $pessoa['id_usuario'],
            'nome'       => $pessoa['nome'] ?? $pessoa['email'],
            'email'      => $pessoa['email'],
        ];

        $this->responder(['mensagem' => 'Login realizado com sucesso.'], 200);
    }

    public function perfisDisponiveis(): void
    {
        $idUsuario = (int) ($_SESSION['usuario']['id_usuario'] ?? 0);
        if (!$idUsuario) {
            $this->responder(['erro' => 'Não autenticado.'], 401);
            return;
        }

        require_once __DIR__ . '/../config/Database.php';
        $db = Database::conectar();

        $temConsumidor = (bool) $db->query(
            "SELECT 1 FROM consumidor WHERE id_usuario = $idUsuario LIMIT 1"
        )->fetch();

        $temProdutor = (bool) $db->query(
            "SELECT 1 FROM produtor WHERE id_usuario = $idUsuario LIMIT 1"
        )->fetch();

        $this->responder([
            'consumidor' => $temConsumidor,
            'produtor'   => $temProdutor,
        ]);
    }

    public function sair(): void
    {
        unset($_SESSION['usuario']);
        header('Location: index.html');
        exit;
    }

    public function mostrarLogin(array $erros = []): void
    {
        require __DIR__ . '/../index.html';
    }
}
