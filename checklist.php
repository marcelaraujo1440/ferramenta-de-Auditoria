<?php
session_start();

$checklist_nome = isset($_SESSION['checklist_nome']) ? $_SESSION['checklist_nome'] : 'Checklist de Auditoria';
$checklist_id = isset($_SESSION['checklist_id']) ? $_SESSION['checklist_id'] : null;

try {
    $host = 'localhost';
    $dbname = 'ferramenta_auditoria';
    $username = 'root'; 
    $password = '';   // ajuste se necessário
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$checklist_nome || $checklist_nome === 'Checklist de Auditoria') {
        $sql = "SELECT id, nome FROM checklist ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $ultimo_checklist = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultimo_checklist) {
            $checklist_nome = $ultimo_checklist['nome'] ?? 'Checklist de Auditoria';
            $checklist_id = $ultimo_checklist['id'];
            $_SESSION['checklist_nome'] = $checklist_nome;
            $_SESSION['checklist_id'] = $checklist_id;
        }
    }

    $sqlItens = "SELECT * FROM checklist ORDER BY id ASC";
    $stmtItens = $pdo->prepare($sqlItens);
    $stmtItens->execute();
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $checklist_nome = 'Checklist de Auditoria';
    $itens = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($checklist_nome); ?> - Ferramenta de Auditoria</title>
        <link rel="stylesheet" href="./styles/style.css">
    </head>
    <body>
        <div class="container">
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                    <li><a href="pages/relatorios.php" class="nav-link">Relatórios</a></li>
                    <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
                </ul>
            </nav>
        </div>
        <div class="page-header">
            <h2 class="page-title"><?php echo htmlspecialchars($checklist_nome); ?></h2>
            <?php if ($checklist_id): ?>
                <p class="checklist-info">ID do Checklist: #<?php echo $checklist_id; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="add-item-section">
            <a href="adicionar-item.php" class="btn-primary">Adicionar Item</a>
        </div>

        <div class="table-container">
            <table id="checklist-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Resultado</th>
                        <th>Responsável</th>
                        <th>Classificação</th>
                        <th>Data de Identificação</th>
                        <th>Prazo</th>
                        <th>Data de Escalonamento</th>
                        <th>Data de Conclusão</th>
                        <th>Observações</th>
                        <th>Ação Corretiva Indicada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($itens)): ?>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['id']); ?></td>
                                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($item['resultado']); ?></td>
                                <td><?php echo htmlspecialchars($item['responsavel']); ?></td>
                                <td><?php echo htmlspecialchars($item['classificacao']); ?></td>
                                <td><?php echo $item['data_identificacao'] ? date('d/m/Y H:i', strtotime($item['data_identificacao'])) : '-'; ?></td>
                                <td><?php echo $item['prazo'] ? date('d/m/Y H:i', strtotime($item['prazo'])) : '-'; ?></td>
                                <td><?php echo $item['data_escalonamento'] ? date('d/m/Y H:i', strtotime($item['data_escalonamento'])) : '-'; ?></td>
                                <td><?php echo $item['data_conclusao'] ? date('d/m/Y H:i', strtotime($item['data_conclusao'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($item['observacoes']); ?></td>
                                <td><?php echo htmlspecialchars($item['acao_corretiva_indicada']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">Nenhum item encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script src="js/app.js"></script>
    </body>
</html>
