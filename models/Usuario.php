<?php
// Model da entidade Usuario — adaptado da antiga classe Pessoa.
// Mapeia os campos reais da tabela `usuario` do banco de dados.
class Usuario
{
    private ?int $id;
    private string $email;
    private string $cpf;
    private string $senha;
    private string $dataNascimento;

    public function __construct(
        ?int   $id,
        string $email,
        string $cpf,
        string $senha          = '',
        string $dataNascimento = ''
    ) {
        $this->id             = $id;
        $this->email          = trim($email);
        $this->cpf            = preg_replace('/\D+/', '', $cpf);
        $this->senha          = $senha;
        $this->dataNascimento = $dataNascimento;
    }

    public function getId(): ?int            { return $this->id; }
    public function getEmail(): string       { return $this->email; }
    public function getCpf(): string         { return $this->cpf; }
    public function getSenha(): string       { return $this->senha; }
    public function getDataNascimento(): string { return $this->dataNascimento; }

    public function validar(bool $exigirSenha = false): array
    {
        $erros = [];

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }

        if (strlen($this->cpf) !== 11) {
            $erros[] = 'O CPF deve ter 11 dígitos.';
        }

        if (trim($this->dataNascimento) === '') {
            $erros[] = 'A data de nascimento é obrigatória.';
        }

        if ($exigirSenha && strlen($this->senha) < 5) {
            $erros[] = 'A senha deve ter pelo menos 5 caracteres.';
        }

        if (!$exigirSenha && $this->senha !== '' && strlen($this->senha) < 5) {
            $erros[] = 'Se informar uma nova senha, ela deve ter pelo menos 5 caracteres.';
        }

        return $erros;
    }
}
