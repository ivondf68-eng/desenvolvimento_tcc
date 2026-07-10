<?php
require_once __DIR__ . '/../dao/ConsumidorDAO.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/Consumidor.php';
require_once __DIR__ . '/../models/UsuarioLogin.php';
require_once __DIR__ . '/../models/Endereco.php';

class ConsumidorController
{
    private ConsumidorDAO $dao;
    private UsuarioDAO $usuarioDAO;
    private string $imagemPadrao = 'foto.png';
    private array $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    private int $tamanhoMaximo = 2 * 1024 * 1024; // 2 MB
    private string $diretorioImagens;

    public function __construct()
    {
        $this->dao = new ConsumidorDAO();
        $this->usuarioDAO = new UsuarioDAO();
        $this->diretorioImagens = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images';
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

    public function listar(): void
    {
        $this->proteger();

        $consumidores = $this->dao->listar();
        $mensagem = $_SESSION['mensagem'] ?? null;
        unset($_SESSION['mensagem']);

        header('Location: views/consumidorMain.html');
        exit;
    }

    public function novo(int $idUsuario = 0): void
    {
        $this->proteger();

        $dados = $this->dadosVazios();
        if ($idUsuario > 0) {
            $usuario = $this->usuarioDAO->buscarPorId($idUsuario);
            if ($usuario) {
                $dados['id_usuario'] = $idUsuario;
                $dados['email'] = $usuario['email'] ?? '';
                $dados['cpf'] = $usuario['cpf'] ?? '';
                $dados['data_nascimento'] = $usuario['data_nascimento'] ?? '';
            }
        }

        header('Location: views/cadastrarConsumidor.html?id_usuario=' . $idUsuario);
        exit;
    }

    public function salvar(array $post): void
    {
        $this->proteger();

        $errosUpload = [];
        $foto = $this->processarUpload(true, $errosUpload);

        $idUsuario = (int) ($post['id_usuario'] ?? 0);

        if ($idUsuario > 0) {
            $usuarioExistente = $this->usuarioDAO->buscarPorId($idUsuario);
            if (!$usuarioExistente) {
                $_SESSION['mensagem'] = 'Usuário não encontrado para continuar o cadastro.';
                header('Location: index.php?acao=novo');
                exit;
            }

            $usuarioLogin = new UsuarioLogin(
                $idUsuario,
                $usuarioExistente['email'] ?? '',
                '',
                $usuarioExistente['cpf'] ?? '',
                $usuarioExistente['data_nascimento'] ?? ''
            );
        } else {
            $usuarioLogin = new UsuarioLogin(
                null,
                $post['email'] ?? '',
                $post['senha'] ?? '',
                $post['cpf'] ?? '',
                $post['data_nascimento'] ?? ''
            );
        }

        $numero = trim((string) ($post['numero'] ?? ''));
        $numeroEndereco = $numero === '' ? null : (int) $numero;

        $endereco = new Endereco(
            null,
            $post['logradouro'] ?? '',
            $numeroEndereco,
            $post['bairro'] ?? '',
            $post['complemento'] ?? '',
            $post['cidade'] ?? '',
            $post['estado'] ?? ''
        );

        $consumidor = new Consumidor(
            null,
            $post['nome'] ?? '',
            $foto,
            $post['telefone'] ?? '',
            $usuarioLogin,
            $endereco
        );

        $erros = array_merge($this->validar($consumidor, true, $idUsuario), $errosUpload);

        if ($erros) {
            $_SESSION['mensagem'] = implode(' ', $erros);
            header('Location: index.php?acao=novo_consumidor&id_usuario=' . $idUsuario);
            exit;
        }

        if ($idUsuario <= 0) {
            $novoUsuarioId = $this->usuarioDAO->registrar($usuarioLogin);
            if (!$novoUsuarioId) {
                $_SESSION['mensagem'] = 'Erro ao criar usuário.';
                header('Location: index.php?acao=novo_consumidor');
                exit;
            }

            $usuarioLogin = new UsuarioLogin(
                $novoUsuarioId,
                $usuarioLogin->getEmail(),
                $usuarioLogin->getSenha(),
                $usuarioLogin->getCpf(),
                $usuarioLogin->getDataNascimento()
            );

            $consumidor = new Consumidor(
                null,
                $post['nome'] ?? '',
                $foto,
                $post['telefone'] ?? '',
                $usuarioLogin,
                $endereco
            );

            $idUsuario = $novoUsuarioId;
        }

        $idConsumidor = $this->dao->cadastrar($consumidor);
        if (!$idConsumidor) {
            $_SESSION['mensagem'] = 'Erro ao cadastrar o consumidor.';
            header('Location: index.php?acao=novo_consumidor&id_usuario=' . $idUsuario);
            exit;
        }

        $_SESSION['usuario'] = [
            'id_usuario' => $idUsuario,
            'nome'       => $post['nome'] ?? '',
            'email'      => $usuarioLogin->getEmail(),
        ];

        unset($_SESSION['cadastro_perfil']);
        header('Location: views/consumidorMain.html');
        exit;
    }

    public function buscar(): void
    {
        $this->proteger();

        $idUsuario = (int)($_GET['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
        if ($idUsuario <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Usuário inválido']);
            return;
        }

        $dados = $this->dao->buscarPorUsuario($idUsuario);
        if (!$dados) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Consumidor não encontrado']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
    }

    public function editar(int $id): void
    {
        $this->proteger();

        $registro = $this->dao->buscarPorId($id);
        if (!$registro) {
            $_SESSION['mensagem'] = 'Consumidor não encontrado.';
            header('Location: views/consumidorMain.html');
            exit;
        }

        header('Location: views/editarConsumidor.html?id_consumidor=' . $id . '&id_usuario=' . $registro['id_usuario']);
        exit;
    }

    public function atualizar(array $post): void
    {
        $this->proteger();

        $idUsuario = (int)($post['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
        if ($idUsuario <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido']);
            return;
        }

        $registro = $this->dao->buscarPorUsuario($idUsuario);
        if (!$registro) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Consumidor não encontrado']);
            return;
        }

        $errosUpload = [];
        $foto = $this->processarUpload(false, $errosUpload, (string) ($registro['foto_perfil'] ?? $this->imagemPadrao));

        $usuarioLogin = new UsuarioLogin(
            (int) $registro['id_usuario'],
            $registro['email'] ?? '',
            '',
            $registro['cpf'] ?? '',
            $registro['data_nascimento'] ?? ''
        );

        $numero = trim((string) ($post['numero'] ?? ''));
        $numeroEndereco = $numero === '' ? null : (int) $numero;

        $endereco = new Endereco(
            (int) $registro['id_endereco'],
            $post['logradouro'] ?? '',
            $numeroEndereco,
            $post['bairro'] ?? '',
            $post['complemento'] ?? '',
            $post['cidade'] ?? '',
            $post['estado'] ?? ''
        );

        $consumidor = new Consumidor(
            (int) $registro['id_consumidor'],
            $post['nome'] ?? '',
            $foto,
            $post['telefone'] ?? '',
            $usuarioLogin,
            $endereco
        );

        $erros = array_merge($this->validar($consumidor, false, $idUsuario), $errosUpload);

        if ($erros) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => implode(', ', $erros),
            ]);
            return;
        }

        $ok = $this->dao->atualizar($consumidor, (int) $registro['id_endereco'], false);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => $ok,
            'mensagem' => $ok ? 'Consumidor atualizado com sucesso.' : 'Erro ao atualizar o consumidor.',
        ]);
    }

    public function excluir(int $id = 0): void
    {
        $this->proteger();

        $idUsuario = (int)($_POST['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
        if ($idUsuario <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido']);
            return;
        }

        $registro = $this->dao->buscarPorUsuario($idUsuario);
        if (!$registro) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Consumidor não encontrado']);
            return;
        }

        $ok = $this->dao->excluir((int) $registro['id_consumidor']);

        if ($ok) {
            unset($_SESSION['usuario']);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => $ok,
            'mensagem' => $ok ? 'Perfil de consumidor excluído com sucesso.' : 'Erro ao excluir o perfil de consumidor.',
        ]);
    }

    private function validar(Consumidor $consumidor, bool $novo, int $idUsuario = 0): array
    {
        $erros = [];
        $usr = $consumidor->getUsuarioLogin();
        $end = $consumidor->getEndereco();

        if (trim($consumidor->getNome()) === '') {
            $erros[] = 'O nome é obrigatório.';
        }

        if (!$novo) {
            // email/cpf/data/senha eu pego do usuário
            // então não valida esses campos como no cadastro
        } else {
            if (!filter_var($usr->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $erros[] = 'Informe um e-mail válido.';
            }
            if ($idUsuario <= 0 && trim($usr->getSenha()) === '') {
                $erros[] = 'A senha é obrigatória no cadastro.';
            }
            if (strlen(preg_replace('/\D/', '', $usr->getCpf())) !== 11) {
                $erros[] = 'CPF deve ter 11 dígitos.';
            }
            if (trim($usr->getDataNascimento()) === '') {
                $erros[] = 'A data de nascimento é obrigatória.';
            }
        }

        if (trim($end->getLogradouro()) === '') {
            $erros[] = 'O logradouro é obrigatório.';
        }
        if (trim($end->getCidade()) === '') {
            $erros[] = 'A cidade é obrigatória.';
        }
        if (strlen(trim($end->getEstado())) !== 2) {
            $erros[] = 'Informe a UF com 2 letras.';
        }

        return $erros;
    }

    private function processarUpload(bool $obrigatorio, array &$erros, string $imagemAtual = 'foto.png'): string
    {
        $erros = [];
        $arquivo = $_FILES['foto_perfil'] ?? null;

        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $obrigatorio ? $this->imagemPadrao : $imagemAtual;
        }

        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Falha no upload da foto.';
            return $imagemAtual;
        }

        if ((int) $arquivo['size'] > $this->tamanhoMaximo) {
            $erros[] = 'A foto deve ter no máximo 2 MB.';
            return $imagemAtual;
        }

        $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $this->extensoesPermitidas, true)) {
            $erros[] = 'Extensão inválida. Use jpg, jpeg, png ou gif.';
            return $imagemAtual;
        }

        if (!is_dir($this->diretorioImagens)) {
            mkdir($this->diretorioImagens, 0777, true);
        }

        $nomeArquivo = uniqid('consumidor_', true) . '.' . $extensao;
        $destino = $this->diretorioImagens . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (!move_uploaded_file((string) $arquivo['tmp_name'], $destino)) {
            $erros[] = 'Não foi possível salvar a foto enviada.';
            return $imagemAtual;
        }

        return $nomeArquivo;
    }

    private function dadosVazios(): array
    {
        return [
            'id_consumidor'   => null,
            'id_usuario'      => null,
            'id_endereco'     => null,
            'nome'            => '',
            'foto_perfil'     => $this->imagemPadrao,
            'telefone'        => '',
            'email'           => '',
            'senha'           => '',
            'cpf'             => '',
            'data_nascimento' => '',
            'logradouro'      => '',
            'numero'          => '',
            'bairro'          => '',
            'complemento'     => '',
            'cidade'          => '',
            'estado'          => '',
        ];
    }
}