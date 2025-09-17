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

if ($nc_id > 0 && !$conexao_erro) {
    try {
        $stmt = $pdo->prepare("
            SELECT *, 
            CASE 
                WHEN prazo_resolucao < NOW() AND status NOT IN ('Resolvida', 'Escalonada') THEN 'vencida'
                WHEN DATEDIFF(prazo_resolucao, NOW()) <= 3 AND status NOT IN ('Resolvida', 'Escalonada') THEN 'critica'
                ELSE 'normal'
            END as urgencia,
            DATEDIFF(prazo_resolucao, NOW()) as dias_restantes
            FROM nao_conformidades 
            WHERE id = ?
        ");
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
    <title>NC #<?= $nc['id'] ?> - <?= htmlspecialchars($nc['titulo']) ?></title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        .view-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .nc-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .nc-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .nc-header h1 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }
        
        .nc-header .nc-id {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .nc-content {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .info-item.status {
            border-left-color: #28a745;
        }
        
        .info-item.urgencia {
            border-left-color: #dc3545;
        }
        
        .info-item.prazo {
            border-left-color: #ffc107;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-aberta { background: #fff3cd; color: #856404; }
        .status-em-andamento { background: #cce5ff; color: #0066cc; }
        .status-resolvida { background: #d4edda; color: #155724; }
        .status-escalonada { background: #f8d7da; color: #721c24; }
        
        .urgencia-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .urgencia-vencida { background: #dc3545; color: white; }
        .urgencia-critica { background: #fd7e14; color: white; }
        .urgencia-normal { background: #28a745; color: white; }
        
        .description-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .description-section h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .description-text {
            line-height: 1.6;
            color: #555;
            white-space: pre-wrap;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .timeline {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .timeline h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .timeline-icon {
            margin-right: 10px;
            color: #007bff;
        }
        
        @media (max-width: 768px) {
            .view-container {
                padding: 10px;
            }
            
            .nc-content {
                padding: 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
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
                <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
            </ul>
        </nav>
    </div>

    <div class="view-container">
        <div class="nc-card">
            <div class="nc-header">
                <h1><?= htmlspecialchars($nc['titulo']) ?></h1>
                <div class="nc-id">Não-Conformidade #<?= htmlspecialchars($nc['id']) ?></div>
            </div>
            
            <div class="nc-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Responsável</div>
                        <div class="info-value"><?= htmlspecialchars($nc['responsavel']) ?></div>
                    </div>
                    
                    <div class="info-item status">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?= str_replace(' ', '-', strtolower($nc['status'])) ?>">
                                <?= htmlspecialchars($nc['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item urgencia">
                        <div class="info-label">Urgência</div>
                        <div class="info-value">
                            <span class="urgencia-badge urgencia-<?= $nc['urgencia'] ?>">
                                <?= ucfirst($nc['urgencia']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item prazo">
                        <div class="info-label">Prazo de Resolução</div>
                        <div class="info-value">
                            <?= date('d/m/Y H:i', strtotime($nc['prazo_resolucao'])) ?>
                            <?php if ($nc['dias_restantes'] !== null): ?>
                                <br><small style="color: #666;">
                                    <?= $nc['dias_restantes'] < 0 ? 'Vencida há ' . abs($nc['dias_restantes']) : $nc['dias_restantes'] ?> dias
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="description-section">
                    <h3>📝 Descrição</h3>
                    <div class="description-text"><?= htmlspecialchars($nc['descricao']) ?></div>
                </div>
                
                <?php if (!empty($nc['observacoes'])): ?>
                    <div class="description-section">
                        <h3>💬 Observações</h3>
                        <div class="description-text"><?= htmlspecialchars($nc['observacoes']) ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="timeline">
                    <h3>📅 Linha do Tempo</h3>
                    <div class="timeline-item">
                        <span class="timeline-icon">📅</span>
                        <span>Criada em: <?= date('d/m/Y H:i', strtotime($nc['data_criacao'])) ?></span>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-icon">🕐</span>
                        <span>Data de abertura: <?= date('d/m/Y H:i', strtotime($nc['data_abertura'])) ?></span>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-icon">⏰</span>
                        <span>Prazo: <?= date('d/m/Y H:i', strtotime($nc['prazo_resolucao'])) ?></span>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-icon">🔄</span>
                        <span>Última atualização: <?= date('d/m/Y H:i', strtotime($nc['data_atualizacao'])) ?></span>
                    </div>
                    <?php if ($nc['escalonada_automaticamente']): ?>
                        <div class="timeline-item">
                            <span class="timeline-icon">🚨</span>
                            <span style="color: #dc3545; font-weight: 600;">Escalonada automaticamente por vencimento</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="nao_conformidades.php" class="btn btn-secondary">← Voltar</a>
                    <a href="editar_nc.php?id=<?= $nc['id'] ?>" class="btn btn-primary">✏️ Editar</a>
                    
                    <?php if ($nc['status'] !== 'Escalonada'): ?>
                        <a href="escalar_nc.php?id=<?= $nc['id'] ?>" class="btn btn-danger" 
                           onclick="return confirm('Deseja escalonar esta não-conformidade?')">
                            📈 Escalonar
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($nc['status'] === 'Escalonada'): ?>
                        <a href="enviar-email-nc.php?nc_id=<?= $nc['id'] ?>" class="btn btn-success">
                            📧 Reenviar Email
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
