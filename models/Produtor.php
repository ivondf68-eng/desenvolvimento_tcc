<?php

class Produtor
{
    private ?int $id;
    private string $nome;
    private string $foto_perfil;
    private string $telefone;
    private UsuarioLogin $usuarioLogin;
    private Endereco $endereco;

    public function __construct(
        ?int $id,
        string $nome,
        string $foto_perfil,
        string $telefone,
        UsuarioLogin $usuarioLogin,
        Endereco $endereco
    ) {
        $this->id           = $id;
        $this->nome         = trim($nome);
        $this->foto_perfil  = trim($foto_perfil) !== '' ? trim($foto_perfil) : 'foto.png';
        $this->telefone     = trim($telefone);
        $this->usuarioLogin = $usuarioLogin;
        $this->endereco     = $endereco;
    }

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getFotoPerfil(): string { return $this->foto_perfil; }
    public function getTelefone(): string { return $this->telefone; }
    public function getUsuarioLogin(): UsuarioLogin { return $this->usuarioLogin; }
    public function getEndereco(): Endereco { return $this->endereco; }
}
