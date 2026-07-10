<?php
require_once __DIR__ . '/../dao/ProdutorDAO.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/Produtor.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';
require_once __DIR__ . '/../models/Endereco.php';

class ProdutorController
{
    private ProdutorDAO $dao;
    private UsuarioDAO $usuarioDAO;

    public function __construct()
    {
        $this->dao = new ProdutorDAO();
        $this->usuarioDAO = new UsuarioDAO();
    }

    private function proteger(): void
    {
        $cadastro = $_SESSION['cadastro_perfil'] ?? [];
        $valido = (isset($_SESSION['usuario'])
            && is_array($_SESSION['usuario'])
            && !empty($_SESSION['usuario']['id_usuario']))
            || ((int) ($cadastro['id_usuario'] ?? 0) > 0);

        if (!$valido) {
            unset($_SESSION['usuario']);
            $_SESSION['mensagem'] = 'Faça login para acessar a área restrita.';
            header('Location: index.php?acao=login');
            exit;
        }
    }

    public function novo(int $idUsuario = 0): void
    {
        $this->proteger();

        $dados = [
            'id_produtor' => null,
            'id_usuario' => $idUsuario,
            'id_endereco' => null,
            'nome' => '',
            'telefone' => '',
            'email' => '',
            'cpf' => '',
            'data_nascimento' => '',
            'logradouro' => '',
            'numero' => '',
            'bairro' => '',
            'complemento' => '',
            'cidade' => '',
            'estado' => '',
        ];

        if ($idUsuario > 0) {
            $usuario = $this->usuarioDAO->buscarPorId($idUsuario);
            if ($usuario) {
                $dados['email'] = $usuario['email'] ?? '';
                $dados['cpf'] = $usuario['cpf'] ?? '';
                $dados['data_nascimento'] = $usuario['data_nascimento'] ?? '';
            }
        }

        $acaoFormulario = 'salvar_produtor';
        $titulo = 'Novo Produtor';
        $erros = [];

        // Redireciona para o formulário HTML estático com o id_usuario na URL
        header('Location: views/cadastrarProdutor.html?id_usuario=' . $idUsuario);
        exit;
    }

    public function salvar(array $post): void
    {
        $this->proteger();

        $idUsuario = (int) ($post['id_usuario'] ?? 0);
        $usuario = $this->usuarioDAO->buscarPorId($idUsuario);
        if (!$usuario) {
            $_SESSION['mensagem'] = 'Usuário não encontrado para continuar o cadastro.';
            header('Location: index.php?acao=novo');
            exit;
        }

        $usuarioLogin = new UsuarioLogin(
            $idUsuario,
            $usuario['email'] ?? '',
            '',
            $usuario['cpf'] ?? '',
            $usuario['data_nascimento'] ?? ''
        );

        $endereco = new Endereco(
            null,
            $post['logradouro'] ?? '',
            (int) ($post['numero'] ?? 0),
            $post['bairro'] ?? '',
            $post['complemento'] ?? '',
            $post['cidade'] ?? '',
            $post['estado'] ?? ''
        );

        $produtor = new Produtor(
            null,
            $post['nome'] ?? '',
            'foto.png',
            $post['telefone'] ?? '',
            $usuarioLogin,
            $endereco
        );

        $erros = $this->validar($produtor);
        if ($erros) {
            // Redireciona de volta ao formulário com o id_usuario
            header('Location: index.php?acao=novo_produtor&id_usuario=' . $idUsuario);
            exit;
        }

        $idProdutor = $this->dao->cadastrar($produtor);
        if (!$idProdutor) {
            $_SESSION['mensagem'] = 'Erro ao cadastrar o produtor.';
            header('Location: index.php?acao=novo_produtor&id_usuario=' . $idUsuario);
            exit;
        }

        $_SESSION['usuario'] = [
            'id_usuario' => $idUsuario,
            'nome'       => $post['nome'] ?? ($usuario['email'] ?? ''),
            'email'      => $usuario['email'] ?? '',
        ];

        $_SESSION['mensagem'] = 'Produtor cadastrado com sucesso.';
        unset($_SESSION['cadastro_perfil']);
        header('Location: views/produtorMain.html');
        exit;
    }

    private function validar(Produtor $produtor): array
    {
        $erros = [];
        if (trim($produtor->getNome()) === '') {
            $erros[] = 'O nome do produtor é obrigatório.';
        }
        if (trim($produtor->getEndereco()->getLogradouro()) === '') {
            $erros[] = 'O logradouro é obrigatório.';
        }
        if (trim($produtor->getEndereco()->getCidade()) === '') {
            $erros[] = 'A cidade é obrigatória.';
        }
        if (strlen(trim($produtor->getEndereco()->getEstado())) !== 2) {
            $erros[] = 'Informe a UF com 2 letras.';
        }
        return $erros;
    }

    public function buscar(): void
    {
        $this->proteger();

        $idUsuario = (int)($_GET['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
        if ($idUsuario <= 0) {
            http_response_code(400);
            echo json_encode(['erro' => 'Usuário inválido']);
            return;
        }

        $dados = $this->dao->buscarPorUsuario($idUsuario);
        if (!$dados) {
            http_response_code(404);
            echo json_encode(['erro' => 'Produtor não encontrado']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($dados);
    }

    public function atualizar(array $post): void
    {
        $this->proteger();

        $idUsuario = (int)($post['id_usuario'] ?? 0);
        $dados = $this->dao->buscarPorUsuario($idUsuario);
        if (!$dados) {
            http_response_code(404);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produtor não encontrado']);
            return;
        }

        // Monta objetos
        $usuarioLogin = new UsuarioLogin(
            $idUsuario,
            $dados['email'] ?? '',
            '',
            $dados['cpf'] ?? '',
            $dados['data_nascimento'] ?? ''
        );

        $endereco = new Endereco(
            (int)$dados['id_endereco'],
            $post['logradouro'] ?? '',
            (int)($post['numero'] ?? 0),
            $post['bairro'] ?? '',
            $post['complemento'] ?? '',
            $post['cidade'] ?? '',
            $post['estado'] ?? ''
        );

        // Foto de perfil (se enviada)
        $fotoPerfil = $dados['foto_perfil'] ?? 'foto.png';
        if (!empty($_FILES['foto_perfil']['name'])) {
            $nomeArquivo = uniqid('foto_') . '_' . basename($_FILES['foto_perfil']['name']);
            $destino = __DIR__ . '/../images/' . $nomeArquivo;
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
                $fotoPerfil = $nomeArquivo;
            }
        }

        $produtor = new Produtor(
            (int)$dados['id_produtor'],
            $post['nome'] ?? '',
            $fotoPerfil,
            $post['telefone'] ?? '',
            $usuarioLogin,
            $endereco
        );

        $erros = $this->validar($produtor);
        if ($erros) {
            echo json_encode(['sucesso' => false, 'mensagem' => implode(', ', $erros)]);
            return;
        }

        $ok = $this->dao->atualizar($produtor, (int)$dados['id_endereco']);
        echo json_encode(['sucesso' => $ok, 'mensagem' => $ok ? 'Perfil atualizado com sucesso.' : 'Erro ao atualizar perfil.']);
    }

    public function excluir(): void
    {
        $this->proteger();

        $idUsuario = (int)($_POST['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
        if ($idUsuario <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido']);
            return;
        }

        $dados = $this->dao->buscarPorUsuario($idUsuario);
        if (!$dados) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Produtor não encontrado']);
            return;
        }

        $ok = $this->dao->excluir((int)$dados['id_produtor']);

        if ($ok) {
            // O perfil de produtor não existe mais: encerra a sessão do usuário
            unset($_SESSION['usuario']);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso'  => $ok,
            'mensagem' => $ok ? 'Perfil de produtor excluído com sucesso.' : 'Erro ao excluir o perfil de produtor.',
            'detalhe'  => $ok ? null : $this->dao->getUltimoErro(),
        ]);
    }
}
