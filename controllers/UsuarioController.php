<?php
require_once __DIR__ . '/../dao/UsuarioDAO.php';

// Controller da área de gerenciamento de usuários.
// Substitui o antigo PessoaController, agora operando sobre a tabela `usuario`.
class UsuarioController
{
    private UsuarioDAO $dao;

    public function __construct()
    {
        $this->dao = new UsuarioDAO();
    }

    private function proteger(): void
    {
        // Verifica a chave correta da sessão: id_usuario (definida pelo AuthController).
        $usuarioValido = isset($_SESSION['usuario'])
            && is_array($_SESSION['usuario'])
            && !empty($_SESSION['usuario']['id_usuario']);

        if (!$usuarioValido) {
            unset($_SESSION['usuario']);
            $_SESSION['mensagem'] = 'Faça login para acessar a área restrita.';
            header('Location: index.php?acao=login');
            exit;
        }
    }

    public function listar(): void
    {
        $this->proteger();

        $usuarios = $this->dao->listar();
        $mensagem = $_SESSION['mensagem'] ?? null;
        unset($_SESSION['mensagem']);

        require __DIR__ . '/../views/usuarios/listar.php';
    }

    public function novo(): void
    {

        $usuario = [
            'id_usuario'      => null,
            'email'           => '',
            'cpf'             => '',
            'senha'           => '',
            'data_nascimento' => '',
        ];
        $acaoFormulario = 'salvar_usuario';
        $titulo         = 'Novo usuário';
        $erros          = [];

        require __DIR__ . '/../views/usuarios/formulario.php';
    }

    public function editar(int $id): void
    {
        $this->proteger();

        $usuario = $this->dao->buscarPorId($id);

        if (!$usuario) {
            $_SESSION['mensagem'] = 'Usuário não encontrado.';
            header('Location: index.php?acao=listar_usuarios');
            exit;
        }

        $usuario['senha'] = '';
        $acaoFormulario   = 'atualizar_usuario';
        $titulo           = 'Editar usuário';
        $erros            = [];

        require __DIR__ . '/../views/usuarios/formulario.php';
    }

    public function salvar(array $dados): void
    {
        $tipo = strtolower($dados['tipo'] ?? '');

        $model = new Usuario(
            null,
            $dados['email']           ?? '',
            $dados['cpf']             ?? '',
            $dados['senha']           ?? '',
            $dados['data_nascimento'] ?? ''
        );
        $erros = $model->validar(true);

        if (!in_array($tipo, ['consumidor', 'produtor', 'os_dois'], true)) {
            $erros[] = 'Selecione um perfil válido.';
        }

        if ($erros) {
            $usuario = [
                'id_usuario'      => null,
                'email'           => $dados['email']           ?? '',
                'cpf'             => $dados['cpf']             ?? '',
                'senha'           => '',
                'data_nascimento' => $dados['data_nascimento'] ?? '',
                'tipo'            => $tipo,
            ];
            $acaoFormulario = 'salvar_usuario';
            $titulo         = 'Novo usuário';
            require __DIR__ . '/../views/usuarios/formulario.php';
            return;
        }

        $idUsuario = $this->dao->salvar($model);
        if ($idUsuario === false) {
            $_SESSION['mensagem'] = 'Não foi possível cadastrar o usuário.';
            header('Location: index.php?acao=novo');
            exit;
        }

        $_SESSION['cadastro_perfil'] = [
            'id_usuario' => $idUsuario,
            'tipo'       => $tipo,
            'proximo'    => $tipo === 'os_dois' ? 'produtor' : null,
        ];

        if ($tipo === 'produtor') {
            header('Location: index.php?acao=novo_produtor&id_usuario=' . $idUsuario);
            exit;
        }

        if ($tipo === 'os_dois') {
            header('Location: index.php?acao=novo_consumidor&id_usuario=' . $idUsuario . '&proximo=produtor');
            exit;
        }

        header('Location: index.php?acao=novo_consumidor&id_usuario=' . $idUsuario);
        exit;
    }

    public function atualizar(array $dados): void
    {
        $this->proteger();

        $registroAtual = $this->dao->buscarPorId((int) ($dados['id'] ?? 0));

        if (!$registroAtual) {
            $_SESSION['mensagem'] = 'Usuário não encontrado.';
            header('Location: index.php?acao=listar_usuarios');
            exit;
        }

        $model = new Usuario(
            (int) ($dados['id']  ?? 0),
            $dados['email']           ?? '',
            $dados['cpf']             ?? '',
            $dados['senha']           ?? '',
            $dados['data_nascimento'] ?? ''
        );
        $erros = $model->validar(false);

        if ($erros) {
            $usuario = [
                'id_usuario'      => $dados['id']             ?? null,
                'email'           => $dados['email']           ?? '',
                'cpf'             => $dados['cpf']             ?? '',
                'senha'           => '',
                'data_nascimento' => $dados['data_nascimento'] ?? '',
            ];
            $acaoFormulario = 'atualizar_usuario';
            $titulo         = 'Editar usuário';
            require __DIR__ . '/../views/usuarios/formulario.php';
            return;
        }

        $this->dao->atualizar($model);
        $_SESSION['mensagem'] = $model->getSenha() !== ''
            ? 'Usuário e senha atualizados com sucesso.'
            : 'Usuário atualizado com sucesso.';
        header('Location: index.php?acao=listar_usuarios');
        exit;
    }

    public function excluir(int $id): void
    {
        $this->proteger();

        $this->dao->excluir($id);
        $_SESSION['mensagem'] = 'Usuário excluído com sucesso.';
        header('Location: index.php?acao=listar_usuarios');
        exit;
    }
}
