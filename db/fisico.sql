CREATE DATABASE IF NOT EXISTS ferramenta_auditoria;
USE ferramenta_auditoria;

CREATE TABLE checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    descricao TEXT,
    resultado ENUM('Sim', 'Não'),
    responsavel VARCHAR(100),
    classificacao ENUM('Simples', 'Média', 'Complexa') NULL,
    data_identificacao DATETIME,
    prazo DATETIME,
    data_escalonamento DATETIME,
    data_conclusao DATETIME,
    observacoes VARCHAR(500),
    acao_corretiva_indicada VARCHAR(500)
);
