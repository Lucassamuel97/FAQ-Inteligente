<?php

require_once 'Database.php';
require_once 'GeminiEmbeddings.php';

/**
 * Gerenciador de documentos para o sistema MCP RAG
 */
class DocumentManager
{
    private $db;
    private $embeddings;
    
    /**
     * Construtor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->embeddings = new GeminiEmbeddings();
    }
    
    /**
     * Cadastra um novo documento
     */
    public function createDocument($data)
    {
        try {
            $this->db->beginTransaction();
            
            // Validar dados
            $this->validateDocumentData($data);
            
            // Inserir documento
            $sql = "INSERT INTO documentos (titulo, conteudo, tipo, categoria, numero_documento, data_publicacao, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['titulo'],
                $data['conteudo'],
                $data['tipo'],
                $data['categoria'],
                $data['numero_documento'] ?? null,
                $data['data_publicacao'] ?? date('Y-m-d'),
                $data['status'] ?? 'ativo'
            ];
            
            $this->db->execute($sql, $params);
            $documentId = $this->db->lastInsertId();
            
            // Processar embeddings
            $this->processDocumentEmbeddings($documentId, $data['conteudo']);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'message' => 'Documento cadastrado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Atualiza um documento existente
     */
    public function updateDocument($id, $data)
    {
        try {
            $this->db->beginTransaction();
            
            // Verificar se o documento existe
            $existingDoc = $this->getDocument($id);
            if (!$existingDoc) {
                throw new Exception("Documento não encontrado!");
            }
            
            // Validar dados
            $this->validateDocumentData($data);
            
            // Atualizar documento
            $sql = "UPDATE documentos 
                    SET titulo = ?, conteudo = ?, tipo = ?, categoria = ?, numero_documento = ?, 
                        data_publicacao = ?, status = ?, data_atualizacao = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $params = [
                $data['titulo'],
                $data['conteudo'],
                $data['tipo'],
                $data['categoria'],
                $data['numero_documento'] ?? null,
                $data['data_publicacao'] ?? $existingDoc['data_publicacao'],
                $data['status'] ?? $existingDoc['status'],
                $id
            ];
            
            $this->db->execute($sql, $params);
            
            // Reprocessar embeddings se o conteúdo mudou
            if ($data['conteudo'] !== $existingDoc['conteudo']) {
                $this->removeDocumentEmbeddings($id);
                $this->processDocumentEmbeddings($id, $data['conteudo']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Documento atualizado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Remove um documento
     */
    public function deleteDocument($id)
    {
        try {
            $this->db->beginTransaction();
            
            // Verificar se o documento existe
            $existingDoc = $this->getDocument($id);
            if (!$existingDoc) {
                throw new Exception("Documento não encontrado!");
            }
            
            // Remover embeddings primeiro (cascade)
            $this->removeDocumentEmbeddings($id);
            
            // Remover documento
            $sql = "DELETE FROM documentos WHERE id = ?";
            $this->db->execute($sql, [$id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Documento removido com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtém um documento por ID
     */
    public function getDocument($id)
    {
        $sql = "SELECT * FROM documentos WHERE id = ?";
        return $this->db->queryOne($sql, [$id]);
    }
    
    /**
     * Lista todos os documentos
     */
    public function listDocuments($filters = [])
    {
        $sql = "SELECT * FROM documentos WHERE 1=1";
        $params = [];
        
        if (isset($filters['tipo']) && !empty($filters['tipo'])) {
            $sql .= " AND tipo = ?";
            $params[] = $filters['tipo'];
        }
        
        if (isset($filters['categoria']) && !empty($filters['categoria'])) {
            $sql .= " AND categoria = ?";
            $params[] = $filters['categoria'];
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (titulo LIKE ? OR conteudo LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY data_publicacao DESC";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Processa embeddings para um documento
     */
    private function processDocumentEmbeddings($documentId, $content)
    {
        // Dividir conteúdo em chunks
        $chunks = $this->embeddings->splitTextIntoChunks($content, 1000, 100);
        
        foreach ($chunks as $index => $chunk) {
            try {
                // Gerar embedding para o chunk
                $embedding = $this->embeddings->generateEmbeddingAsJson($chunk);
                
                // Inserir embedding no banco
                $sql = "INSERT INTO embeddings (documento_id, texto_chunk, embedding_vector, chunk_index) 
                        VALUES (?, ?, ?, ?)";
                
                $this->db->execute($sql, [
                    $documentId,
                    $chunk,
                    $embedding,
                    $index
                ]);
                
            } catch (Exception $e) {
                error_log("Erro ao processar embedding para chunk {$index}: " . $e->getMessage());
                // Continuar com os próximos chunks
            }
        }
    }
    
    /**
     * Remove embeddings de um documento
     */
    private function removeDocumentEmbeddings($documentId)
    {
        $sql = "DELETE FROM embeddings WHERE documento_id = ?";
        $this->db->execute($sql, [$documentId]);
    }
    
    /**
     * Valida dados do documento
     */
    private function validateDocumentData($data)
    {
        $required = ['titulo', 'conteudo', 'tipo', 'categoria'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo '{$field}' é obrigatório!");
            }
        }
        
        // Validar tipo
        $validTypes = ['lei', 'regulamento', 'servico', 'informacao'];
        if (!in_array($data['tipo'], $validTypes)) {
            throw new Exception("Tipo inválido! Valores permitidos: " . implode(', ', $validTypes));
        }
        
        // Validar conteúdo
        if (!$this->embeddings->validateText($data['conteudo'])) {
            throw new Exception("Conteúdo inválido ou muito longo!");
        }
        
        // Validar título
        if (strlen($data['titulo']) > 255) {
            throw new Exception("Título muito longo! Máximo 255 caracteres.");
        }
    }
    
    /**
     * Obtém estatísticas dos documentos
     */
    public function getDocumentStats()
    {
        $stats = [];
        
        // Total de documentos
        $sql = "SELECT COUNT(*) as total FROM documentos WHERE status = 'ativo'";
        $result = $this->db->queryOne($sql);
        $stats['total_documentos'] = $result['total'];
        
        // Documentos por tipo
        $sql = "SELECT tipo, COUNT(*) as count FROM documentos WHERE status = 'ativo' GROUP BY tipo";
        $result = $this->db->query($sql);
        $stats['por_tipo'] = $result;
        
        // Documentos por categoria
        $sql = "SELECT categoria, COUNT(*) as count FROM documentos WHERE status = 'ativo' GROUP BY categoria";
        $result = $this->db->query($sql);
        $stats['por_categoria'] = $result;
        
        // Total de embeddings
        $sql = "SELECT COUNT(*) as total FROM embeddings";
        $result = $this->db->queryOne($sql);
        $stats['total_embeddings'] = $result['total'];
        
        return $stats;
    }
    
    /**
     * Reindexa todos os documentos (reprocessa embeddings)
     */
    public function reindexAllDocuments()
    {
        try {
            $this->db->beginTransaction();
            
            // Limpar todos os embeddings
            $this->db->execute("DELETE FROM embeddings");
            
            // Obter todos os documentos ativos
            $documents = $this->db->query("SELECT id, conteudo FROM documentos WHERE status = 'ativo'");
            
            $processed = 0;
            foreach ($documents as $doc) {
                try {
                    $this->processDocumentEmbeddings($doc['id'], $doc['conteudo']);
                    $processed++;
                } catch (Exception $e) {
                    error_log("Erro ao reindexar documento {$doc['id']}: " . $e->getMessage());
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'processed' => $processed,
                'message' => "Reindexação concluída! {$processed} documentos processados."
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
} 