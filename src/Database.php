<?php

require_once 'Config.php';

/**
 * Classe de conexão com o banco de dados
 */
class Database
{
    private static $instance = null;
    private $pdo;
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct()
    {
        try {
            $config = Config::getDatabaseConfig();
            $this->pdo = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            // Forçar UTF-8 na conexão
            $this->pdo->exec("SET NAMES utf8mb4");
            $this->pdo->exec("SET CHARACTER SET utf8mb4");
            $this->pdo->exec("SET character_set_connection = utf8mb4");
            $this->pdo->exec("SET character_set_client = utf8mb4");
            $this->pdo->exec("SET character_set_results = utf8mb4");
            
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém instância única da conexão
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtém a conexão PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }
    
    /**
     * Executa uma consulta SELECT
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Executa uma consulta SELECT e retorna uma única linha
     */
    public function queryOne($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Executa uma consulta INSERT, UPDATE ou DELETE
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro na execução: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém o último ID inserido
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * Reverte uma transação
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }
    
    /**
     * Verifica se está em uma transação
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Escapa uma string para uso em consultas SQL
     */
    public function quote($string)
    {
        return $this->pdo->quote($string);
    }
    
    /**
     * Verifica se a conexão está ativa
     */
    public function isConnected()
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
} 