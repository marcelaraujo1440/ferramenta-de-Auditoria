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
$erro_mensagem = '';
$message = '';
$success = false;

// Conectar ao banco de dados
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar tabela se não existir
    $create_table = "
    CREATE TABLE IF NOT EXISTS nao_conformidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT NOT NULL,
        responsavel VARCHAR(100) NOT NULL,
        data_abertura DATETIME NOT NULL,
        prazo_resolucao DATETIME NOT NULL,
        status ENUM('Aberta', 'Em andamento', 'Resolvida', 'Escalonada') DEFAULT 'Aberta',
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        observacoes TEXT,
        escalonada_automaticamente BOOLEAN DEFAULT FALSE
    )";
    $pdo->exec($create_table);
    
} catch (PDOException $e) {
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}

// Buscar todas as não-conformidades
$nao_conformidades = [];
if (!$conexao_erro) {
    try {
        $stmt = $pdo->query("
            SELECT *, 
            CASE 
                WHEN prazo_resolucao < NOW() AND status NOT IN ('Resolvida', 'Escalonada') THEN 'vencida'
                WHEN DATEDIFF(prazo_resolucao, NOW()) <= 3 AND status NOT IN ('Resolvida', 'Escalonada') THEN 'critica'
                ELSE 'normal'
            END as urgencia,
            DATEDIFF(prazo_resolucao, NOW()) as dias_restantes
            FROM nao_conformidades 
            ORDER BY 
                CASE status
                    WHEN 'Escalonada' THEN 1
                    WHEN 'Aberta' THEN 2
                    WHEN 'Em andamento' THEN 3
                    WHEN 'Resolvida' THEN 4
                END,
                prazo_resolucao ASC
        ");
        $nao_conformidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_mensagem = $e->getMessage();
    }
}

// Verificar mensagens
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $success = strpos($message, '✅') !== false;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Não-Conformidades - Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/add-item.css">
    <style>
        .nc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .nc-form-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .nc-form-section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .nc-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .nc-form-group {
            display: flex;
            flex-direction: column;
        }
        
        .nc-form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .nc-form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .nc-form-group input,
        .nc-form-group textarea,
        .nc-form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .nc-form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .nc-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .nc-table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .nc-table-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.4rem;
        }
        
        .nc-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .nc-table th,
        .nc-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        
        .nc-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .nc-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-aberta { background: #fff3cd; color: #856404; }
        .status-em-andamento { background: #cce5ff; color: #0066cc; }
        .status-resolvida { background: #d4edda; color: #155724; }
        .status-escalonada { background: #f8d7da; color: #721c24; }
        
        .urgencia-badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .urgencia-vencida { background: #dc3545; color: white; }
        .urgencia-critica { background: #fd7e14; color: white; }
        .urgencia-normal { background: #28a745; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 11px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            transition: opacity 0.2s;
        }
        
        .btn-small:hover {
            opacity: 0.8;
        }
        
        .btn-update { background: #007bff; }
        .btn-view { background: #28a745; }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .sync-section {
            position: relative;
        }
        
        .update-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .update-indicator.show {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .page-header > div {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .page-header > div > div {
                flex-direction: column;
                width: 100%;
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
            </ul>
        </nav>
    </div>

    <div class="nc-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1 class="page-title">Gestão de Não-Conformidades</h1>
                    <p style="color: #666; margin-top: 10px;">Sistema completo de registro e acompanhamento de não-conformidades</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="atualizarPagina()" class="btn-secondary" style="padding: 8px 12px;">
                        🔄 Atualizar
                    </button>
                    <a href="solicitar-resolucao.php" class="btn-primary">📬 Solicitar Resolução</a>
                </div>
            </div>
        </div>

        <?php if ($conexao_erro): ?>
            <div class="alert alert-error">
                <strong>Erro de Conexão:</strong> <?= htmlspecialchars($erro_mensagem) ?>
            </div>
        <?php else: ?>

            <?php if ($message): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Estatísticas -->
            <?php
            $stats = [
                'total' => count($nao_conformidades),
                'abertas' => count(array_filter($nao_conformidades, fn($nc) => $nc['status'] === 'Aberta')),
                'em_andamento' => count(array_filter($nao_conformidades, fn($nc) => $nc['status'] === 'Em andamento')),
                'resolvidas' => count(array_filter($nao_conformidades, fn($nc) => $nc['status'] === 'Resolvida')),
                'escalonadas' => count(array_filter($nao_conformidades, fn($nc) => $nc['status'] === 'Escalonada')),
                'vencidas' => count(array_filter($nao_conformidades, fn($nc) => $nc['urgencia'] === 'vencida'))
            ];
            
            // Verificar itens do checklist que são "Não" mas não têm NC
            $itens_checklist_pendentes = [];
            if (!$conexao_erro) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.id, c.nome, c.descricao, c.responsavel, c.data_identificacao, c.prazo, c.acao_corretiva_indicada
                        FROM checklist c
                        LEFT JOIN nao_conformidades nc ON (
                            nc.titulo = CONCAT('NC - ', c.nome) OR 
                            nc.descricao LIKE CONCAT('%', c.nome, '%')
                        )
                        WHERE c.resultado = 'Não' 
                        AND c.situacao != 'Resolvido'
                        AND nc.id IS NULL
                        ORDER BY c.data_identificacao DESC
                    ");
                    $stmt->execute();
                    $itens_checklist_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // Continuar sem os dados do checklist
                }
            }
            ?>
            
            <!-- Seção de sincronização com checklist -->
            <?php if (!empty($itens_checklist_pendentes)): ?>
                <div class="sync-section" style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #ffc107;">
                    <h3 style="margin: 0 0 15px 0; color: #856404;">
                        🔄 Itens do Checklist Pendentes de NC
                    </h3>
                    <p style="margin-bottom: 15px; color: #856404;">
                        Encontramos <?= count($itens_checklist_pendentes) ?> item(ns) do checklist marcado(s) como "Não" que ainda não possuem não-conformidades registradas.
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button onclick="mostrarItensChecklist()" class="btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">
                            📋 Ver Itens (<?= count($itens_checklist_pendentes) ?>)
                        </button>
                        <button onclick="sincronizarTodosItens()" class="btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">
                            ⚡ Sincronizar Todos
                        </button>
                        <button onclick="window.location.reload()" class="btn-secondary" style="padding: 8px 15px; font-size: 0.9rem;">
                            🔄 Atualizar Lista
                        </button>
                    </div>
                    
                    <!-- Lista de itens pendentes (inicialmente oculta) -->
                    <div id="itensChecklistPendentes" style="display: none; margin-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: #856404;">Itens Pendentes:</h4>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($itens_checklist_pendentes as $item): ?>
                                <div class="item-checklist" style="background: white; padding: 15px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ffc107;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                        <div style="flex: 1;">
                                            <h5 style="margin: 0 0 8px 0; color: #333;">
                                                <?= htmlspecialchars($item['nome']) ?>
                                            </h5>
                                            <p style="margin: 0 0 8px 0; color: #666; font-size: 0.9rem;">
                                                <?= htmlspecialchars($item['descricao']) ?>
                                            </p>
                                            <div style="font-size: 0.85rem; color: #666;">
                                                <span><strong>Responsável:</strong> <?= htmlspecialchars($item['responsavel']) ?></span> |
                                                <span><strong>Data:</strong> <?= date('d/m/Y', strtotime($item['data_identificacao'])) ?></span>
                                                <?php if ($item['prazo']): ?>
                                                    | <span><strong>Prazo:</strong> <?= date('d/m/Y', strtotime($item['prazo'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button onclick="criarNCDoItem(<?= $item['id'] ?>)" 
                                                class="btn-primary" 
                                                style="padding: 6px 12px; font-size: 0.8rem; white-space: nowrap;">
                                            + Criar NC
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total</h3>
                    <div class="number"><?= $stats['total'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Abertas</h3>
                    <div class="number"><?= $stats['abertas'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Em Andamento</h3>
                    <div class="number"><?= $stats['em_andamento'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Resolvidas</h3>
                    <div class="number"><?= $stats['resolvidas'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Escalonadas</h3>
                    <div class="number"><?= $stats['escalonadas'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Vencidas</h3>
                    <div class="number"><?= $stats['vencidas'] ?></div>
                </div>
            </div>

            <!-- Lista de Não-Conformidades -->
            <div class="nc-table-container">
                <div class="nc-table-header">
                    <h2>📋 Não-Conformidades Registradas</h2>
                </div>
                
                <?php if (empty($nao_conformidades)): ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <p>📋 Nenhuma não-conformidade registrada ainda.</p>
                        <p>As não-conformidades são criadas automaticamente a partir do checklist quando itens são marcados como "Não".</p>
                        <?php if (!empty($itens_checklist_pendentes)): ?>
                            <p style="color: #856404; font-weight: 600;">
                                ⚠️ Há <?= count($itens_checklist_pendentes) ?> item(ns) do checklist pendente(s) para sincronização!
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <table class="nc-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Responsável</th>
                                <th>Data Abertura</th>
                                <th>Prazo</th>
                                <th>Status</th>
                                <th>Urgência</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nao_conformidades as $nc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($nc['id']) ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($nc['titulo']) ?>">
                                        <?= htmlspecialchars(strlen($nc['titulo']) > 30 ? substr($nc['titulo'], 0, 30) . '...' : $nc['titulo']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($nc['responsavel']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($nc['data_abertura'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($nc['prazo_resolucao'])) ?>
                                        <?php if ($nc['dias_restantes'] !== null): ?>
                                            <br><small style="color: #666;">
                                                <?= $nc['dias_restantes'] < 0 ? 'Vencida há ' . abs($nc['dias_restantes']) : $nc['dias_restantes'] ?> dias
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace(' ', '-', strtolower($nc['status'])) ?>">
                                            <?= htmlspecialchars($nc['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="urgencia-badge urgencia-<?= $nc['urgencia'] ?>">
                                            <?= ucfirst($nc['urgencia']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="solicitar-resolucao.php?nc_id=<?= $nc['id'] ?>" class="btn-small btn-primary">📬 Solicitar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-remover mensagens de alerta após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Definir prazo padrão para 7 dias a partir de agora
            const prazoInput = document.getElementById('prazo_resolucao');
            if (prazoInput && !prazoInput.value) {
                const agora = new Date();
                agora.setDate(agora.getDate() + 7);
                prazoInput.value = agora.toISOString().slice(0, 16);
            }
            
            // Verificação automática de novos itens do checklist a cada 30 segundos
            let ultimaVerificacao = Date.now();
            setInterval(async () => {
                try {
                    const response = await fetch('verificar-novos-itens.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ultima_verificacao: ultimaVerificacao })
                    });
                    
                    if (response.ok) {
                        const dados = await response.json();
                        if (dados.novos_itens > 0) {
                            mostrarNotificacaoNovosItens(dados.novos_itens);
                        }
                        ultimaVerificacao = Date.now();
                    }
                } catch (error) {
                    console.log('Verificação automática pausada:', error.message);
                }
            }, 30000); // 30 segundos
        });
        
        function mostrarNotificacaoNovosItens(quantidade) {
            // Criar notificação
            const notificacao = document.createElement('div');
            notificacao.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #ffc107;
                color: #856404;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 600;
                border-left: 4px solid #856404;
            `;
            notificacao.innerHTML = `
                🆕 ${quantidade} novo(s) item(ns) do checklist encontrado(s)!
                <button onclick="location.reload()" style="margin-left: 10px; padding: 5px 10px; background: #856404; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    🔄 Atualizar
                </button>
            `;
            
            document.body.appendChild(notificacao);
            
            // Remover após 10 segundos
            setTimeout(() => {
                notificacao.remove();
            }, 10000);
        }
        
        function mostrarItensChecklist() {
            const div = document.getElementById('itensChecklistPendentes');
            const button = event.target;
            
            if (div.style.display === 'none') {
                div.style.display = 'block';
                button.textContent = '📋 Ocultar Itens';
            } else {
                div.style.display = 'none';
                button.textContent = '📋 Ver Itens (<?= count($itens_checklist_pendentes ?? []) ?>)';
            }
        }
        
        function criarNCDoItem(itemId) {
            if (!confirm('Deseja criar uma não-conformidade para este item do checklist?')) {
                return;
            }
            
            // Enviar requisição para criar NC
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'sincronizar-checklist.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'item_id';
            input.value = itemId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        
        function sincronizarTodosItens() {
            const count = <?= count($itens_checklist_pendentes ?? []) ?>;
            if (!confirm(`Deseja criar não-conformidades para todos os ${count} itens pendentes?`)) {
                return;
            }
            
            // Enviar requisição para sincronizar todos
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'sincronizar-checklist.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sincronizar_todos';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        
        function atualizarPagina() {
            // Recarregar a página imediatamente
            window.location.reload();
        }
    </script>
</body>
</html>
