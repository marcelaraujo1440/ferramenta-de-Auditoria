<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

$pdo = null;
$conexao_erro = false;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}

// Buscar NC pelo ID
$nc_id = intval($_GET['id'] ?? 0);
$nc = null;
$message = $_GET['msg'] ?? '';

if ($nc_id > 0 && !$conexao_erro) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM nao_conformidades WHERE id = ?");
        $stmt->execute([$nc_id]);
        $nc = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_mensagem = $e->getMessage();
    }
}

if (!$nc) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Não-conformidade não encontrada!"));
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar NC #<?= $nc['id'] ?> - Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/add-item.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .edit-form-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .edit-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .status-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Início</a></li>
                <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                <li><a href="pages/relatoriosNc.php" class="nav-link">Relatórios</a></li>
                <li><a href="nao_conformidades.php" class="nav-link">Não-Conformidades</a></li>
            </ul>
        </nav>
    </div>

    <div class="edit-container">
        <div class="page-header">
            <h1 class="page-title">Editar Não-Conformidade #<?= htmlspecialchars($nc['id']) ?></h1>
        </div>

        <?php if ($conexao_erro): ?>
            <div class="alert alert-error">
                <strong>Erro de Conexão:</strong> <?= htmlspecialchars($erro_mensagem) ?>
            </div>
        <?php else: ?>

            <?php if ($message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="status-info">
                <h3>Informações Atuais</h3>
                <p><strong>Criada em:</strong> <?= date('d/m/Y H:i', strtotime($nc['data_criacao'])) ?></p>
                <p><strong>Última atualização:</strong> <?= date('d/m/Y H:i', strtotime($nc['data_atualizacao'])) ?></p>
                <p><strong>Status atual:</strong> <?= htmlspecialchars($nc['status']) ?></p>
            </div>

            <div class="edit-form-section">
                <form method="POST" action="processa_nc.php" class="edit-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($nc['id']) ?>">
                    
                    <div class="form-group">
                        <label for="titulo">Título da NC *</label>
                        <input type="text" id="titulo" name="titulo" required maxlength="255" 
                               value="<?= htmlspecialchars($nc['titulo']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="responsavel">Responsável *</label>
                        <input type="text" id="responsavel" name="responsavel" required maxlength="100"
                               value="<?= htmlspecialchars($nc['responsavel']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_abertura">Data de Abertura *</label>
                        <input type="datetime-local" id="data_abertura" name="data_abertura" required
                               value="<?= date('Y-m-d\TH:i', strtotime($nc['data_abertura'])) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="prazo_resolucao">Prazo de Resolução *</label>
                        <input type="datetime-local" id="prazo_resolucao" name="prazo_resolucao" required
                               value="<?= date('Y-m-d\TH:i', strtotime($nc['prazo_resolucao'])) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="Aberta" <?= $nc['status'] === 'Aberta' ? 'selected' : '' ?>>Aberta</option>
                            <option value="Em andamento" <?= $nc['status'] === 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
                            <option value="Resolvida" <?= $nc['status'] === 'Resolvida' ? 'selected' : '' ?>>Resolvida</option>
                            <option value="Escalonada" <?= $nc['status'] === 'Escalonada' ? 'selected' : '' ?>>Escalonada</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="descricao">Descrição Detalhada *</label>
                        <textarea id="descricao" name="descricao" required><?= htmlspecialchars($nc['descricao']) ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes"><?= htmlspecialchars($nc['observacoes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="nao_conformidades.php" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
