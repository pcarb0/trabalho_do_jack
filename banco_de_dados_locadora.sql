-- TABELA 1: USUARIO
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    numero VARCHAR(20)
);

-- TABELA 2: FILMES
CREATE TABLE filmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    diretor VARCHAR(100),
    ano_publicacao INT,
    genero VARCHAR(50),
    sinopse TEXT
);

-- TABELA 3: REVIEWS
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,     -- Equivale ao "autor"
    filme_id INT NOT NULL,       -- Equivale ao "titulo" do filme avaliado
    conteudo TEXT NOT NULL,      -- Equivale ao "review em si"
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Criando as conexões com as outras tabelas:
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (filme_id) REFERENCES filmes(id)
);trabalho

-- TABELA EXTRA: ALUGUEIS (Para representar os "filmes alugados" dos usuários)
CREATE TABLE alugueis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    filme_id INT NOT NULL,
    data_aluguel DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_devolucao DATETIME,
    
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (filme_id) REFERENCES filmes(id)
);