CREATE DATABASE printly_db;

CREATE USER 'printly_user'@'localhost' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON printly_db.* TO 'printly_user'@'localhost';
FLUSH PRIVILEGES;

USE printly_db;

-- 1. TABELA DE USUÁRIOS (Unificando os perfis Cliente, Maker e Admin)
-- Baseado no Diagrama de Classes (Usuario/Pessoa) e perfis do PBB
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_perfil ENUM('CLIENTE', 'MAKER', 'ADMIN') NOT NULL,
    documento VARCHAR(20), -- CPF ou CNPJ
    telefone VARCHAR(20),
    cep VARCHAR(10),
    cidade VARCHAR(100),
    estado CHAR(2),
    endereco TEXT,
    status ENUM('ATIVO', 'PENDENTE', 'BANIDO') DEFAULT 'PENDENTE',
    lat double,
    lng double,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELA DE IMPRESSORAS DO FABRICANTE
-- Para o maker gerenciar suas capacidades de hardware
CREATE TABLE impressoras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maker_id INT NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    volume_maximo_cm3 DECIMAL(10,2), -- Para validar o tamanho da peça
    status ENUM('DISPONIVEL', 'MANUTENCAO') DEFAULT 'DISPONIVEL',
    FOREIGN KEY (maker_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 3. TABELA DE MATERIAIS E PREÇOS (Configuração do Maker)
-- Para o sistema calcular orçamentos baseados nos custos do maker
CREATE TABLE materiais_maker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maker_id INT NOT NULL,
    tipo_material VARCHAR(50) NOT NULL, -- Ex: PLA, ABS, PETG
    preco_por_grama DECIMAL(10,2) NOT NULL, -- Obrigatório ser > 0
    FOREIGN KEY (maker_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 4. TABELA DE PROJETOS (Arquivos 3D enviados pelo Cliente)
CREATE TABLE projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome_projeto VARCHAR(100) NOT NULL,
    descricao TEXT,
    arquivo_caminho VARCHAR(255) NOT NULL, -- Caminho do arquivo STL/OBJ
    formato ENUM('STL', 'OBJ') NOT NULL,
    volume_estimado_cm3 DECIMAL(10,2),
    peso_estimado_gramas DECIMAL(10,2),
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 5. TABELA DE PEDIDOS DE IMPRESSÃO
-- Conecta o Projeto do Cliente ao Maker escolhido
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    maker_id INT NOT NULL,
    material_escolhido VARCHAR(50) NOT NULL,
    quantidade INT DEFAULT 1,
    valor_total DECIMAL(10,2) NOT NULL,
    status ENUM('AGUARDANDO_CONFIRMACAO', 'ARQUIVO_VALIDADO', 'ACEITO', 'EM_PRODUCAO', 'CONCLUIDO', 'NEGADO') DEFAULT 'AGUARDANDO_CONFIRMACAO',
    motivo_recusa TEXT, -- Preenchido caso o status seja NEGADO
    endereco_entrega TEXT NOT NULL,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id),
    FOREIGN KEY (maker_id) REFERENCES usuarios(id)
);

-- 6. TABELA DE MENSAGENS (Chat em tempo real)
-- Para negociação de condições de compra entre Cliente e Maker
CREATE TABLE mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
);

-- 7. TABELA DE FEEDBACKS / AVALIAÇÕES
-- Avaliações pós-entrega
CREATE TABLE avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    cliente_id INT NOT NULL,
    maker_id INT NOT NULL,
    nota INT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    resposta_maker TEXT, -- Interação do fabricante ao comentário
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (maker_id) REFERENCES usuarios(id)
);

-- 8. TABELA DE LOGS DO SISTEMA (Auditoria do Administrador)
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT, -- Quem realizou a ação
    acao VARCHAR(255) NOT NULL, -- Ex: "Atualizou status do pedido #12 para CONCLUÍDO"
    tabela_afetada VARCHAR(50),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 9. TABELA DE ANÚNCIOS GLOBAIS (Para manutenções programadas)
CREATE TABLE anuncios_globais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. TABELA DE NOTIFICAÇÕES (Para atrasos e retratações)
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ATRASO', 'MANUTENCAO', 'RETIFICACAO') NOT NULL,
    pedido_id INT NULL, -- Para atrasos
    titulo VARCHAR(255),
    mensagem TEXT NOT NULL,
    email_destino VARCHAR(100),
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    retratada BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
);

-- 11. CAMPO DE PRAZO NOS PEDIDOS (necessário para controle de tempo)
ALTER TABLE pedidos
    ADD COLUMN prazo_pedido DATETIME NULL DEFAULT NULL;
    
select * from usuarios;

INSERT INTO usuarios (nome, email, senha, tipo_perfil) VALUES 
('João Silva', 'joao2@email.com', '123456', 'CLIENTE'),
('Maria Souza', 'maria@email.com', '123456', 'MAKER');

--FABRICANTE NOVA INSERÇÃO

ALTER TABLE usuarios
ADD COLUMN status_fabricante ENUM('NAO_SOLICITADO', 'PENDENTE', 'APROVADO', 'REJEITADO') DEFAULT 'NAO_SOLICITADO';

CREATE TABLE fabricantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cnpj VARCHAR(20),
    telefone_comercial VARCHAR(20),
    endereco_empresa TEXT,
    data_aprovacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

SELECT u.id, u.nome, u.email, f.cnpj, f.telefone_comercial, f.endereco_empresa
FROM usuarios u
JOIN fabricantes f ON u.id = f.usuario_id
WHERE u.status_fabricante = 'APROVADO';

SELECT id, nome, email
FROM usuarios
WHERE status_fabricante = 'PENDENTE';

INSERT INTO fabricantes (usuario_id, cnpj, telefone_comercial, endereco_empresa)
VALUES 
(2, '12.345.678/0001-90', '(41) 99999-0001', 'Rua das Impressoras, 123, Curitiba, PR');

INSERT INTO usuarios (nome, email, senha, tipo_perfil)
VALUES ('Carlos Pereira', 'carlos@email.com', '123456', 'MAKER');


INSERT INTO fabricantes (usuario_id, cnpj, telefone_comercial, endereco_empresa)
VALUES 
(3, '23.456.789/0001-55', '(41) 97777-0003', 'Rua dos Criadores, 789, Curitiba, PR');


--ADMINISTRADOR NOVA INSERÇÃO
INSERT INTO usuarios (nome, email, senha, tipo_perfil, status, telefone, cep, cidade, estado, endereco)
VALUES 
('Carlos Pereira', 'carlos.admin@email.com', '123456', 'ADMIN', 'ATIVO', '(41) 98888-0000', '80000-000', 'Curitiba', 'PR', 'Rua do Administrador, 100');

INSERT INTO usuarios (nome, email, senha, tipo_perfil, status, telefone, cep, cidade, estado, endereco)
VALUES 
('Ana Martins', 'ana.admin@email.com', '123456', 'ADMIN', 'ATIVO', '(41) 97777-1111', '80010-000', 'Curitiba', 'PR', 'Avenida Central, 200');