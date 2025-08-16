<?php

/**
 * Classe de configuração do sistema MCP RAG
 * Gerencia variáveis de ambiente e configurações
 */
class Config
{
    private static $config = [];
    
    /**
     * Inicializa as configurações
     */
    public static function init()
    {
        // Carregar arquivo .env se existir
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remover aspas se existirem
                    if (preg_match('/^"(.*)"$/', $value, $matches)) {
                        $value = $matches[1];
                    }
                    
                    self::$config[$key] = $value;
                }
            }
        }
        
        // Configurações padrão
        $defaults = [
            'DB_HOST' => 'db',
            'DB_NAME' => 'mcp_rag',
            'DB_USER' => 'mcp_user',
            'DB_PASS' => 'mcp_password',
            'DB_PORT' => '3306',
            'GEMINI_API_KEY' => '',
            'EMBEDDING_MODEL' => 'models/embedding-001',
            'EMBEDDING_DIMENSIONS' => '768',
            'MAX_RESULTS' => '3',
            'SIMILARITY_THRESHOLD' => '0.1',
            'APP_NAME' => 'Sistema MCP RAG - Prefeitura',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost:8000'
        ];
        
        // Mesclar configurações padrão com as do arquivo .env
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
        
        // Sobrescrever com variáveis de ambiente do sistema se existirem
        foreach (self::$config as $key => $value) {
            $envValue = getenv($key);
            if ($envValue !== false) {
                self::$config[$key] = $envValue;
            }
        }
    }
    
    /**
     * Obtém uma configuração
     */
    public static function get($key, $default = null)
    {
        if (empty(self::$config)) {
            self::init();
        }
        
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Define uma configuração
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }
    
    /**
     * Verifica se uma configuração existe
     */
    public static function has($key)
    {
        if (empty(self::$config)) {
            self::init();
        }
        
        return isset(self::$config[$key]);
    }
    
    /**
     * Obtém todas as configurações
     */
    public static function all()
    {
        if (empty(self::$config)) {
            self::init();
        }
        
        return self::$config;
    }
    
    /**
     * Obtém configurações do banco de dados
     */
    public static function getDatabaseConfig()
    {
        return [
            'host' => self::get('DB_HOST'),
            'dbname' => self::get('DB_NAME'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASS'),
            'port' => self::get('DB_PORT')
        ];
    }
    
    /**
     * Obtém configurações do Gemini
     */
    public static function getGeminiConfig()
    {
        return [
            'api_key' => self::get('GEMINI_API_KEY'),
            'embedding_model' => self::get('EMBEDDING_MODEL'),
            'embedding_dimensions' => (int) self::get('EMBEDDING_DIMENSIONS'),
            'max_results' => (int) self::get('MAX_RESULTS'),
            'similarity_threshold' => (float) self::get('SIMILARITY_THRESHOLD')
        ];
    }
}

// Inicializar configurações automaticamente
Config::init(); 