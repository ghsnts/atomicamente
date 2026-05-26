CREATE DATABASE IF NOT EXISTS atomicamente_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atomicamente_db;

-- 1. Permissões de Acesso
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE
);

-- Garante a inserção dos perfis base
INSERT INTO roles (id, nome) VALUES (1, 'admin'), (2, 'estudante') 
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- 2. Utilizadores da Plataforma
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- 3. Grandes Áreas da Química (Ex: Química Geral, Orgânica)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- Inserir uma matéria padrão para os teus testes locais
INSERT INTO subjects (id, nome, slug) VALUES (1, 'Química Geral', 'quimica-geral')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- 4. Tópicos Específicos do ENEM (Com as colunas de conteúdo integradas!)
CREATE TABLE IF NOT EXISTS subtopics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    texto_aula TEXT NULL,          -- Guarda o texto preparado pelo admin
    video_url VARCHAR(255) NULL,   -- Guarda o link do YouTube embed
    fontes TEXT NULL,              -- Guarda as referências bibliográficas
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 5. Banco de Questões
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtopic_id INT NOT NULL,
    enunciado TEXT NOT NULL,
    resolucao_comentada TEXT NULL,
    criado_por INT NULL,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE CASCADE
);

-- 6. Alternativas das Questões
CREATE TABLE IF NOT EXISTS alternatives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    letra CHAR(1) NOT NULL, -- A, B, C, D ou E
    texto_alternativa TEXT NOT NULL,
    eh_correta BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- 7. Histórico de Respostas e Progresso (O Motor dos teus Gráficos)
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 1,
    question_id INT NOT NULL,
    alternative_id INT NOT NULL,
    foi_correta BOOLEAN NOT NULL DEFAULT FALSE, -- Define se soma pontos no gráfico
    respondido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_question_unique (user_id, question_id), -- Aluna só responde 1 vez por questão
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (alternative_id) REFERENCES alternatives(id) ON DELETE CASCADE
);
