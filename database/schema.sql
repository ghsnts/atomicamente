CREATE DATABASE IF NOT EXISTS atomicamente_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atomicamente_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO roles (nome) VALUES ('admin'), ('estudante') ON DUPLICATE KEY UPDATE nome=nome;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS subtopics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtopic_id INT NOT NULL,
    enunciado TEXT NOT NULL,
    resolucao_comentada TEXT NULL,
    criado_por INT NULL,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alternatives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    letra CHAR(1) NOT NULL,
    texto_alternativa TEXT NOT NULL,
    eh_correta BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    alternative_id INT NOT NULL,
    UNIQUE KEY user_question_unique (user_id, question_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (alternative_id) REFERENCES alternatives(id) ON DELETE CASCADE
);
