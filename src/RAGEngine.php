<?php

require_once 'Database.php';
require_once 'GeminiEmbeddings.php';

/**
 * Motor RAG (Retrieval-Augmented Generation) para busca semântica
 */
class RAGEngine
{
    private $db;
    private $embeddings;
    private $maxResults;
    private $similarityThreshold;
    
    /**
     * Construtor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->embeddings = new GeminiEmbeddings();
        $this->maxResults = (int) Config::get('MAX_RESULTS', 3);
        $this->similarityThreshold = (float) Config::get('SIMILARITY_THRESHOLD', 0.1);
    }
    
    /**
     * Processa uma pergunta e retorna uma resposta baseada nos documentos
     */
    public function processQuestion($question, $options = [])
    {
        $startTime = microtime(true);
        
        try {
            // Gerar embedding da pergunta
            $questionEmbedding = $this->embeddings->generateEmbeddingAsJson($question);
            
            // Buscar documentos relevantes
            $relevantDocuments = $this->findRelevantDocuments($questionEmbedding, $options);
            
            // Verificar se os documentos são realmente relevantes
            if (empty($relevantDocuments) || !$this->areDocumentsRelevant($relevantDocuments)) {
                $feedback = $this->getRelevanceFeedback($relevantDocuments);
                
                return [
                    'success' => false,
                    'message' => 'Nenhum documento relevante encontrado para sua pergunta.',
                    'feedback' => $feedback,
                    'suggestions' => $this->getSuggestions(),
                    'response_time' => round(microtime(true) - $startTime, 4)
                ];
            }
            
            // Gerar resposta baseada nos documentos encontrados
            $response = $this->generateResponse($question, $relevantDocuments);
            
            // Calcular tempo de resposta
            $responseTime = microtime(true) - $startTime;
            
            // Registrar consulta no histórico
            $this->logQuery($question, $response, $questionEmbedding, $relevantDocuments, $responseTime);
            
            return [
                'success' => true,
                'response' => $response,
                'documents' => $relevantDocuments,
                'response_time' => round($responseTime, 4),
                'confidence' => $this->calculateConfidence($relevantDocuments)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar sua pergunta: ' . $e->getMessage(),
                'error' => Config::get('APP_DEBUG') === 'true' ? $e->getMessage() : null
            ];
        }
    }
    
    /**
     * Busca documentos relevantes baseado na similaridade semântica
     */
    private function findRelevantDocuments($questionEmbedding, $options = [])
    {
        $category = $options['category'] ?? null;
        $documentType = $options['document_type'] ?? null;
        
        // Buscar embeddings de documentos
        $sql = "SELECT e.*, d.titulo, d.conteudo, d.tipo, d.categoria, d.numero_documento 
                FROM embeddings e 
                JOIN documentos d ON e.documento_id = d.id 
                WHERE d.status = 'ativo'";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND d.categoria = ?";
            $params[] = $category;
        }
        
        if ($documentType) {
            $sql .= " AND d.tipo = ?";
            $params[] = $documentType;
        }
        
        $embeddings = $this->db->query($sql, $params);
        
        if (empty($embeddings)) {
            return [];
        }
        
        // Calcular similaridade e ordenar
        $similarities = [];
        foreach ($embeddings as $embedding) {
            $similarity = $this->embeddings->calculateCosineSimilarity(
                $questionEmbedding, 
                $embedding['embedding_vector']
            );
            
            // Aumentar o threshold para ser mais rigoroso
            if ($similarity >= 0.3) { // Aumentado de 0.1 para 0.3 (30%)
                $similarities[] = [
                    'similarity' => $similarity,
                    'document' => $embedding
                ];
            }
        }
        
        // Ordenar por similaridade (maior primeiro)
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Retornar apenas os resultados mais relevantes
        $topResults = array_slice($similarities, 0, $this->maxResults);
        
        // Formatar resultados
        $results = [];
        foreach ($topResults as $result) {
            $results[] = [
                'id' => $result['document']['documento_id'],
                'titulo' => $result['document']['titulo'],
                'conteudo' => $result['document']['texto_chunk'],
                'tipo' => $result['document']['tipo'],
                'categoria' => $result['document']['categoria'],
                'numero_documento' => $result['document']['numero_documento'],
                'similarity' => $result['similarity']
            ];
        }
        
        return $results;
    }
    
    /**
     * Gera uma resposta baseada nos documentos encontrados
     */
    private function generateResponse($question, $documents)
    {
        // Construir contexto baseado nos documentos
        $context = "Com base nos documentos oficiais da prefeitura, aqui está a resposta para sua pergunta:\n\n";
        
        foreach ($documents as $doc) {
            $context .= "**{$doc['titulo']}** ({$doc['tipo']} - {$doc['categoria']})\n";
            $context .= "{$doc['conteudo']}\n\n";
        }
        
        // Gerar resposta estruturada
        $response = $this->formatResponse($question, $context, $documents);
        
        return $response;
    }
    
    /**
     * Formata a resposta de forma estruturada
     */
    private function formatResponse($question, $context, $documents)
    {
        $response = "## Resposta para: {$question}\n\n";
        
        // Resumo da resposta
        $response .= "### Resumo\n";
        $response .= "Com base na legislação e regulamentos municipais, encontrei as seguintes informações relevantes:\n\n";
        
        // Detalhes dos documentos
        foreach ($documents as $doc) {
            $response .= "**📄 {$doc['titulo']}**\n";
            $response .= "- **Tipo:** {$doc['tipo']}\n";
            $response .= "- **Categoria:** {$doc['categoria']}\n";
            $response .= "- **Número:** {$doc['numero_documento']}\n";
            $response .= "- **Relevância:** " . round($doc['similarity'] * 100, 1) . "%\n\n";
            
            $response .= "**Informações:**\n";
            $response .= "{$doc['conteudo']}\n\n";
        }
        
        // Recomendações
        $response .= "### Recomendações\n";
        $response .= "Para obter informações mais detalhadas ou esclarecimentos, recomendo:\n";
        $response .= "1. Entrar em contato com a prefeitura através dos canais oficiais\n";
        $response .= "2. Consultar o site oficial da prefeitura\n";
        $response .= "3. Visitar o atendimento presencial se necessário\n\n";
        
        $response .= "---\n";
        $response .= "*Esta resposta foi gerada automaticamente com base na legislação municipal vigente.*";
        
        return $response;
    }
    
    /**
     * Calcula o nível de confiança da resposta
     */
    private function calculateConfidence($documents)
    {
        if (empty($documents)) {
            return 0;
        }
        
        $totalSimilarity = 0;
        foreach ($documents as $doc) {
            $totalSimilarity += $doc['similarity'];
        }
        
        $averageSimilarity = $totalSimilarity / count($documents);
        
        // Converter para porcentagem
        return round($averageSimilarity * 100, 1);
    }
    
    /**
     * Registra a consulta no histórico
     */
    private function logQuery($question, $response, $questionEmbedding, $documents, $responseTime)
    {
        try {
            $documentIds = array_column($documents, 'id');
            
            $sql = "INSERT INTO consultas (pergunta, resposta, embedding_consulta, documentos_relevantes, tempo_resposta, ip_usuario, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $question,
                $response,
                $questionEmbedding,
                json_encode($documentIds),
                $responseTime,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];
            
            $this->db->execute($sql, $params);
        } catch (Exception $e) {
            // Log do erro, mas não falhar a consulta principal
            error_log("Erro ao registrar consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Retorna sugestões de perguntas comuns
     */
    private function getSuggestions()
    {
        return [
            "Como tirar alvará de funcionamento?",
            "Quais documentos preciso para certidão de casamento?",
            "Qual o valor da taxa de coleta de lixo?",
            "Como funciona o horário de funcionamento dos estabelecimentos?",
            "Quais são as normas de posturas municipais?"
        ];
    }
    
    /**
     * Verifica se os documentos encontrados são relevantes para a pergunta
     */
    private function areDocumentsRelevant($documents)
    {
        if (empty($documents)) {
            return false;
        }

        $totalSimilarity = 0;
        $highRelevanceCount = 0;
        
        foreach ($documents as $doc) {
            $totalSimilarity += $doc['similarity'];
            
            // Contar documentos com alta relevância (>= 70%)
            if ($doc['similarity'] >= 0.7) {
                $highRelevanceCount++;
            }
        }

        $averageSimilarity = $totalSimilarity / count($documents);

        // Critérios de relevância mais rigorosos:
        // 1. Pelo menos um documento deve ter alta relevância (>= 70%)
        // 2. A média de similaridade deve ser >= 50%
        // 3. Pelo menos 50% dos documentos devem ter relevância >= 60%
        $mediumRelevanceCount = 0;
        foreach ($documents as $doc) {
            if ($doc['similarity'] >= 0.6) {
                $mediumRelevanceCount++;
            }
        }
        
        $mediumRelevancePercentage = $mediumRelevanceCount / count($documents);
        
        return $highRelevanceCount > 0 && 
               $averageSimilarity >= 0.5 && 
               $mediumRelevancePercentage >= 0.5;
    }
    
    /**
     * Fornece feedback detalhado sobre por que a pergunta não foi relevante
     */
    private function getRelevanceFeedback($documents)
    {
        if (empty($documents)) {
            return "Nenhum documento foi encontrado com similaridade suficiente.";
        }
        
        $maxSimilarity = max(array_column($documents, 'similarity'));
        $averageSimilarity = array_sum(array_column($documents, 'similarity')) / count($documents);
        
        $feedback = "Documentos encontrados, mas com baixa relevância:\n";
        $feedback .= "- Maior similaridade: " . round($maxSimilarity * 100, 1) . "%\n";
        $feedback .= "- Similaridade média: " . round($averageSimilarity * 100, 1) . "%\n\n";
        
        if ($maxSimilarity < 0.7) {
            $feedback .= "❌ Nenhum documento atingiu alta relevância (>= 70%)\n";
        }
        
        if ($averageSimilarity < 0.5) {
            $feedback .= "❌ Similaridade média muito baixa (< 50%)\n";
        }
        
        $feedback .= "\n💡 Dicas para melhorar sua pergunta:\n";
        $feedback .= "• Use termos mais específicos relacionados aos serviços municipais\n";
        $feedback .= "• Evite perguntas genéricas ou fora do contexto da prefeitura\n";
        $feedback .= "• Tente usar palavras-chave como: alvará, certidão, taxa, regulamento\n";
        
        return $feedback;
    }
    
    /**
     * Busca documentos por categoria
     */
    public function getDocumentsByCategory($category)
    {
        $sql = "SELECT * FROM documentos WHERE categoria = ? AND status = 'ativo' ORDER BY data_publicacao DESC";
        return $this->db->query($sql, [$category]);
    }
    
    /**
     * Busca documentos por tipo
     */
    public function getDocumentsByType($type)
    {
        $sql = "SELECT * FROM documentos WHERE tipo = ? AND status = 'ativo' ORDER BY data_publicacao DESC";
        return $this->db->query($sql, [$type]);
    }
    
    /**
     * Busca documentos por palavra-chave
     */
    public function searchDocumentsByKeyword($keyword)
    {
        $sql = "SELECT * FROM documentos 
                WHERE (titulo LIKE ? OR conteudo LIKE ?) 
                AND status = 'ativo' 
                ORDER BY data_publicacao DESC";
        
        $searchTerm = "%{$keyword}%";
        return $this->db->query($sql, [$searchTerm, $searchTerm]);
    }
} 