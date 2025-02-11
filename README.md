mariadb -u admin -padmin

CREATE DATABASE usuarios01;


USE usuarios01;


CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(50) NOT NULL
);


INSERT INTO usuarios (usuario, senha) VALUES 
    ('admin', 'admin'),
    ('lobo_guara', 'lobo_guara');


ALTER TABLE usuarios ADD COLUMN recuperar_senha TINYINT(1) DEFAULT 0;


UPDATE usuarios SET data_solicitacao = NOW() WHERE recuperar_senha = 1 AND data_solicitacao IS NULL;


CREATE TABLE pontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    data_hora DATETIME NOT NULL
);

