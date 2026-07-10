<?php

class Endereco {
    private ?int $id;
    private string $logradouro;
    private ?int $numero;
    private string $bairro;
    private ?string $complemento;
    private string $cidade;
    private string $estado;

    public function __construct(?int $id, string $logradouro, ?int $numero, string $bairro, ?string $complemento, string $cidade, string $estado) {
        $this->id = $id;
        $this->logradouro = $logradouro;
        $this->numero = $numero;
        $this->bairro = $bairro;
        $this->complemento = $complemento;
        $this->cidade = $cidade;
        $this->estado = $estado;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLogradouro(): string {
        return $this->logradouro;
    }

    public function getNumero(): ?int {
        return $this->numero;
    }

    public function getBairro(): string {
        return $this->bairro;
    }

    public function getComplemento(): ?string {
        return $this->complemento;
    }

    public function getCidade(): string {
        return $this->cidade;
    }

    public function getEstado(): string {
        return $this->estado;
    }
}
