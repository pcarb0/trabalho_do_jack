-- TABELA 1: USUARIO (Continua igual)
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    username VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    numero VARCHAR(20),
    adminstrador BOOLEAN DEFAULT FALSE
    
);

-- TABELA 2: FILMES
CREATE TABLE filmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    diretor VARCHAR(100),
    ano_publicacao INT,
    genero VARCHAR(50),
    sinopse TEXT,
    nota_media DECIMAL(3,1) DEFAULT 0.0,
    poster_url VARCHAR(255)
);

-- TABELA 3: REVIEWS
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    filme_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    nota INT NOT NULL,               
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT chk_nota CHECK (nota >= 1 AND nota <= 10),
    
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (filme_id) REFERENCES filmes(id)
);

-- TABELA EXTRA: ALUGUEIS (Continua igual)
CREATE TABLE alugueis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    filme_id INT NOT NULL,
    data_aluguel DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_devolucao DATETIME,
    
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (filme_id) REFERENCES filmes(id)
);

SELECT f.titulo, IFNULL(AVG(r.nota), 0) AS media_das_notas
FROM filmes f
LEFT JOIN reviews r ON f.id = r.filme_id
GROUP BY f.id, f.titulo;

INSERT INTO usuario (username, senha, adminstrador) 
VALUES ('admin', '123', 1);

