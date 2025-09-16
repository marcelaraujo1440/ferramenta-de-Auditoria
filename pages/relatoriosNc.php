<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = '';

$pdo = null;
$conexao_erro = false;
$estatisticas = [];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_itens,
            SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) as conformes,
            SUM(CASE WHEN resultado = 'Não' THEN 1 ELSE 0 END) as nao_conformes,
            SUM(CASE WHEN classificacao = 'Simples' THEN 1 ELSE 0 END) as simples,
            SUM(CASE WHEN classificacao = 'Média' THEN 1 ELSE 0 END) as media,
            SUM(CASE WHEN classificacao = 'Complexa' THEN 1 ELSE 0 END) as complexa,
            SUM(CASE WHEN prazo < NOW() AND data_conclusao IS NULL THEN 1 ELSE 0 END) as vencidas,
            SUM(CASE WHEN data_conclusao IS NOT NULL THEN 1 ELSE 0 END) as concluidas
        FROM checklist 
        WHERE descricao IS NOT NULL AND descricao != ''
    ");
    $stmt->execute();
    $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular percentual de aderência
    if ($estatisticas['total_itens'] > 0) {
        $estatisticas['percentual_aderencia'] = round(($estatisticas['conformes'] / $estatisticas['total_itens']) * 100, 1);
    } else {
        $estatisticas['percentual_aderencia'] = 0;
    }
    
    // Buscar itens por responsável
    $stmt = $pdo->prepare("
        SELECT 
            responsavel,
            COUNT(*) as total,
            SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) as conformes,
            SUM(CASE WHEN resultado = 'Não' THEN 1 ELSE 0 END) as nao_conformes
        FROM checklist 
        WHERE responsavel IS NOT NULL AND responsavel != ''
        AND descricao IS NOT NULL AND descricao != ''
        GROUP BY responsavel
        ORDER BY total DESC
    ");
    $stmt->execute();
    $por_responsavel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar itens vencidos ou próximos do vencimento
    $stmt = $pdo->prepare("
        SELECT id, descricao, responsavel, prazo, classificacao,
               DATEDIFF(prazo, NOW()) as dias_restantes
        FROM checklist 
        WHERE prazo IS NOT NULL 
        AND data_conclusao IS NULL
        AND descricao IS NOT NULL AND descricao != ''
        AND DATEDIFF(prazo, NOW()) <= 7
        ORDER BY prazo ASC
        LIMIT 10
    ");
    $stmt->execute();
    $prazos_criticos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/relatorio.css">
</head>
<body>
    <div class="container">
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="../index.php" class="nav-link">Início</a></li>
                <li><a href="../checklist.php" class="nav-link">Checklist</a></li>
                <li><a href="relatorios.php" class="nav-link">Relatórios</a></li>
                <li><a href="envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
            </ul>
        </nav>
    </div>

    <div class="page-header">
        <h1 class="page-title">Relatórios de Auditoria</h1>
    </div>

    <?php if ($conexao_erro): ?>
        <div class="error-container">
            <h3>Erro de Conexão</h3>
            <p>Não foi possível conectar ao banco de dados.</p>
        </div>
    <?php else: ?>
        <div class="reports-container">
            <!-- Estatísticas Gerais -->
            <div class="stats-grid">
                <div class="stat-card aderencia-card">
                    <div class="stat-number"><?php echo $estatisticas['percentual_aderencia']; ?>%</div>
                    <div class="stat-label">Aderência</div>
                    <div class="stat-description">Percentual de conformidade geral</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $estatisticas['total_itens']; ?></div>
                    <div class="stat-label">Total de Itens</div>
                    <div class="stat-description">Itens avaliados no checklist</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $estatisticas['nao_conformes']; ?></div>
                    <div class="stat-label">Não Conformidades</div>
                    <div class="stat-description">Itens que precisam de correção</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $estatisticas['vencidas']; ?></div>
                    <div class="stat-label">Vencidas</div>
                    <div class="stat-description">NC com prazo expirado</div>
                </div>
            </div>

            <!-- Relatório por Responsável -->
            <div class="report-section">
                <h2 class="section-title">Desempenho por Responsável</h2>
                <?php if (empty($por_responsavel)): ?>
                    <div class="no-data">Nenhum responsável encontrado</div>
                <?php else: ?>
                    <div class="responsavel-grid">
                        <?php foreach ($por_responsavel as $resp): ?>
                            <div class="responsavel-item">
                                <div class="responsavel-nome"><?php echo htmlspecialchars($resp['responsavel']); ?></div>
                                <div class="responsavel-stats">
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?php echo $resp['total']; ?></div>
                                        <div class="mini-stat-label">Total</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?php echo $resp['conformes']; ?></div>
                                        <div class="mini-stat-label">Conformes</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?php echo $resp['nao_conformes']; ?></div>
                                        <div class="mini-stat-label">NC</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number">
                                            <?php echo $resp['total'] > 0 ? round(($resp['conformes'] / $resp['total']) * 100, 1) : 0; ?>%
                                        </div>
                                        <div class="mini-stat-label">Aderência</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Prazos Críticos -->
            <div class="report-section">
                <h2 class="section-title">Prazos Críticos</h2>
                <?php if (empty($prazos_criticos)): ?>
                    <div class="no-data">Nenhum item com prazo crítico</div>
                <?php else: ?>
                    <div class="prazos-grid">
                        <?php foreach ($prazos_criticos as $item): ?>
                            <?php 
                                $classe = '';
                                $status = '';
                                if ($item['dias_restantes'] < 0) {
                                    $classe = 'prazo-vencido';
                                    $status = 'VENCIDO';
                                } elseif ($item['dias_restantes'] <= 2) {
                                    $classe = 'prazo-critico';
                                    $status = 'CRÍTICO';
                                }
                            ?>
                            <div class="prazo-item <?php echo $classe; ?>">
                                <div class="prazo-info">
                                    <div class="prazo-descricao"><?php echo htmlspecialchars($item['descricao']); ?></div>
                                    <div class="prazo-responsavel">
                                        <?php echo htmlspecialchars($item['responsavel']); ?> • 
                                        <?php echo htmlspecialchars($item['classificacao']); ?> • 
                                        ID: <?php echo $item['id']; ?>
                                    </div>
                                </div>
                                <div class="prazo-days">
                                    <div class="days-number">
                                        <?php 
                                            if ($item['dias_restantes'] < 0) {
                                                echo abs($item['dias_restantes']);
                                            } else {
                                                echo $item['dias_restantes'];
                                            }
                                        ?>
                                    </div>
                                    <div class="days-label">
                                        <?php echo $item['dias_restantes'] < 0 ? 'DIAS ATRASO' : 'DIAS REST.'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Classificação das NC -->
            <div class="report-section">
                <h2 class="section-title">Distribuição por Classificação</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estatisticas['simples']; ?></div>
                        <div class="stat-label">Simples</div>
                        <div class="stat-description">NC de baixa complexidade</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estatisticas['media']; ?></div>
                        <div class="stat-label">Média</div>
                        <div class="stat-description">NC de complexidade média</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estatisticas['complexa']; ?></div>
                        <div class="stat-label">Complexa</div>
                        <div class="stat-description">NC de alta complexidade</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação dos cards
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
