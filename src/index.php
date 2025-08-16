<?php
require_once 'Config.php';
require_once 'RAGEngine.php';
require_once 'DocumentManager.php';

// Inicializar componentes
$ragEngine = new RAGEngine();
$documentManager = new DocumentManager();

// Processar pergunta se enviada
$response = null;
$question = '';
$loading = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    $question = trim($_POST['question']);
    $loading = true;
    
    try {
        $response = $ragEngine->processQuestion($question);
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Erro ao processar pergunta: ' . $e->getMessage()
        ];
    }
    
    $loading = false;
}

// Obter estatísticas
$stats = $documentManager->getDocumentStats();
$categories = $documentManager->listDocuments(['status' => 'ativo']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Config::get('APP_NAME') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 1rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .response-card {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            border-radius: 8px;
        }
        .document-card {
            transition: transform 0.2s;
        }
        .document-card:hover {
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .loading-spinner {
            display: none;
        }
        .suggestion-chip {
            cursor: pointer;
            transition: all 0.2s;
        }
        .suggestion-chip:hover {
            background: #007bff !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-city me-2"></i>
                <?= Config::get('APP_NAME') ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#faq">FAQ</a>
                <a class="nav-link" href="/admin.php">Admin</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">
                <i class="fas fa-search me-3"></i>
                Sistema de FAQ Inteligente
            </h1>
            <p class="lead mb-5">
                Encontre respostas rápidas para suas dúvidas sobre serviços municipais, 
                leis e regulamentos usando inteligência artificial.
            </p>
            
            <!-- Search Box -->
            <div class="search-box mx-auto" style="max-width: 80vw;">
                <form method="POST" id="questionForm">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control border-0" 
                               name="question" 
                               value="<?= htmlspecialchars($question) ?>"
                               placeholder="Digite sua pergunta aqui... (ex: Como tirar alvará de funcionamento?)"
                               required>
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fas fa-search me-2"></i>
                            Perguntar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Loading Spinner -->
            <div class="loading-spinner mt-3" id="loadingSpinner">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Processando sua pergunta...</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Response Section -->
        <?php if ($response): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card response-card">
                    <div class="card-body">
                        <?php if ($response['success']): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title text-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Resposta Encontrada
                                </h4>
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $response['response_time'] ?>s
                                        <i class="fas fa-chart-line ms-3 me-1"></i>
                                        Confiança: <?= $response['confidence'] ?>%
                                    </small>
                                </div>
                            </div>
                            
                            <div class="response-content">
                                <?= nl2br(htmlspecialchars($response['response'])) ?>
                            </div>
                            
                            <?php if (!empty($response['documents'])): ?>
                            <div class="mt-4">
                                <h6>Documentos Consultados:</h6>
                                <div class="row">
                                    <?php foreach ($response['documents'] as $doc): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card document-card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-file-alt me-2"></i>
                                                    <?= htmlspecialchars($doc['titulo']) ?>
                                                </h6>
                                                <p class="card-text small text-muted">
                                                    <?= htmlspecialchars(substr($doc['conteudo'], 0, 150)) ?>...
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-primary"><?= ucfirst($doc['tipo']) ?></span>
                                                    <span class="badge bg-secondary"><?= $doc['categoria'] ?></span>
                                                    <small class="text-muted">
                                                        Relevância: <?= round($doc['similarity'] * 100, 1) ?>%
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                                <h5 class="text-warning"><?= htmlspecialchars($response['message']) ?></h5>
                                
                                <?php if (isset($response['feedback'])): ?>
                                <div class="mt-3 text-start">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Análise de Relevância:</h6>
                                        <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($response['feedback']) ?></pre>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($response['suggestions'])): ?>
                                <div class="mt-3">
                                    <p>Perguntas sugeridas:</p>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <?php foreach ($response['suggestions'] as $suggestion): ?>
                                        <span class="badge bg-light text-dark suggestion-chip" 
                                              onclick="setQuestion('<?= htmlspecialchars($suggestion) ?>')">
                                            <?= htmlspecialchars($suggestion) ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estatísticas do Sistema
                </h3>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <h4><?= $stats['total_documentos'] ?? 0 ?></h4>
                        <p class="mb-0">Documentos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-brain fa-2x mb-2"></i>
                        <h4><?= $stats['total_embeddings'] ?? 0 ?></h4>
                        <p class="mb-0">Embeddings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-gavel fa-2x mb-2"></i>
                        <h4><?= count(array_filter($stats['por_tipo'] ?? [], fn($t) => $t['tipo'] === 'lei')) ?></h4>
                        <p class="mb-0">Leis</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-concierge-bell fa-2x mb-2"></i>
                        <h4><?= count(array_filter($stats['por_tipo'] ?? [], fn($t) => $t['tipo'] === 'servico')) ?></h4>
                        <p class="mb-0">Serviços</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 <?= Config::get('APP_NAME') ?>. Sistema de FAQ Inteligente com IA - MVP 1.0 </p>
            <p class="mb-0">
                <small class="text-muted">
                    Powered by SamucaDEV | PHP 8.4 | MySQL 8.0 | Docker
                </small>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar loading ao enviar formulário
        document.getElementById('questionForm').addEventListener('submit', function() {
            document.getElementById('loadingSpinner').style.display = 'block';
        });

        // Função para definir pergunta nos chips de sugestão
        function setQuestion(question) {
            document.querySelector('input[name="question"]').value = question;
            document.getElementById('questionForm').submit();
        }

        // Scroll suave para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html> 