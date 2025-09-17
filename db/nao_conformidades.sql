-- Script para criar tabela de não-conformidades
-- Execute este script no seu banco de dados MySQL

USE ferramenta_auditoria;

CREATE TABLE IF NOT EXISTS nao_conformidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    responsavel VARCHAR(100) NOT NULL,
    data_abertura DATETIME NOT NULL,
    prazo_resolucao DATETIME NOT NULL,
    status ENUM('Aberta', 'Em andamento', 'Resolvida', 'Escalonada') DEFAULT 'Aberta',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    observacoes TEXT,
    escalonada_automaticamente BOOLEAN DEFAULT FALSE
);
