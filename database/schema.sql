-- ===================================================
-- Banco de dados: Sistema de Gerenciamento de Chamados
-- ===================================================

CREATE DATABASE IF NOT EXISTS helpdesk;
USE helpdesk;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'agente', 'cliente') DEFAULT 'cliente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL
);

CREATE TABLE chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    status ENUM('aberto', 'em_andamento', 'resolvido', 'fechado') DEFAULT 'aberto',
    categoria_id INT,
    cliente_id INT NOT NULL,
    responsavel_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id INT NOT NULL,
    usuario_id INT NOT NULL,
    texto TEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ===================================================
-- Dados iniciais (seed)
-- ===================================================

INSERT INTO categorias (nome) VALUES
('Hardware'), ('Software'), ('Rede'), ('Acesso/Login');

-- Senha para todos os usuários abaixo: "123456"
-- Hash gerado com password_hash('123456', PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Admin Geral', 'admin@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Agente João', 'joao@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agente'),
('Cliente Maria', 'maria@cliente.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente');

INSERT INTO chamados (titulo, descricao, status, categoria_id, cliente_id, responsavel_id) VALUES
('Computador não liga', 'Ao apertar o botão de ligar, nada acontece.', 'aberto', 1, 3, NULL),
('Erro ao abrir sistema financeiro', 'Sistema retorna erro 500 ao tentar logar.', 'em_andamento', 2, 3, 2);

INSERT INTO comentarios (chamado_id, usuario_id, texto) VALUES
(2, 2, 'Estamos verificando o log do servidor, retorno em breve.');
