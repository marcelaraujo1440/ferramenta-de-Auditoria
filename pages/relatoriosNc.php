<?php
session_start();

/* ======================= DB CONFIG ======================= */
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = '';

$pdo = null;
$conexao_erro = false;
$erro_mensagem = '';

$estatisticas = [];
$por_responsavel = [];
$prazos_criticos = [];
$evolucao_diaria = [];
$eficiencia = [];
$alertas = [];

/* ======================= BOOTSTRAP PDO ======================= */
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}

/* ======================= AJAX (APIs) ======================= */
if (!$conexao_erro && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'items_by_day') {
        header('Content-Type: application/json; charset=utf-8');
        $date = $_GET['date'] ?? '';
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['ok' => false, 'error' => 'Parâmetro "date" inválido.']); exit;
        }
        try {
            $stmt = $pdo->prepare("
                SELECT id, nome, descricao, resultado, responsavel, classificacao,
                       data_identificacao, prazo, data_escalonamento, data_conclusao
                FROM checklist
                WHERE DATE(COALESCE(data_identificacao, prazo)) = :d
                  AND (descricao IS NOT NULL AND descricao != '')
                ORDER BY id DESC
            ");
            $stmt->execute([':d' => $date]);
            echo json_encode(['ok' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'items_by_responsavel') {
        header('Content-Type: application/json; charset=utf-8');
        $resp = $_GET['responsavel'] ?? '';
        if ($resp === '') {
            echo json_encode(['ok' => false, 'error' => 'Parâmetro "responsavel" inválido.']); exit;
        }
        try {
            $stmt = $pdo->prepare("
                SELECT id, nome, descricao, resultado, responsavel, classificacao,
                       data_identificacao, prazo, data_escalonamento, data_conclusao
                FROM checklist
                WHERE responsavel = :r
                  AND (descricao IS NOT NULL AND descricao != '')
                ORDER BY
                  CASE WHEN data_conclusao IS NULL THEN 0 ELSE 1 END ASC,
                  prazo ASC
            ");
            $stmt->execute([':r' => $resp]);
            echo json_encode(['ok' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'export_csv') {
        // Gera um CSV executivo simples com evolução diária e estatísticas
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_executivo.csv"');

        $out = fopen('php://output', 'w');

        // estatísticas gerais
        fputcsv($out, ['Resumo']);
        fputcsv($out, ['Total Itens', 'Conformes', 'Não Conformes', 'Vencidas', 'Concluídas', 'Aderência (%)']);
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_itens,
                SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) AS conformes,
                SUM(CASE WHEN resultado = 'Não' THEN 1 ELSE 0 END) AS nao_conformes,
                SUM(CASE WHEN prazo IS NOT NULL AND prazo < NOW() AND data_conclusao IS NULL THEN 1 ELSE 0 END) AS vencidas,
                SUM(CASE WHEN data_conclusao IS NOT NULL THEN 1 ELSE 0 END) AS concluidas
            FROM checklist
            WHERE (descricao IS NOT NULL AND descricao != '')
        ");
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_itens'=>0,'conformes'=>0,'nao_conformes'=>0,'vencidas'=>0,'concluidas'=>0];
        $ader = ($r['total_itens']>0) ? round(($r['conformes']/$r['total_itens'])*100,1) : 0;
        fputcsv($out, [$r['total_itens'],$r['conformes'],$r['nao_conformes'],$r['vencidas'],$r['concluidas'],$ader]);

        fputcsv($out, []); // linha em branco

        // evolução diária
        fputcsv($out, ['Evolução Diária (últimos 6 meses)']);
        fputcsv($out, ['Dia','Total','Conformes','Aderência (%)']);
        $stmt = $pdo->query("
            SELECT DATE(COALESCE(data_identificacao, prazo)) AS dia,
                   COUNT(*) AS total,
                   SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) AS conformes
            FROM checklist
            WHERE COALESCE(data_identificacao, prazo) IS NOT NULL
              AND COALESCE(data_identificacao, prazo) >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              AND (descricao IS NOT NULL AND descricao != '')
            GROUP BY DATE(COALESCE(data_identificacao, prazo))
            ORDER BY dia ASC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $t = (int)$row['total']; $c = (int)$row['conformes'];
            $p = $t>0 ? round(($c/$t)*100,1) : 0;
            fputcsv($out, [$row['dia'],$t,$c,$p]);
        }
        fclose($out);
        exit;
    }
}

/* ======================= CARREGAR DADOS ======================= */
if (!$conexao_erro) {
    try {
        // Estatísticas
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_itens,
                SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) AS conformes,
                SUM(CASE WHEN resultado = 'Não' THEN 1 ELSE 0 END) AS nao_conformes,
                SUM(CASE WHEN classificacao = 'Simples' THEN 1 ELSE 0 END) AS simples,
                SUM(CASE WHEN classificacao = 'Média' THEN 1 ELSE 0 END) AS media,
                SUM(CASE WHEN classificacao = 'Complexa' THEN 1 ELSE 0 END) AS complexa,
                SUM(CASE WHEN prazo IS NOT NULL AND prazo < NOW() AND data_conclusao IS NULL THEN 1 ELSE 0 END) AS vencidas,
                SUM(CASE WHEN data_conclusao IS NOT NULL THEN 1 ELSE 0 END) AS concluidas
            FROM checklist
            WHERE (descricao IS NOT NULL AND descricao != '')
        ");
        $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach (['total_itens','conformes','nao_conformes','simples','media','complexa','vencidas','concluidas'] as $k) {
            $estatisticas[$k] = isset($estatisticas[$k]) ? (int)$estatisticas[$k] : 0;
        }
        $estatisticas['percentual_aderencia'] = ($estatisticas['total_itens']>0)
            ? round(($estatisticas['conformes']/$estatisticas['total_itens'])*100,1) : 0.0;

        // Responsável
        $stmt = $pdo->query("
            SELECT 
                responsavel,
                COUNT(*) AS total,
                SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) AS conformes,
                SUM(CASE WHEN resultado = 'Não' THEN 1 ELSE 0 END) AS nao_conformes
            FROM checklist
            WHERE (responsavel IS NOT NULL AND responsavel != '')
              AND (descricao IS NOT NULL AND descricao != '')
            GROUP BY responsavel
            ORDER BY (SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) / COUNT(*)) DESC
        ");
        $por_responsavel = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prazos críticos
        $stmt = $pdo->query("
            SELECT 
                id, descricao, responsavel, prazo, classificacao,
                DATEDIFF(prazo, NOW()) AS dias_restantes
            FROM checklist
            WHERE prazo IS NOT NULL
              AND data_conclusao IS NULL
              AND (descricao IS NOT NULL AND descricao != '')
              AND DATEDIFF(prazo, NOW()) <= 7
            ORDER BY prazo ASC
            LIMIT 20
        ");
        $prazos_criticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Evolução diária
        $stmt = $pdo->query("
            SELECT 
                DATE(COALESCE(data_identificacao, prazo)) AS dia,
                COUNT(*) AS total,
                SUM(CASE WHEN resultado = 'Sim' THEN 1 ELSE 0 END) AS conformes
            FROM checklist
            WHERE COALESCE(data_identificacao, prazo) IS NOT NULL
              AND COALESCE(data_identificacao, prazo) >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              AND (descricao IS NOT NULL AND descricao != '')
            GROUP BY DATE(COALESCE(data_identificacao, prazo))
            ORDER BY dia ASC
        ");
        $evolucao_diaria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Eficiência
        $taxa_conclusao = ($estatisticas['total_itens']>0) ? round(($estatisticas['concluidas']/$estatisticas['total_itens'])*100,1) : 0.0;

        $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(DAY, data_identificacao, data_conclusao)) FROM checklist WHERE data_conclusao IS NOT NULL AND data_identificacao IS NOT NULL");
        $avg_days = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(DAY, data_identificacao, data_escalonamento)) FROM checklist WHERE data_escalonamento IS NOT NULL AND data_identificacao IS NOT NULL");
        $avg_escalon = (float)($stmt->fetchColumn() ?: 0);

        $total_dias = max(1, count($evolucao_diaria));
        $soma_total_por_dia = array_sum(array_map(fn($r)=>(int)$r['total'], $evolucao_diaria));
        $media_itens_dia = round($soma_total_por_dia / $total_dias, 1);

        $eficiencia = [
            'taxa_conclusao' => $taxa_conclusao,
            'tempo_medio_resolucao' => round($avg_days, 1),
            'tempo_medio_escalonamento' => round($avg_escalon, 1),
            'media_itens_por_dia' => $media_itens_dia
        ];

        // Alertas
        if ($estatisticas['vencidas'] > 0) {
            $alertas[] = ['tipo'=>'risco','titulo'=>'Itens vencidos','descricao'=>"Existem {$estatisticas['vencidas']} item(ns) com prazo expirado."];
        }
        $total_class = max(1, $estatisticas['simples'] + $estatisticas['media'] + $estatisticas['complexa']);
        $p_complexa = round(($estatisticas['complexa'] / $total_class) * 100, 1);
        if ($p_complexa >= 40) {
            $alertas[] = ['tipo'=>'atencao','titulo'=>'Complexidades elevadas','descricao'=>"$p_complexa% das NCs são classificadas como Complexa."];
        }
        $n = count($evolucao_diaria);
        if ($n >= 14) {
            $last7 = array_slice($evolucao_diaria, -7);
            $prev7 = array_slice($evolucao_diaria, -14, 7);
            $avg = function($arr){ $s=0;$c=0; foreach($arr as $r){ $t=(int)$r['total']; $x=(int)$r['conformes']; if($t>0){ $s+=($x/$t)*100; $c++;}} return $c?round($s/$c,1):0; };
            $avg_last7=$avg($last7); $avg_prev7=$avg($prev7);
            if ($avg_prev7>0 && ($avg_prev7-$avg_last7)>=10) {
                $alertas[]=['tipo'=>'risco','titulo'=>'Queda na aderência','descricao'=>"A aderência média caiu de {$avg_prev7}% para {$avg_last7}% nos últimos 7 dias."];
            }
        }
        $stmt = $pdo->query("
            SELECT responsavel, COUNT(*) AS pendentes
            FROM checklist
            WHERE resultado = 'Não' AND data_conclusao IS NULL AND (responsavel IS NOT NULL AND responsavel != '')
            GROUP BY responsavel
            ORDER BY pendentes DESC
            LIMIT 1
        ");
        $topPend = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($topPend && (int)$topPend['pendentes']>0) {
            $alertas[] = [
                'tipo'=>'atencao',
                'titulo'=>'Foco por responsável',
                'descricao'=> htmlspecialchars($topPend['responsavel'])." possui ".(int)$topPend['pendentes']." pendência(s) não conforme(s).",
                'action'=>['tipo'=>'responsavel','valor'=>$topPend['responsavel']]
            ];
        }

    } catch (PDOException $e) {
        $conexao_erro = true;
        $erro_mensagem = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Relatórios - Ferramenta de Auditoria</title>

<link rel="stylesheet" href="../styles/style.css">
<link rel="stylesheet" href="../styles/relatorio.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<style>
/* ===== Complementos específicos (FAB/painel/ajustes) ===== */
:root{
  --bg:#ffffff; --text:#0F172A; --muted:#64748B; --border:#E5E7EB; --card:#ffffff; --subtle:#F8FAFC;
}
[data-theme="dark"]{
  --bg:#0b0e11; --text:#E5E7EB; --muted:#94A3B8; --border:#1f2429; --card:#11161b; --subtle:#0d1116;
}
body{ background:var(--bg); color:var(--text); }

.export-bar{
  display:flex; justify-content:flex-end; gap:10px; margin: -10px 0 20px;
}
.btn-export{
  background: var(--card);
  color: var(--text);
  border: 1px solid var(--border);
  padding: 10px 14px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .5px;
  cursor: pointer; border-radius: 6px;
  transition: transform .18s ease, box-shadow .18s ease;
}
.btn-export:hover{ transform: translateY(-1px); box-shadow: 0 10px 24px rgba(0,0,0,.12); }

/* KPI FAB */
.kpi-fab{
  position: fixed;
  right: 24px; bottom: 24px;
  z-index: 12000;
  width: 52px; height: 52px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  display: grid; place-items: center;
  box-shadow: 0 12px 28px rgba(0,0,0,.14);
  cursor: pointer;
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.kpi-fab:hover{ transform: translateY(-1px); box-shadow: 0 16px 36px rgba(0,0,0,.18); }

/* Painel */
.kpi-panel{
  position: fixed;
  right: 24px; bottom: 88px;
  width: min(360px, 92vw);
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px 14px 14px;
  box-shadow: 0 18px 40px rgba(0,0,0,.16);
  transform-origin: 100% 100%;
  transform: scale(.98) translateY(8px);
  opacity: 0; visibility: hidden;
  transition: transform .18s ease, opacity .18s ease, visibility 0s linear .18s;
  z-index: 11999;
  will-change: transform, opacity;
  pointer-events: none;
}
.kpi-panel.open{
  transform: scale(1) translateY(0);
  opacity: 1; visibility: visible;
  transition: transform .18s ease, opacity .18s ease;
  pointer-events: auto;
}
.kpi-panel-header{
  display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;
}
.kpi-panel-title{ font-weight:800; font-size:14px; }
.kpi-panel-body{
  display:grid; grid-template-columns: repeat(2, minmax(0,1fr));
  gap:10px 12px;
}
.kpi-panel label{ display:flex; gap:8px; align-items:center; font-size:13px; color:var(--muted); }

/* Modal overrides to ensure above charts */
.modal{ z-index: 13000; }
</style>
</head>
<body>
<div class="container">
    <!-- NAV -->
    <nav class="main-nav">
        <ul class="nav-list">
            <li><a href="../index.php" class="nav-link">Início</a></li>
            <li><a href="../checklist.php" class="nav-link">Checklist</a></li>
            <li><a href="relatoriosNc.php" class="nav-link">Relatórios</a></li>
            <li><a href="envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
        </ul>
    </nav>

    <div class="page-header fade-in">
        <h1 class="page-title">Relatórios de Auditoria</h1>
        <div class="export-bar">
            <a class="btn-export" href="relatoriosNc.php?action=export_csv">Exportar CSV</a>
        </div>
    </div>

    <?php if ($conexao_erro): ?>
        <div class="error-container fade-in">
            <h3>Erro de Conexão</h3>
            <p>Não foi possível conectar ao banco de dados.</p>
            <p><?= htmlspecialchars($erro_mensagem) ?></p>
        </div>
    <?php else: ?>

        <!-- ALERTAS -->
        <?php if (!empty($alertas)): ?>
            <div class="alerts-grid fade-in" style="margin-top:10px;">
                <?php foreach ($alertas as $al): ?>
                    <div class="alert-card <?= htmlspecialchars($al['tipo']) ?>">
                        <div class="alert-title"><?= htmlspecialchars($al['titulo']) ?></div>
                        <div class="alert-desc"><?= htmlspecialchars($al['descricao']) ?></div>
                        <?php if (!empty($al['action']) && $al['action']['tipo'] === 'responsavel'): ?>
                            <div class="alert-actions">
                                <button class="btn-secondary" type="button"
                                        onclick="openResponsavelDrill('<?= htmlspecialchars($al['action']['valor'], ENT_QUOTES) ?>')">
                                    Ver itens de <?= htmlspecialchars($al['action']['valor']) ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="reportRoot" class="reports-container fade-in">
            <!-- KPIs -->
            <div class="stats-grid">
                <div id="kpi-aderencia" class="stat-card aderencia-card">
                    <div class="stat-number" data-target="<?= $estatisticas['percentual_aderencia'] ?>">0</div>
                    <div class="stat-label">Aderência</div>
                    <div class="stat-description">Percentual de conformidade geral</div>
                </div>
                <div id="kpi-total" class="stat-card">
                    <div class="stat-number" data-target="<?= $estatisticas['total_itens'] ?>">0</div>
                    <div class="stat-label">Total de Itens</div>
                    <div class="stat-description">Itens avaliados no checklist</div>
                </div>
                <div id="kpi-nc" class="stat-card">
                    <div class="stat-number" data-target="<?= $estatisticas['nao_conformes'] ?>">0</div>
                    <div class="stat-label">Não Conformidades</div>
                    <div class="stat-description">Itens que precisam de correção</div>
                </div>
                <div id="kpi-vencidas" class="stat-card">
                    <div class="stat-number" data-target="<?= $estatisticas['vencidas'] ?>">0</div>
                    <div class="stat-label">Vencidas</div>
                    <div class="stat-description">NC com prazo expirado</div>
                </div>

                <div id="kpi-taxa" class="stat-card">
                    <div class="stat-number"><?= number_format($eficiencia['taxa_conclusao'] ?? 0, 1) ?>%</div>
                    <div class="stat-label">Taxa de Conclusão</div>
                    <div class="stat-description">Concluídos sobre o total</div>
                </div>
                <div id="kpi-resolucao" class="stat-card">
                    <div class="stat-number"><?= number_format($eficiencia['tempo_medio_resolucao'] ?? 0, 1) ?></div>
                    <div class="stat-label">Tempo Médio Resolução</div>
                    <div class="stat-description">Dias entre identificação e conclusão</div>
                </div>
                <div id="kpi-escalonamento" class="stat-card">
                    <div class="stat-number"><?= number_format($eficiencia['tempo_medio_escalonamento'] ?? 0, 1) ?></div>
                    <div class="stat-label">Tempo Médio Escalonamento</div>
                    <div class="stat-description">Dias até o escalonamento</div>
                </div>
                <div id="kpi-media-dia" class="stat-card">
                    <div class="stat-number"><?= number_format($eficiencia['media_itens_por_dia'] ?? 0, 1) ?></div>
                    <div class="stat-label">Itens/Dia (média)</div>
                    <div class="stat-description">Volume médio diário</div>
                </div>
            </div>

            <!-- Gráficos -->
            <?php if (!empty($evolucao_diaria)): ?>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3 class="section-title">Evolução da Aderência</h3>
                        <div class="chart-controls">
                            <button class="btn-secondary" type="button" data-range="30">30 dias</button>
                            <button class="btn-secondary" type="button" data-range="60">60 dias</button>
                            <button class="btn-secondary" type="button" data-range="90">90 dias</button>
                            <button class="btn-secondary" type="button" data-range="all">Tudo</button>
                            <label style="margin-left:10px;">
                                <input type="checkbox" id="smaToggle" />
                                Suavizar (SMA 7)
                            </label>
                        </div>
                        <div class="chart-container">
                            <canvas id="evolutionChart"></canvas>
                        </div>
                        <div id="evolutionDetail" class="stat-card" style="margin-top:16px; display:none;">
                            <div class="stat-label">Detalhe</div>
                            <div class="stat-number" id="evolutionDetailNum">0%</div>
                            <div class="stat-description" id="evolutionDetailDesc">—</div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3 class="section-title">Distribuição por Classificação</h3>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Por responsável -->
            <div class="report-section">
                <h2 class="section-title">Desempenho por Responsável</h2>
                <?php if (empty($por_responsavel)): ?>
                    <div class="no-data">Nenhum responsável encontrado</div>
                <?php else: ?>
                    <div class="responsavel-grid">
                        <?php foreach ($por_responsavel as $index => $resp):
                            $aderenciaResp = ($resp['total'] > 0) ? round(($resp['conformes'] / $resp['total']) * 100, 1) : 0;
                            $emoji = $index === 0 ? '🏆' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : ''));
                        ?>
                            <div class="responsavel-item" onclick="openResponsavelDrill('<?= htmlspecialchars($resp['responsavel'], ENT_QUOTES) ?>')" style="cursor:pointer;">
                                <div class="responsavel-nome">
                                    <?= $emoji . ' ' . htmlspecialchars($resp['responsavel']) ?>
                                </div>
                                <div class="responsavel-stats">
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?= (int)$resp['total'] ?></div>
                                        <div class="mini-stat-label">Total</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?= (int)$resp['conformes'] ?></div>
                                        <div class="mini-stat-label">Conformes</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?= (int)$resp['nao_conformes'] ?></div>
                                        <div class="mini-stat-label">NC</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-number"><?= $aderenciaResp ?>%</div>
                                        <div class="mini-stat-label">Aderência</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Prazos críticos -->
            <div class="report-section">
                <h2 class="section-title">Prazos Críticos</h2>
                <?php if (empty($prazos_criticos)): ?>
                    <div class="no-data">Nenhum item com prazo crítico</div>
                <?php else: ?>
                    <?php foreach ($prazos_criticos as $item):
                        $classe = '';
                        if ($item['dias_restantes'] < 0)      $classe = 'prazo-vencido';
                        elseif ($item['dias_restantes'] <= 2) $classe = 'prazo-critico';
                    ?>
                        <div class="prazo-item <?= $classe ?>">
                            <div class="prazo-info">
                                <div class="prazo-descricao"><?= htmlspecialchars($item['descricao']) ?></div>
                                <div class="prazo-responsavel">
                                    <?= htmlspecialchars($item['responsavel']) ?> • 
                                    <?= htmlspecialchars($item['classificacao']) ?> • 
                                    ID: <?= (int)$item['id'] ?>
                                </div>
                            </div>
                            <div class="prazo-days">
                                <div class="days-number">
                                    <?= ($item['dias_restantes'] < 0) ? abs((int)$item['dias_restantes']) : (int)$item['dias_restantes'] ?>
                                </div>
                                <div class="days-label">
                                    <?= ($item['dias_restantes'] < 0) ? 'DIAS ATRASO' : 'DIAS REST.' ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- KPI FAB + Painel -->
<button id="kpiFab" class="kpi-fab" aria-controls="kpiPanel" aria-expanded="false" title="Configurar KPIs">⚙️</button>
<div id="kpiPanel" class="kpi-panel" role="dialog" aria-labelledby="kpiPanelTitle" aria-modal="true">
  <div class="kpi-panel-header">
    <div id="kpiPanelTitle" class="kpi-panel-title">KPIs Visíveis</div>
    <button id="kpiPanelClose" class="btn-secondary" type="button">Fechar</button>
  </div>
  <div class="kpi-panel-body">
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-aderencia" checked> Aderência</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-total" checked> Total de Itens</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-nc" checked> Não Conformidades</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-vencidas" checked> Vencidas</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-taxa" checked> Taxa de Conclusão</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-resolucao" checked> Tempo Médio Resolução</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-escalonamento" checked> Tempo Médio Escalonamento</label>
    <label><input type="checkbox" class="kpi-toggle" data-target="#kpi-media-dia" checked> Itens/Dia (média)</label>
  </div>
</div>

<!-- Modal Drill-Down -->
<div id="drillModal" class="modal" aria-hidden="true">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="drillTitle">Detalhes</div>
            <button class="modal-close" type="button" onclick="closeDrill()">Fechar</button>
        </div>
        <div id="drillBody"></div>
    </div>
</div>

<script>
// ===== Animate KPIs =====
function animateNumbers() {
  document.querySelectorAll('[data-target]').forEach(el => {
    const isPercent = el.closest('.aderencia-card') !== null;
    const target = parseFloat(el.getAttribute('data-target')) || 0;
    let current = 0, steps = 50, inc = target / steps;
    const timer = setInterval(() => {
      current += inc;
      if (current >= target) { current = target; clearInterval(timer); }
      el.textContent = isPercent ? Math.floor(current) + '%' : Math.floor(current);
    }, 20);
  });
}

// ===== KPI FAB panel =====
(function KPIFab(){
  const STORAGE_KEY = 'kpi_visibility';
  const defaults = {
    '#kpi-aderencia': true,
    '#kpi-total': true,
    '#kpi-nc': true,
    '#kpi-vencidas': true,
    '#kpi-taxa': true,
    '#kpi-resolucao': true,
    '#kpi-escalonamento': true,
    '#kpi-media-dia': true
  };
  const fab   = document.getElementById('kpiFab');
  const panel = document.getElementById('kpiPanel');
  const close = document.getElementById('kpiPanelClose');
  if (!fab || !panel) return;

  let state = {};
  try { state = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(e){ state = {}; }
  state = { ...defaults, ...state };

  function applyState(){
    Object.entries(state).forEach(([sel, visible])=>{
      const el = document.querySelector(sel);
      if (el) el.style.display = visible ? '' : 'none';
    });
    panel.querySelectorAll('.kpi-toggle').forEach(cb=>{
      const t = cb.getAttribute('data-target');
      cb.checked = !!state[t];
    });
  }
  function openPanel(){
    panel.classList.add('open');
    panel.style.pointerEvents = 'auto';
    fab.setAttribute('aria-expanded','true');
  }
  function closePanel(){
    panel.classList.remove('open');
    panel.style.pointerEvents = 'none';
    fab.setAttribute('aria-expanded','false');
  }
  fab.addEventListener('click', (e)=>{
    e.stopPropagation();
    if (panel.classList.contains('open')) closePanel(); else openPanel();
  });
  close?.addEventListener('click', closePanel);
  document.addEventListener('click', (e)=>{
    if (!panel.classList.contains('open')) return;
    if (panel.contains(e.target) || fab.contains(e.target)) return;
    closePanel();
  });
  document.addEventListener('keydown', (e)=>{ if (e.key==='Escape' && panel.classList.contains('open')) closePanel(); });
  panel.addEventListener('change', (e)=>{
    const cb = e.target.closest('.kpi-toggle');
    if (!cb) return;
    const t = cb.getAttribute('data-target');
    state[t] = cb.checked;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    const el = document.querySelector(t);
    if (el) el.style.display = cb.checked ? '' : 'none';
  });

  applyState();
  panel.style.pointerEvents = 'none';
})();

// ===== Chart: Evolução =====
<?php if (!empty($evolucao_diaria)): ?>
const evLabels = [<?php
  $lbl=[]; foreach($evolucao_diaria as $row){ $lbl[]="'".date('d/m', strtotime($row['dia']))."'"; } echo implode(',',$lbl);
?>];
const evDates  = [<?php
  $d=[]; foreach($evolucao_diaria as $row){ $d[]="'".date('Y-m-d', strtotime($row['dia']))."'"; } echo implode(',',$d);
?>];
const evValues = [<?php
  $v=[]; foreach($evolucao_diaria as $row){ $t=(int)$row['total']; $c=(int)$row['conformes']; $v[] = $t>0? round(($c/$t)*100,1) : 0; } echo implode(', ',$v);
?>];

function SMA(arr, period=7){
  if (period<=1) return arr.slice();
  const out=[]; let sum=0;
  for(let i=0;i<arr.length;i++){
    sum+=arr[i];
    if(i>=period) sum-=arr[i-period];
    out.push(i>=period-1? +(sum/period).toFixed(1) : null);
  }
  return out;
}
let currentRange = 'all';
let smaOn = false;
let evolutionChart = null;

function buildEvolutionData(range='all', smooth=false){
  let L=evLabels.slice(), D=evDates.slice(), V=evValues.slice();
  if(range!=='all'){ const n=parseInt(range,10); L=L.slice(-n); D=D.slice(-n); V=V.slice(-n); }
  const dataset = smooth ? SMA(V,7) : V;
  return { labels:L, dates:D, dataset };
}
function renderEvolution(){
  const ctx = document.getElementById('evolutionChart');
  if(!ctx) return;
  const pack = buildEvolutionData(currentRange, smaOn);
  if(evolutionChart) evolutionChart.destroy();
  evolutionChart = new Chart(ctx, {
    type:'line',
    data:{ labels: pack.labels, datasets:[{
      label:'Aderência (%)',
      data: pack.dataset,
      borderColor: getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || '#000',
      backgroundColor:'rgba(0,0,0,0.06)',
      borderWidth:2, fill:true, tension:.3,
      pointRadius:0, pointHoverRadius:4
    }]},
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{intersect:false, mode:'index'} },
      scales:{
        x:{ grid:{color:'rgba(0,0,0,0.06)'}, ticks:{color:'#666', autoSkip:true, maxRotation:0} },
        y:{ grid:{color:'rgba(0,0,0,0.06)'}, ticks:{color:'#666'}, beginAtZero:true, max:100 }
      },
      onClick:(evt, elements)=>{
        if(!elements.length) return;
        const idx = elements[0].index;
        openDayDrill(pack.dates[idx], pack.dataset[idx]);
      }
    }
  });
}
function bindChartControls(){
  document.querySelectorAll('.chart-controls .btn-secondary[data-range]').forEach(btn=>{
    btn.addEventListener('click', ()=>{ currentRange = btn.getAttribute('data-range')||'all'; renderEvolution(); });
  });
  const sma = document.getElementById('smaToggle');
  sma?.addEventListener('change', ()=>{ smaOn = sma.checked; renderEvolution(); });
}
<?php endif; ?>

// ===== Chart: Distribuição =====
function renderCategoryChart(){
  const ctx = document.getElementById('categoryChart'); if(!ctx) return;
  new Chart(ctx, {
    type:'doughnut',
    data:{
      labels:['Simples','Média','Complexa'],
      datasets:[{ data:[<?= (int)$estatisticas['simples'] ?>, <?= (int)$estatisticas['media'] ?>, <?= (int)$estatisticas['complexa'] ?>],
        backgroundColor:['#111827','#9CA3AF','#D1D5DB'], borderWidth:0, hoverOffset:8 }]
    },
    options:{ responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ position:'bottom', labels:{ padding:16, usePointStyle:true } } } }
  });
}

// ===== Drill-Down =====
const modal = document.getElementById('drillModal');
const drillTitle = document.getElementById('drillTitle');
const drillBody = document.getElementById('drillBody');
function closeDrill(){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); drillBody.innerHTML=''; }
modal.addEventListener('click', (e)=>{ if (e.target===modal) closeDrill(); });

function tableFromItems(items){
  if(!items.length) return '<div class="no-data">Sem itens para exibir.</div>';
  let html = '<table class="drill-table"><thead><tr>';
  html += '<th>ID</th><th>Nome</th><th>Descrição</th><th>Resultado</th><th>Responsável</th><th>Classificação</th><th>Identificação</th><th>Prazo</th><th>Escalonamento</th><th>Conclusão</th>';
  html += '</tr></thead><tbody>';
  for(const it of items){
    html += '<tr>';
    html += `<td><strong>${it.id}</strong></td>`;
    html += `<td>${it.nome ?? ''}</td>`;
    html += `<td>${(it.descricao ?? '').toString().slice(0,200)}</td>`;
    html += `<td><span class="badge">${it.resultado ?? ''}</span></td>`;
    html += `<td>${it.responsavel ?? ''}</td>`;
    html += `<td>${it.classificacao ?? ''}</td>`;
    html += `<td>${(it.data_identificacao ?? '').replace('T',' ').slice(0,16)}</td>`;
    html += `<td>${(it.prazo ?? '').replace('T',' ').slice(0,16)}</td>`;
    html += `<td>${(it.data_escalonamento ?? '').replace('T',' ').slice(0,16)}</td>`;
    html += `<td>${(it.data_conclusao ?? '').replace('T',' ').slice(0,16)}</td>`;
    html += '</tr>';
  }
  html += '</tbody></table>';
  return html;
}
async function openDayDrill(dateISO, aderencia){
  try{
    const res = await fetch(`relatoriosNc.php?action=items_by_day&date=${encodeURIComponent(dateISO)}`);
    const json = await res.json();
    if(!json.ok) throw new Error(json.error||'Falha ao carregar itens.');
    const dNum = document.getElementById('evolutionDetailNum');
    const dDesc = document.getElementById('evolutionDetailDesc');
    const detail = document.getElementById('evolutionDetail');
    if(dNum && dDesc && detail){
      dNum.textContent = (aderencia!=null?aderencia:0) + '%';
      dDesc.textContent = `Dia ${dateISO} • ${json.items.length} item(ns)`;
      detail.style.display='';
    }
    drillTitle.textContent = `Itens do dia ${dateISO}`;
    drillBody.innerHTML = tableFromItems(json.items);
    modal.classList.add('show'); modal.setAttribute('aria-hidden','false');
  }catch(e){ alert(e.message); }
}
async function openResponsavelDrill(resp){
  try{
    const res = await fetch(`relatoriosNc.php?action=items_by_responsavel&responsavel=${encodeURIComponent(resp)}`);
    const json = await res.json();
    if(!json.ok) throw new Error(json.error||'Falha ao carregar itens.');
    drillTitle.textContent = `Itens de ${resp}`;
    drillBody.innerHTML = tableFromItems(json.items);
    modal.classList.add('show'); modal.setAttribute('aria-hidden','false');
  }catch(e){ alert(e.message); }
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', ()=>{
  animateNumbers();
  renderCategoryChart();
  <?php if (!empty($evolucao_diaria)): ?>
  bindChartControls();
  renderEvolution();
  <?php endif; ?>
});
</script>
</body>
</html>
