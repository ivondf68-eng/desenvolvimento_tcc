<?php

class Produto
{
    private ?int $id;
    private string $nome;
    private string $imagem;
    private string $descricao;
    private float $preco;
    private int $estoque;
    private bool $ativo;
    private int $idCategoria;
    private int $idProdutor;

    public function __construct(
        ?int $id,
        string $nome,
        string $imagem,
        string $descricao,
        float $preco,
        int $estoque,
        bool $ativo,
        int $idCategoria,
        int $idProdutor
    ) {
        $this->id           = $id;
        $this->nome         = trim($nome);
        $this->imagem       = trim($imagem) !== '' ? trim($imagem) : 'produto.png';
        $this->descricao    = trim($descricao);
        $this->preco        = $preco;
        $this->estoque      = $estoque;
        $this->ativo        = $ativo;
        $this->idCategoria  = $idCategoria;
        $this->idProdutor   = $idProdutor;
    }

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getImagem(): string { return $this->imagem; }
    public function getDescricao(): string { return $this->descricao; }
    public function getPreco(): float { return $this->preco; }
    public function getEstoque(): int { return $this->estoque; }
    public function getAtivo(): bool { return $this->ativo; }
    public function getIdCategoria(): int { return $this->idCategoria; }
    public function getIdProdutor(): int { return $this->idProdutor; }
}