-- Cria o banco de dados
CREATE DATABASE IF NOT EXISTS gerenciador_logistico
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gerenciador_logistico;

-- Produtos
CREATE TABLE produtos (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          nome VARCHAR(120) NOT NULL,
                          peso DECIMAL(10,3) NOT NULL,
                          valor DECIMAL(10,2) NOT NULL
);

-- Endereços (servem como origem ou destino)
CREATE TABLE enderecos (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           apelido VARCHAR(80) NOT NULL,
                           cep VARCHAR(9) NOT NULL,
                           logradouro VARCHAR(150) NOT NULL,
                           numero VARCHAR(10) NOT NULL,
                           complemento VARCHAR(80) NULL,
                           bairro VARCHAR(80) NOT NULL,
                           cidade VARCHAR(80) NOT NULL,
                           uf CHAR(2) NOT NULL
);

-- Transportadores
CREATE TABLE transportadores (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 nome VARCHAR(120) NOT NULL,
                                 cnpj VARCHAR(18) NOT NULL,
                                 telefone VARCHAR(20) NULL,
                                 email VARCHAR(120) NULL,
                                 placa VARCHAR(10) NULL
);

-- Fretes: ligam 1 transportador, 1 origem e 1 destino
CREATE TABLE fretes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        transportador_id INT NOT NULL,
                        origem_id INT NOT NULL,
                        destino_id INT NOT NULL,
                        data_frete DATE NOT NULL,
                        valor_frete DECIMAL(10,2) NOT NULL DEFAULT 0,
                        observacoes TEXT NULL,
                        FOREIGN KEY (transportador_id) REFERENCES transportadores(id),
                        FOREIGN KEY (origem_id) REFERENCES enderecos(id),
                        FOREIGN KEY (destino_id) REFERENCES enderecos(id)
);

-- Liga cada frete a vários produtos (relação 1 para muitos)
CREATE TABLE frete_produtos (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                frete_id INT NOT NULL,
                                produto_id INT NOT NULL,
                                quantidade INT NOT NULL DEFAULT 1,
                                FOREIGN KEY (frete_id) REFERENCES fretes(id) ON DELETE CASCADE,
                                FOREIGN KEY (produto_id) REFERENCES produtos(id)
);