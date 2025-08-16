-- Script de inicialização do banco de dados MCP RAG
-- Sistema de FAQ Dinâmico para Prefeitura

USE mcp_rag;

-- Tabela de documentos (leis, regulamentos, serviços)
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    tipo ENUM('lei', 'regulamento', 'servico', 'informacao') NOT NULL,
    categoria VARCHAR(100),
    numero_documento VARCHAR(50),
    data_publicacao DATE,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo', 'arquivado') DEFAULT 'ativo',
    embedding_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de embeddings para busca semântica
CREATE TABLE IF NOT EXISTS embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    texto_chunk TEXT NOT NULL,
    embedding_vector JSON NOT NULL,
    chunk_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    INDEX idx_documento (documento_id),
    INDEX idx_chunk (chunk_index)
);

-- Tabela de perguntas e respostas do FAQ
CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta TEXT NOT NULL,
    resposta TEXT NOT NULL,
    categoria VARCHAR(100),
    tags JSON,
    documento_referencia INT,
    embedding_pergunta JSON,
    embedding_resposta JSON,
    visualizacoes INT DEFAULT 0,
    util_contador INT DEFAULT 0,
    nao_util_contador INT DEFAULT 0,
    status ENUM('ativo', 'inativo', 'pendente') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_referencia) REFERENCES documentos(id) ON DELETE SET NULL
);

-- Tabela de histórico de consultas
CREATE TABLE IF NOT EXISTS consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta TEXT NOT NULL,
    resposta TEXT NOT NULL,
    embedding_consulta JSON,
    documentos_relevantes JSON,
    tempo_resposta DECIMAL(10,4),
    ip_usuario VARCHAR(45),
    user_agent TEXT,
    satisfacao ENUM('satisfeito', 'insatisfeito', 'neutro'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de categorias de documentos
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    cor VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(50),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de usuários do sistema (administradores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'editor', 'visualizador') DEFAULT 'visualizador',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir categorias padrão
INSERT INTO categorias (nome, descricao, cor, icone) VALUES
('Leis Municipais', 'Leis aprovadas pela Câmara Municipal', '#28a745', 'gavel'),
('Regulamentos', 'Regulamentos e decretos municipais', '#17a2b8', 'file-alt'),
('Serviços Públicos', 'Informações sobre serviços oferecidos pela prefeitura', '#ffc107', 'concierge-bell'),
('Documentação', 'Documentos necessários para serviços', '#6f42c1', 'file'),
('Taxas e Impostos', 'Informações sobre taxas municipais', '#dc3545', 'money-bill'),
('Obras e Infraestrutura', 'Informações sobre obras públicas', '#fd7e14', 'hard-hat');

-- Inserir usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, nivel) VALUES
('Administrador', 'admin@prefeitura.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir alguns documentos de exemplo
INSERT INTO documentos (titulo, conteudo, tipo, categoria, numero_documento, data_publicacao) VALUES
('Lei Municipal nº 123/2024 - Código de Posturas', 'Esta lei estabelece as normas de posturas municipais, incluindo horários de funcionamento de estabelecimentos comerciais, limpeza urbana, e outras disposições para o bom convívio social no município. Art. 1º - Ficam estabelecidas as seguintes normas de posturas municipais: I - Horário de funcionamento de estabelecimentos comerciais das 8h às 22h; II - Proibição de som alto após as 22h; III - Obrigatoriedade de manutenção da limpeza das calçadas; IV - Normas para instalação de placas e letreiros.', 'lei', 'Leis Municipais', '123/2024', '2024-01-15'),
('Regulamento de Alvará de Funcionamento', 'Este regulamento estabelece as normas para emissão de alvará de funcionamento para estabelecimentos comerciais, industriais e de prestação de serviços no município. Para obter o alvará, o interessado deve apresentar: 1) Requerimento padronizado; 2) Documentação pessoal (RG, CPF); 3) Comprovante de endereço; 4) Planta baixa do estabelecimento; 5) Projeto de instalações elétricas e hidráulicas; 6) Certificado de aprovação do Corpo de Bombeiros; 7) Licença ambiental quando aplicável. O prazo para análise é de 30 dias úteis.', 'regulamento', 'Serviços Públicos', 'REG-001/2024', '2024-02-01'),
('Guia de Serviços - 2ª Via de Documentos', 'Este guia orienta os cidadãos sobre como solicitar 2ª via de documentos municipais. Documentos disponíveis: 1) Certidão de Casamento - Requerimento + RG + CPF + Taxa de R$ 15,00; 2) Certidão de Nascimento - Requerimento + RG + CPF + Taxa de R$ 10,00; 3) Certidão de Óbito - Requerimento + RG + CPF + Taxa de R$ 12,00; 4) Certidão Negativa de Débitos - Requerimento + RG + CPF + Gratuito. Todos os documentos são emitidos no prazo de 5 dias úteis.', 'servico', 'Documentação', 'GS-002/2024', '2024-01-20'),
('Decreto Municipal nº 456/2024 - Taxa de Coleta de Lixo', 'Este decreto estabelece a taxa de coleta de lixo domiciliar no município. Art. 1º - Fica instituída a Taxa de Coleta de Lixo Domiciliar (TCLD) no valor de R$ 25,00 mensais por unidade habitacional. Art. 2º - A TCLD será cobrada mensalmente junto com o IPTU. Art. 3º - Estão isentos da TCLD: I - Imóveis com renda familiar inferior a 3 salários mínimos; II - Imóveis rurais; III - Imóveis públicos destinados a serviços essenciais.', 'regulamento', 'Taxas e Impostos', '456/2024', '2024-03-01');

-- Criar índices para melhor performance
CREATE INDEX idx_documentos_tipo ON documentos(tipo);
CREATE INDEX idx_documentos_categoria ON documentos(categoria);
CREATE INDEX idx_documentos_status ON documentos(status);
CREATE INDEX idx_faq_categoria ON faq(categoria);
CREATE INDEX idx_faq_status ON faq(status);
CREATE INDEX idx_consultas_created_at ON consultas(created_at); 