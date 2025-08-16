# Sistema MCP RAG - FAQ Inteligente para Prefeitura

Um sistema completo de FAQ dinâmico baseado em inteligência artificial que utiliza o protocolo MCP (Model Context Protocol) com funcionalidade RAG (Retrieval-Augmented Generation) para buscar informações em documentos oficiais da prefeitura usando embeddings do Google Gemini.

## 🚀 Características Principais

- **FAQ Inteligente**: Sistema de perguntas e respostas baseado em IA
- **Busca Semântica**: Utiliza embeddings do Google Gemini para busca inteligente
- **Gestão de Documentos**: Cadastro e gerenciamento de leis, regulamentos e serviços
- **Interface Moderna**: Design responsivo com Bootstrap 5
- **Área Administrativa**: Painel completo para gestão do sistema
- **Docker**: Containerização completa para fácil implantação
- **MySQL 8.0**: Banco de dados robusto e escalável
- **PHP 8.4**: Linguagem moderna com recursos avançados

## 🏗️ Arquitetura do Sistema

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend PHP   │    │   Google Gemini │
│   (Bootstrap)   │◄──►│   (MCP RAG)     │◄──►│   (Embeddings)  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx         │    │   MySQL 8.0     │    │   API Gemini    │
│   (Proxy)       │    │   (Database)    │    │   (AI Service)  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📋 Pré-requisitos

- Docker e Docker Compose
- Chave de API do Google Gemini
- Mínimo 4GB RAM disponível
- Conexão com internet para API Gemini

## 🛠️ Instalação

### 1. Clone o repositório
```bash
git clone https://github.com/Lucassamuel97/FAQ-Inteligente.git
cd FAQ-Inteligente
```

### 2. Configure as variáveis de ambiente
```bash
cp env.example .env
```

Edite o arquivo `.env` com suas configurações:
```env
# API Google Gemini
GEMINI_API_KEY=sua_chave_api_aqui

# Configurações do Banco
DB_HOST=db
DB_NAME=mcp_rag
DB_USER=mcp_user
DB_PASS=mcp_password
DB_PORT=3306

# Configurações da Aplicação
APP_NAME="Sistema MCP RAG - Prefeitura"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080
```

### 3. Inicie os containers
```bash
docker-compose up -d
```

### 4. Aguarde a inicialização
```bash
# Verifique os logs
docker-compose logs -f

# Aguarde até ver "MySQL is ready to accept connections"
```

### 5. Inicialize os embeddings
```bash
# Acesse o container PHP
docker-compose exec app php init_embeddings.php
```

## 🌐 Acesso ao Sistema

- **Site Principal**: http://localhost:8080
- **Área Admin**: http://localhost:8080/admin.php
- **Login Admin**: http://localhost:8080/login.php
- **Nginx (opcional)**: http://localhost:8081

### Credenciais Padrão
- **Email**: admin@prefeitura.gov.br
- **Senha**: admin123

## 📚 Como Usar

### Para Cidadãos (FAQ)
1. Acesse http://localhost:8080
2. Digite sua pergunta na caixa de busca
3. O sistema buscará nos documentos oficiais
4. Receba uma resposta baseada na legislação vigente

### Para Administradores
1. Acesse http://localhost:8000/login.php
2. Faça login com as credenciais
3. Gerencie documentos, categorias e usuários
4. Monitore estatísticas do sistema

## 🔧 Funcionalidades

### Sistema RAG
- **Embeddings**: Geração automática de vetores semânticos
- **Busca Inteligente**: Similaridade de cosseno para relevância
- **Respostas Contextuais**: Baseadas em documentos oficiais
- **Histórico**: Registro de todas as consultas realizadas

### Gestão de Documentos
- **Tipos**: Leis, regulamentos, serviços, informações
- **Categorias**: Organização por área de atuação
- **Status**: Ativo, inativo, arquivado
- **Versionamento**: Controle de datas de publicação

### Área Administrativa
- **Dashboard**: Visão geral do sistema
- **CRUD Documentos**: Criação, edição, exclusão
- **Estatísticas**: Métricas de uso e performance
- **Reindexação**: Reprocessamento de embeddings

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- `documentos`: Leis, regulamentos e serviços
- `embeddings`: Vetores semânticos para busca
- `faq`: Perguntas e respostas frequentes
- `consultas`: Histórico de buscas
- `categorias`: Organização dos documentos
- `usuarios`: Administradores do sistema

## 🐛 Troubleshooting

### Problemas Comuns

#### 1. Erro de Conexão com Banco
```bash
# Verifique se o MySQL está rodando
docker-compose ps db

# Verifique logs
docker-compose logs db
```

#### 2. Erro de API Gemini
```bash
# Verifique a chave da API
echo $GEMINI_API_KEY

# Teste a conexão
curl -H "Authorization: Bearer $GEMINI_API_KEY" \
     https://generativelanguage.googleapis.com/v1beta/models
```

#### 3. Embeddings Não Gerados
```bash
# Execute o script de inicialização
docker-compose exec app php init_embeddings.php

# Verifique logs de erro
docker-compose logs app
```

## 📈 Performance

### Métricas Esperadas
- **Tempo de Resposta**: < 3 segundos
- **Taxa de Acerto**: > 85%
- **Concorrência**: 100+ usuários simultâneos
- **Uptime**: 99.9%

### Otimizações
- **Índices MySQL**: Otimizados para busca semântica
- **Cache de Embeddings**: Evita reprocessamento
- **Chunking Inteligente**: Divisão otimizada de documentos
- **Pool de Conexões**: Gerenciamento eficiente de recursos


### Contribuições
1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.


---

**Desenvolvido com ❤️ para modernizar o atendimento ao cidadão** 