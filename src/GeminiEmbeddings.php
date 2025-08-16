<?php

require_once 'Config.php';

/**
 * Classe para gerar embeddings usando a API do Gemini
 */
class GeminiEmbeddings
{
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent';
    
    /**
     * Construtor
     */
    public function __construct()
    {
        $this->apiKey = Config::get('GEMINI_API_KEY');
        
        if (empty($this->apiKey)) {
            throw new Exception("Chave da API Gemini não configurada!");
        }
    }
    
    /**
     * Gera embedding para um texto usando o modelo embedding-001 do Gemini
     */
    public function generateEmbedding($text)
    {
        try {
            return $this->generateEmbeddingWithCurl($text);
        } catch (Exception $e) {
            // Se cURL falhar, tenta método alternativo
            try {
                return $this->generateEmbeddingAlternative($text);
            } catch (Exception $e2) {
                throw new Exception("Ambos os métodos falharam. cURL: " . $e->getMessage() . " | Alternativo: " . $e2->getMessage());
            }
        }
    }
    
    /**
     * Gera embedding usando cURL (método principal)
     */
    private function generateEmbeddingWithCurl($text)
    {
        $data = [
            'model' => 'models/embedding-001',
            'content' => [
                'parts' => [
                    [
                        'text' => $text
                    ]
                ]
            ]
        ];
        
        $url = $this->apiUrl . '?key=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Configurações SSL para resolver problemas de certificado
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Configurações adicionais para melhorar a estabilidade
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MCP-RAG-Server/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - Resposta: " . $response);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['embedding']['values'])) {
            throw new Exception("Resposta da API inválida: " . $response);
        }
        
        return $result['embedding']['values'];
    }
    
    /**
     * Gera embedding e retorna como JSON string para armazenamento no banco
     */
    public function generateEmbeddingAsJson($text)
    {
        $embedding = $this->generateEmbedding($text);
        return json_encode($embedding);
    }
    
    /**
     * Valida se um texto é adequado para geração de embedding
     */
    public function validateText($text)
    {
        if (empty(trim($text))) {
            return false;
        }
        
        // Limita o tamanho do texto para evitar custos excessivos
        if (strlen($text) > 8000) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Método alternativo para gerar embedding usando file_get_contents (se cURL falhar)
     */
    public function generateEmbeddingAlternative($text)
    {
        $data = [
            'model' => 'models/embedding-001',
            'content' => [
                'parts' => [
                    [
                        'text' => $text
                    ]
                ]
            ]
        ];
        
        $url = $this->apiUrl . '?key=' . $this->apiKey;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Erro ao fazer requisição HTTP alternativa");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['embedding']['values'])) {
            throw new Exception("Resposta da API inválida: " . $response);
        }
        
        return $result['embedding']['values'];
    }
    
    /**
     * Calcula similaridade de cosseno entre dois embeddings
     */
    public function calculateCosineSimilarity($embedding1, $embedding2)
    {
        if (is_string($embedding1)) {
            $embedding1 = json_decode($embedding1, true);
        }
        if (is_string($embedding2)) {
            $embedding2 = json_decode($embedding2, true);
        }
        
        if (count($embedding1) !== count($embedding2)) {
            throw new Exception("Embeddings devem ter a mesma dimensão");
        }
        
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Divide texto em chunks para processamento
     */
    public function splitTextIntoChunks($text, $maxChunkSize = 1000, $overlap = 100)
    {
        $chunks = [];
        $words = explode(' ', $text);
        $currentChunk = '';
        
        foreach ($words as $word) {
            if (strlen($currentChunk . ' ' . $word) > $maxChunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $word;
            } else {
                $currentChunk .= (!empty($currentChunk) ? ' ' : '') . $word;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }
} 