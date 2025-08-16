#!/bin/bash

# Script de instalação automatizada do Sistema MCP RAG
# Sistema de FAQ Inteligente para Prefeitura

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir mensagens coloridas
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Sistema MCP RAG - Instalação${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Verificar se Docker está instalado
check_docker() {
    print_message "Verificando se Docker está instalado..."
    
    if ! command -v docker &> /dev/null; then
        print_error "Docker não está instalado. Por favor, instale o Docker primeiro."
        print_message "Visite: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose não está instalado. Por favor, instale o Docker Compose primeiro."
        print_message "Visite: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    print_message "Docker e Docker Compose estão instalados."
}

# Verificar se a chave da API Gemini está configurada
check_gemini_api() {
    print_message "Verificando configuração da API Gemini..."
    
    if [ -z "$GEMINI_API_KEY" ]; then
        print_warning "Variável GEMINI_API_KEY não está definida."
        print_message "Por favor, configure sua chave da API Gemini:"
        echo "export GEMINI_API_KEY='sua_chave_aqui'"
        echo ""
        read -p "Digite sua chave da API Gemini: " GEMINI_API_KEY
        
        if [ -z "$GEMINI_API_KEY" ]; then
            print_error "Chave da API Gemini é obrigatória!"
            exit 1
        fi
        
        # Salvar no arquivo .env
        echo "GEMINI_API_KEY=$GEMINI_API_KEY" >> .env
        print_message "Chave da API Gemini configurada."
    else
        print_message "Chave da API Gemini já está configurada."
    fi
}

# Criar arquivo .env se não existir
create_env_file() {
    print_message "Configurando variáveis de ambiente..."
    
    if [ ! -f .env ]; then
        cp env.example .env
        print_message "Arquivo .env criado a partir do exemplo."
    else
        print_message "Arquivo .env já existe."
    fi
    
    # Atualizar GEMINI_API_KEY no .env se foi fornecida
    if [ ! -z "$GEMINI_API_KEY" ]; then
        sed -i "s/GEMINI_API_KEY=.*/GEMINI_API_KEY=$GEMINI_API_KEY/" .env
    fi
}

# Verificar se os diretórios necessários existem
create_directories() {
    print_message "Criando diretórios necessários..."
    
    mkdir -p logs
    mkdir -p database
    mkdir -p src
    
    print_message "Diretórios criados."
}

# Iniciar containers Docker
start_containers() {
    print_message "Iniciando containers Docker..."
    
    # Parar containers existentes se houver
    docker-compose down 2>/dev/null || true
    
    # Construir e iniciar containers
    docker-compose up -d --build
    
    print_message "Containers iniciados. Aguardando inicialização..."
    
    # Aguardar MySQL estar pronto
    print_message "Aguardando MySQL estar pronto..."
    timeout=60
    counter=0
    
    while ! docker-compose exec -T db mysqladmin ping -h"localhost" --silent; do
        sleep 2
        counter=$((counter + 2))
        
        if [ $counter -ge $timeout ]; then
            print_error "Timeout aguardando MySQL. Verifique os logs:"
            docker-compose logs db
            exit 1
        fi
        
        echo -n "."
    done
    
    echo ""
    print_message "MySQL está pronto!"
}

# Executar script de inicialização do banco
init_database() {
    print_message "Inicializando banco de dados..."
    
    # Aguardar um pouco mais para garantir que o MySQL esteja totalmente pronto
    sleep 5
    
    # Executar script SQL de inicialização
    docker-compose exec -T db mysql -u root -proot_password mcp_rag < database/init.sql
    
    print_message "Banco de dados inicializado com sucesso!"
}

# Executar script de embeddings
init_embeddings() {
    print_message "Inicializando embeddings dos documentos..."
    
    # Aguardar um pouco para garantir que o PHP esteja funcionando
    sleep 10
    
    # Executar script de inicialização de embeddings
    docker-compose exec -T app php init_embeddings.php
    
    print_message "Embeddings inicializados com sucesso!"
}

# Verificar se o sistema está funcionando
health_check() {
    print_message "Verificando saúde do sistema..."
    
    # Aguardar um pouco para o sistema estabilizar
    sleep 15
    
    # Testar se a página principal está respondendo
    if curl -s http://localhost:8080 > /dev/null; then
        print_message "✅ Sistema está funcionando!"
        print_message "🌐 Acesse: http://localhost:8080"
        print_message "🔐 Admin: http://localhost:8080/login.php"
        print_message "📧 Login: admin@prefeitura.gov.br"
        print_message "🔑 Senha: admin123"
    else
        print_warning "⚠️  Sistema pode não estar totalmente funcional ainda."
        print_message "Aguarde alguns minutos e tente novamente."
    fi
}

# Função principal
main() {
    print_header
    
    print_message "Iniciando instalação do Sistema MCP RAG..."
    
    # Verificações prévias
    check_docker
    check_gemini_api
    
    # Preparação
    create_directories
    create_env_file
    
    # Instalação
    start_containers
    init_database
    init_embeddings
    
    # Verificação final
    health_check
    
    print_message ""
    print_message "🎉 Instalação concluída com sucesso!"
    print_message ""
    print_message "📚 Próximos passos:"
    print_message "1. Acesse o sistema em: http://localhost:8080"
    print_message "2. Faça login na área administrativa"
    print_message "3. Cadastre novos documentos conforme necessário"
    print_message "4. Teste o sistema fazendo perguntas no FAQ"
    print_message ""
    print_message "📖 Para mais informações, consulte o README.md"
    print_message ""
    print_message "🚀 Sistema MCP RAG instalado e funcionando!"
}

# Executar função principal
main "$@" 