<?php
require_once 'Config.php';
require_once 'DocumentManager.php';
require_once 'Database.php';

session_start();

// Verificar se está logado (sistema simples de autenticação)
if (!isset($_SESSION['user_id'])) {
    // Redirecionar para login se não estiver autenticado
    header('Location: login.php');
    exit;
}

$documentManager = new DocumentManager();
$db = Database::getInstance();

// Processar ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_document':
                try {
                    $result = $documentManager->createDocument([
                        'titulo' => $_POST['titulo'],
                        'conteudo' => $_POST['conteudo'],
                        'tipo' => $_POST['tipo'],
                        'categoria' => $_POST['categoria'],
                        'numero_documento' => $_POST['numero_documento'] ?: null,
                        'data_publicacao' => $_POST['data_publicacao'] ?: date('Y-m-d'),
                        'status' => $_POST['status']
                    ]);
                    $message = $result['message'];
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update_document':
                try {
                    $result = $documentManager->updateDocument($_POST['id'], [
                        'titulo' => $_POST['titulo'],
                        'conteudo' => $_POST['conteudo'],
                        'tipo' => $_POST['tipo'],
                        'categoria' => $_POST['categoria'],
                        'numero_documento' => $_POST['numero_documento'] ?: null,
                        'data_publicacao' => $_POST['data_publicacao'],
                        'status' => $_POST['status']
                    ]);
                    $message = $result['message'];
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_document':
                try {
                    $result = $documentManager->deleteDocument($_POST['id']);
                    $message = $result['message'];
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'reindex_all':
                try {
                    $result = $documentManager->reindexAllDocuments();
                    $message = $result['message'];
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Obter dados para exibição
$documents = $documentManager->listDocuments();
$stats = $documentManager->getDocumentStats();
$categories = $db->query("SELECT * FROM categorias WHERE status = 'ativo' ORDER BY nome");
$documentTypes = ['lei', 'regulamento', 'servico', 'informacao'];
$statusOptions = ['ativo', 'inativo', 'arquivado'];

// Filtros
$filters = [];
if (isset($_GET['tipo']) && !empty($_GET['tipo'])) $filters['tipo'] = $_GET['tipo'];
if (isset($_GET['categoria']) && !empty($_GET['categoria'])) $filters['categoria'] = $_GET['categoria'];
if (isset($_GET['status']) && !empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (isset($_GET['search']) && !empty($_GET['search'])) $filters['search'] = $_GET['search'];

if (!empty($filters)) {
    $documents = $documentManager->listDocuments($filters);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - <?= Config::get('APP_NAME') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: #495057;
        }
        .main-content {
            margin-left: 250px;
        }
        .document-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
        }
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar position-fixed" style="width: 250px;">
        <div class="p-3">
            <h4 class="text-white">
                <i class="fas fa-city me-2"></i>
                Admin
            </h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#dashboard">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            <a class="nav-link" href="#documents">
                <i class="fas fa-file-alt me-2"></i>
                Documentos
            </a>
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>
                Voltar ao Site
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>
                Sair
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Painel Administrativo</h1>
                <div>
                    <span class="text-muted">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></span>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <div id="dashboard">
                <h2 class="mb-4">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <h4><?= $stats['total_documentos'] ?? 0 ?></h4>
                                <p class="mb-0">Total de Documentos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-brain fa-2x mb-2"></i>
                                <h4><?= $stats['total_embeddings'] ?? 0 ?></h4>
                                <p class="mb-0">Total de Embeddings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h4><?= count($categories) ?></h4>
                                <p class="mb-0">Categorias Ativas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-sync fa-2x mb-2"></i>
                                <h4>Reindexar</h4>
                                <p class="mb-0">Sistema</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Ações Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#documentModal">
                                    <i class="fas fa-plus me-2"></i>
                                    Novo Documento
                                </button>
                                <button class="btn btn-warning me-2" onclick="reindexSystem()">
                                    <i class="fas fa-sync me-2"></i>
                                    Reindexar Sistema
                                </button>
                                <a href="index.php" class="btn btn-info">
                                    <i class="fas fa-eye me-2"></i>
                                    Visualizar Site
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div id="documents" class="mt-5">
                <h2 class="mb-4">
                    <i class="fas fa-file-alt me-2"></i>
                    Gerenciar Documentos
                </h2>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($documentTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= ($_GET['tipo'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= ucfirst($type) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['nome'] ?>" <?= ($_GET['categoria'] ?? '') === $cat['nome'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= $status ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="search" class="form-control" 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                                       placeholder="Palavra-chave">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>
                                    Filtrar
                                </button>
                                <a href="admin.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i>
                                    Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Documents Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Documentos (<?= count($documents) ?>)</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#documentModal">
                            <i class="fas fa-plus me-2"></i>
                            Novo
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum documento encontrado.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Categoria</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?= $doc['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($doc['titulo']) ?></strong>
                                            <?php if ($doc['numero_documento']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($doc['numero_documento']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= ucfirst($doc['tipo']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($doc['categoria']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $doc['status'] === 'ativo' ? 'success' : ($doc['status'] === 'inativo' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($doc['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($doc['data_publicacao'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editDocument(<?= $doc['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?= $doc['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="documentForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create_document">
                        <input type="hidden" name="id" id="documentId">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Título *</label>
                                    <input type="text" name="titulo" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Número do Documento</label>
                                    <input type="text" name="numero_documento" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo *</label>
                                    <select name="tipo" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($documentTypes as $type): ?>
                                        <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoria *</label>
                                    <select name="categoria" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['nome'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Data de Publicação</label>
                                    <input type="date" name="data_publicacao" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= $status ?>" <?= $status === 'ativo' ? 'selected' : '' ?>>
                                            <?= ucfirst($status) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Conteúdo *</label>
                            <textarea name="conteudo" class="form-control" rows="10" required 
                                      placeholder="Digite o conteúdo completo do documento..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funções para gerenciar documentos
        function editDocument(id) {
            // Aqui você implementaria a busca dos dados do documento
            // e preencheria o formulário
            alert('Funcionalidade de edição será implementada em breve.');
        }

        function deleteDocument(id) {
            if (confirm('Tem certeza que deseja excluir este documento?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function reindexSystem() {
            if (confirm('Tem certeza que deseja reindexar todo o sistema? Isso pode levar alguns minutos.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reindex_all">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Navegação da sidebar
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    
                    // Remover classe active de todos os links
                    document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                    
                    // Adicionar classe active ao link clicado
                    this.classList.add('active');
                    
                    // Mostrar seção correspondente
                    const targetId = this.getAttribute('href').substring(1);
                    document.querySelectorAll('div[id]').forEach(div => {
                        div.style.display = div.id === targetId ? 'block' : 'none';
                    });
                }
            });
        });

        // Mostrar dashboard por padrão
        document.getElementById('dashboard').style.display = 'block';
        document.getElementById('documents').style.display = 'none';
    </script>
</body>
</html> 