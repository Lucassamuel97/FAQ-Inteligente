<?php
/**
 * Script para inicializar embeddings dos documentos existentes
 * Execute este script após a primeira execução do sistema para processar
 * todos os documentos e gerar seus embeddings
 */

require_once 'Config.php';
require_once 'DocumentManager.php';

echo "=== Inicialização de Embeddings - Sistema MCP RAG ===\n\n";

try {
    $documentManager = new DocumentManager();
    
    echo "Iniciando processamento de embeddings...\n";
    echo "Este processo pode levar alguns minutos dependendo da quantidade de documentos.\n\n";
    
    // Reindexar todos os documentos
    $result = $documentManager->reindexAllDocuments();
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "\n";
        echo "📊 Documentos processados: " . $result['processed'] . "\n\n";
        
        // Obter estatísticas
        $stats = $documentManager->getDocumentStats();
        echo "📈 Estatísticas do Sistema:\n";
        echo "   - Total de documentos: " . ($stats['total_documentos'] ?? 0) . "\n";
        echo "   - Total de embeddings: " . ($stats['total_embeddings'] ?? 0) . "\n";
        
        if (!empty($stats['por_tipo'])) {
            echo "   - Documentos por tipo:\n";
            foreach ($stats['por_tipo'] as $tipo) {
                echo "     * " . ucfirst($tipo['tipo']) . ": " . $tipo['count'] . "\n";
            }
        }
        
        if (!empty($stats['por_categoria'])) {
            echo "   - Documentos por categoria:\n";
            foreach ($stats['por_categoria'] as $cat) {
                echo "     * " . $cat['categoria'] . ": " . $cat['count'] . "\n";
            }
        }
        
        echo "\n🎉 Sistema inicializado com sucesso!\n";
        echo "Agora você pode acessar o sistema e fazer perguntas.\n";
        
    } else {
        echo "❌ Erro ao processar embeddings: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro crítico: " . $e->getMessage() . "\n";
    echo "Verifique se:\n";
    echo "1. O banco de dados está rodando\n";
    echo "2. As credenciais estão corretas\n";
    echo "3. A API do Google Gemini está configurada\n";
}

echo "\n=== Fim da Inicialização ===\n"; 