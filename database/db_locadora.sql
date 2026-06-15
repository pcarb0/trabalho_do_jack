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

INSERT INTO filmes (titulo, diretor, ano_publicacao, genero, sinopse, nota_media, poster_url) VALUES
('O Poderoso Chefão', 'Francis Ford Coppola', 1972, 'Drama', 'O patriarca idoso de uma dinastia do crime organizado transfere o controle de seu império clandestino para seu filho relutante.', 9.2, 'poderoso_chefao.png'),
('Um Sonho de Liberdade', 'Frank Darabont', 1994, 'Drama', 'Dois homens presos se conhecem ao longo de vários anos, encontrando consolo e eventual redenção através de atos de decência comum.', 9.3, 'sonho_de_liberdade.png'),
('Batman: O Cavaleiro das Trevas', 'Christopher Nolan', 2008, 'Ação', 'Quando a ameaça conhecida como o Coringa surge de seu passado, ela causa estragos e caos no povo de Gotham.', 9.0, 'cavaleiro_das_trevas.png'),
('A Lista de Schindler', 'Steven Spielberg', 1993, 'Biografia', 'Na Polônia ocupada pelos alemães durante a Segunda Guerra Mundial, o industrial Oskar Schindler gradualmente se preocupa com sua força de trabalho judia.', 9.0, 'lista_de_schindler.png'),
('Pulp Fiction: Tempo de Violência', 'Quentin Tarantino', 1994, 'Crime', 'As vidas de dois assassinos da máfia, um pugilista, a esposa de um gângster e um par de assaltantes de restaurante se entrelaçam.', 8.9, 'pulp_fiction.png'),
('O Senhor dos Anéis: O Retorno do Rei', 'Peter Jackson', 2003, 'Aventura', 'Gandalf e Aragorn lideram o Mundo dos Homens contra o exército de Sauron para desviar o olhar do Portador do Anel, Frodo.', 9.0, 'retorno_do_rei.png'),
('Clube da Luta', 'David Fincher', 1999, 'Drama', 'Um funcionário de escritório insone e um criador de sabão descuidado formam um clube de luta underground que evolui para algo muito maior.', 8.8, 'clube_da_luta.png'),
('A Origem', 'Christopher Nolan', 2010, 'Ficção Científica', 'Um ladrão que rouba segredos corporativos por meio do uso de tecnologia de compartilhamento de sonhos recebe a tarefa inversa de plantar uma ideia.', 8.8, 'a_origem.png'),
('Matrix', 'Lana Wachowski, Lilly Wachowski', 1999, 'Ficção Científica', 'Quando uma bela desconhecida leva o hacker Neo para um submundo proibido, ele descobre a chocante verdade: a vida que ele conhece é uma ilusão.', 8.7, 'matrix.png'),
('Forrest Gump: O Contador de Histórias', 'Robert Zemeckis', 1994, 'Drama', 'As presidências de Kennedy e Johnson, os eventos do Vietnã, Watergate e outras histórias se desenrolam do ponto de vista de um homem do Alabama.', 8.8, 'forrest_gump.png'),
('O Império Contra-Ataca', 'Irvin Kershner', 1980, 'Ficção Científica', 'Depois que os Rebeldes são brutalmente dominados pelo Império no planeta de gelo Hoth, Luke Skywalker inicia seu treinamento Jedi com Yoda.', 8.7, 'imperio_contra_ataca.png'),
('Gladiador', 'Ridley Scott', 2000, 'Ação', 'Um ex-general romano busca vingança contra o imperador corrupto que assassinou sua família e o enviou para a escravidão.', 8.5, 'gladiador.png'),
('O Rei Leão', 'Roger Allers, Rob Minkoff', 1994, 'Animação', 'O príncipe leão Simba e seu pai são alvos de seu tio amargurado, que quer assumir o trono a qualquer custo.', 8.5, 'rei_leao.png'),
('O Silêncio dos Inocentes', 'Jonathan Demme', 1991, 'Suspense', 'Uma jovem cadete do FBI deve receber a ajuda de um assassino canibal encarcerado para ajudar a capturar outro assassino em série.', 8.6, 'silencio_dos_inocentes.png'),
('Cidade de Deus', 'Fernando Meirelles, Kátia Lund', 2002, 'Drama', 'Nas favelas do Rio de Janeiro, dois caminhos de jovens se separam: um se torna fotógrafo e o outro um chefe do tráfico.', 8.6, 'cidade_de_deus.png'),
('Interestelar', 'Christopher Nolan', 2014, 'Ficção Científica', 'Uma equipe de exploradores viaja através de um buraco de minhoca no espaço em uma tentativa de garantir a sobrevivência da humanidade.', 8.6, 'interestelar.png'),
('À Espera de um Milagre', 'Frank Darabont', 1999, 'Drama', 'As vidas dos guardas do corredor da morte mudam drasticamente quando um dos detentos demonstra ter um dom milagroso de cura.', 8.6, 'espera_de_un_milagre.png'),
('Os Vingadores', 'Joss Whedon', 2012, 'Ação', 'Os heróis mais poderosos da Terra devem se unir e aprender a lutar em equipe se quiserem impedir o travesso Loki de escravizar a humanidade.', 8.0, 'vingadores.png'),
('Titanic', 'James Cameron', 1997, 'Romance', 'Uma aristocrata de dezessete anos se apaixona por um artista gentil, mas pobre, a bordo do luxuoso e azarado R.M.S. Titanic.', 7.9, 'titanic.png'),
('Coringa', 'Todd Phillips', 2019, 'Drama', 'Isolado, intimidado e desconsiderado pela sociedade, o comediante fracassado Arthur Fleck inicia uma espiral de loucura e revolta sangrenta.', 8.4, 'coringa.png');

INSERT INTO reviews (usuario_id, filme_id, conteudo, nota) VALUES
(1, 1, 'Um clássico absoluto do cinema. A atuação de Marlon Brando é impecável.', 10),
(1, 3, 'O melhor filme de super-herói já feito. Heath Ledger eterno como Coringa.', 10),
(1, 5, 'Diálogos rápidos, trilha sonora incrível e narrativa não-linear genial.', 9),
(1, 8, 'Complexo, visualmente deslumbrante e com um roteiro que te faz explodir a cabeça.', 9),
(1, 9, 'Revolucionou os efeitos visuais e a ficção científica no cinema.', 9),
(1, 13, 'Marcou a minha infância. A trilha sonora do Hans Zimmer é emocionante.', 10),
(1, 15, 'Uma obra-prima do cinema nacional. Ritmo frenético e cru.', 10),
(1, 16, 'Visualmente perfeito e com uma pegada científica muito bem trabalhada.', 9),
(1, 19, 'Uma das maiores produções da história. Romântico e trágico na medida certa.', 8),
(1, 20, 'Joaquin Phoenix teve uma atuação digna de Oscar. Angustiante e necessário.', 9);

INSERT INTO alugueis (usuario_id, filme_id, data_aluguel, data_devolucao) VALUES
(1, 2, '2026-06-01 10:00:00', '2026-06-03 14:30:00'),
(1, 4, '2026-06-02 11:15:00', '2026-06-05 09:00:00'),
(1, 6, '2026-06-04 18:20:00', '2026-06-07 17:00:00'),
(1, 7, '2026-06-05 14:00:00', '2026-06-06 13:45:00'),
(1, 10, '2026-06-06 09:30:00', '2026-06-09 11:00:00'),
(1, 11, '2026-06-08 20:00:00', '2026-06-11 19:30:00'),
(1, 12, '2026-06-09 16:45:00', '2026-06-12 15:00:00'),
(1, 14, '2026-06-10 13:10:00', '2026-06-12 12:00:00'),
(1, 17, '2026-06-12 19:00:00', '2026-06-15 18:30:00'),
(1, 18, '2026-06-13 15:22:00', NULL);

