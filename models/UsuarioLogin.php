<?php
// Model usado para autenticação e cadastro de usuário.
class UsuarioLogin
{
    private ?int $id;
    private string $email;
    private string $senha;
    private string $cpf;
    private string $data_nascimento;

    public function __construct(?int $id, string $email, string $senha, string $cpf, string $data_nascimento)
    {
        $this->id              = $id;
        $this->email           = trim($email);
        $this->senha           = $senha;
        $this->cpf             = $cpf;
        $this->data_nascimento = $data_nascimento;
    }

    public function getId(): ?int          { return $this->id; }
    public function setId(?int $id): void  { $this->id = $id; }

    public function getEmail(): string           { return $this->email; }
    public function getSenha(): string           { return $this->senha; }
    public function getCpf(): string             { return $this->cpf; }
    public function getDataNascimento(): string  { return $this->data_nascimento; }

    public function validar(): array
    {
        $erros = [];
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }
        if ($this->senha === '') {
            $erros[] = 'Informe a senha.';
        }
        return $erros;
    }
}
