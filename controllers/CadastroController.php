<?php
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';

class CadastroController
{
    private UsuarioDAO $usuarioDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
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

    public function cadastrar(): void
    {
        $dados = $this->lerDados();

        $email           = trim($dados['email']          ?? '');
        $senha           = $dados['senha']               ?? '';
        $cpf             = preg_replace('/\D/', '', $dados['cpf'] ?? '');
        $dataNascimento  = $dados['data_nascimento']     ?? '';
        $tipoPerfil      = $dados['tipo_perfil']         ?? '';

        $erros = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }
        if (strlen($senha) < 6) {
            $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        if (strlen($cpf) !== 11) {
            $erros[] = 'O CPF deve ter 11 dígitos.';
        }
        if (empty($dataNascimento)) {
            $erros[] = 'A data de nascimento é obrigatória.';
        }
        if (!in_array($tipoPerfil, ['consumidor', 'produtor', 'ambos'])) {
            $erros[] = 'Selecione um tipo de perfil válido.';
        }

        if ($erros) {
            $this->responder(['erro' => implode(' ', $erros)], 422);
            return;
        }

        $usuario = new UsuarioLogin(null, $email, $senha, $cpf, $dataNascimento);

        if ($this->usuarioDAO->emailExiste($email)) {
            $this->responder(['erro' => 'Este e-mail já está cadastrado.'], 409);
            return;
        }

        $idUsuario = $this->usuarioDAO->cadastrarUsuario($usuario);

        if (!$idUsuario) {
            $this->responder(['erro' => 'E-mail já cadastrado ou erro ao criar conta.'], 409);
            return;
        }

        $_SESSION['cadastro_perfil'] = [
            'id_usuario'  => $idUsuario,
            'tipo_perfil' => $tipoPerfil,
        ];

        $this->responder([
            'mensagem'    => 'Conta criada com sucesso.',
            'id_usuario'  => $idUsuario,
            'tipo_perfil' => $tipoPerfil,
        ], 201);
    }
}
