CREATE TABLE usuario (
id_usuario SERIAL PRIMARY KEY,
email VARCHAR(150) NOT NULL,
senha VARCHAR(255) NOT NULL,
cpf VARCHAR(11) NOT NULL,
data_nascimento DATE NOT NULL
);

CREATE TABLE endereco (
id_endereco SERIAL PRIMARY KEY,
cidade VARCHAR(100) NOT NULL,
estado CHAR(2) NOT NULL,
logradouro VARCHAR(200) NOT NULL,
numero INT,
bairro VARCHAR(100),
complemento VARCHAR(100)
);

CREATE TABLE consumidor (
id_consumidor SERIAL PRIMARY KEY,
nome VARCHAR(150) NOT NULL,
foto_perfil VARCHAR(300),
telefone VARCHAR(20),
id_usuario INT NOT NULL,
id_endereco INT NOT NULL,
CONSTRAINT fk_usuario_consumidor FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
CONSTRAINT fk_endereco_consumidor FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE produtor (
id_produtor SERIAL PRIMARY KEY,
nome VARCHAR(150) NOT NULL,
foto_perfil VARCHAR(300),
telefone VARCHAR(20),
id_usuario INT NOT NULL,
id_endereco INT NOT NULL,
CONSTRAINT fk_usuario_produtor FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
CONSTRAINT fk_endereco_produtor FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE categoria (
id_categoria SERIAL PRIMARY KEY,
nome VARCHAR(150) NOT NULL
);

CREATE TABLE produto (
id_produto SERIAL PRIMARY KEY,
nome VARCHAR(150) NOT NULL,
imagem VARCHAR(300) NOT NULL,
descricao TEXT,
preco NUMERIC(10,2) NOT NULL,
estoque INT NOT NULL,
ativo BOOLEAN NOT NULL,
id_categoria INT NOT NULL,
id_produtor INT NOT NULL,
CONSTRAINT fk_categoria FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria),
CONSTRAINT fk_produtor_produto FOREIGN KEY (id_produtor) REFERENCES produtor(id_produtor)
);

CREATE TABLE pedido (
id_pedido SERIAL PRIMARY KEY,
total NUMERIC(10,2),
status BOOLEAN NOT NULL,
agendamento TIMESTAMP NOT NULL,
criado TIMESTAMP NOT NULL,
observacoes TEXT,
id_consumidor INT NOT NULL,
id_produtor INT NOT NULL,
CONSTRAINT fk_consumidor FOREIGN KEY (id_consumidor) REFERENCES consumidor(id_consumidor),
CONSTRAINT fk_produtor FOREIGN KEY (id_produtor) REFERENCES produtor(id_produtor)
);

CREATE TABLE pedido_produto (
id_pedido_produto SERIAL PRIMARY KEY,
quantidade INT NOT NULL,
preco_unidade NUMERIC(10,2) NOT NULL,
total NUMERIC(10,2) NOT NULL,
id_pedido INT NOT NULL,
id_produto INT NOT NULL,
CONSTRAINT fk_pedido FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
CONSTRAINT fk_produto FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);

CREATE TABLE carrinho (
id_carrinho SERIAL PRIMARY KEY,
id_consumidor INT NOT NULL UNIQUE,
criado_em TIMESTAMP NOT NULL DEFAULT NOW(),
atualizado_em TIMESTAMP NOT NULL DEFAULT NOW(),
CONSTRAINT fk_consumidor_carrinho FOREIGN KEY (id_consumidor) REFERENCES consumidor(id_consumidor)
);

CREATE TABLE carrinho_item (
id_carrinho_item SERIAL PRIMARY KEY,
id_carrinho INT NOT NULL,
id_produto INT NOT NULL,
quantidade INT NOT NULL DEFAULT 1,
preco_unidade NUMERIC(10,2) NOT NULL,
criado_em TIMESTAMP NOT NULL DEFAULT NOW(),
atualizado_em TIMESTAMP NOT NULL DEFAULT NOW(),
CONSTRAINT fk_carrinho FOREIGN KEY (id_carrinho) REFERENCES carrinho(id_carrinho),
CONSTRAINT fk_produto_carrinho FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);
