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
        if (file_exists(__DIR__ . '/.env')) {
            $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
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
            'SIMILARITY_THRESHOLD' => '0.3',
            'APP_NAME' => 'Sistema MCP RAG - Prefeitura',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost:8080'
        ];
        
        // Mesclar configurações
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
        
        // Configurações do banco de dados
        self::$config['DB_DSN'] = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4;collation=utf8mb4_unicode_ci',
            self::$config['DB_HOST'],
            self::$config['DB_PORT'],
            self::$config['DB_NAME']
        );
    }
    
    /**
     * Obtém uma configuração
     */
    public static function get($key, $default = null)
    {
        if (!self::$config) {
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
        if (!self::$config) {
            self::init();
        }
        
        return isset(self::$config[$key]);
    }
    
    /**
     * Obtém todas as configurações
     */
    public static function all()
    {
        if (!self::$config) {
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
            'dsn' => self::get('DB_DSN'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASS'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
    }
}

// Inicializar configurações automaticamente
Config::init(); 