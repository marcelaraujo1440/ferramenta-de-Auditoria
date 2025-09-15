<?php
session_start();

$checklist_nome = isset($_SESSION['checklist_nome']) ? $_SESSION['checklist_nome'] : 'Checklist de Auditoria';
$checklist_id = isset($_SESSION['checklist_id']) ? $_SESSION['checklist_id'] : null;

if (!$checklist_nome || $checklist_nome === 'Checklist de Auditoria') {
    try {
        $host = 'localhost';
        $dbname = 'ferramenta_auditoria';
        $username = 'root'; 
        $password = '';   // ajuste se necessário
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT id, nome FROM checklist ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $ultimo_checklist = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultimo_checklist) {
            $checklist_nome = $ultimo_checklist['nome'];
            $checklist_id = $ultimo_checklist['id'];
            $_SESSION['checklist_nome'] = $checklist_nome;
            $_SESSION['checklist_id'] = $checklist_id;
        }
    } catch (PDOException $e) {
        $checklist_nome = 'Checklist de Auditoria';
    }
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
                    <th>Classificação da NCF</th>
                    <th>Situação da NCF</th>
                    <th>Data de Identificação</th>
                    <th>Prazo</th>
                    <th>Data de Escalonamento</th>
                    <th>Data de Conclusão</th>
                    <th>Observações</th>
                    <th>Ação Corretiva Indicada</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $host = 'localhost';
                    $dbname = 'ferramenta_auditoria';
                    $username = 'root';
                    $password = '';
                    
                    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $sqlItens = "SELECT * FROM checklist ORDER BY id ASC";
                    $stmt = $pdo->prepare($sqlItens);
                    $stmt->execute();
                    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($itens) {
                        foreach ($itens as $item) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($item['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['descricao']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['resultado']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['responsavel']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['classificacao']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['situacao'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($item['data_identificacao']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['prazo']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['data_escalonamento']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['data_conclusao']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['observacoes']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['acao_corretiva_indicada']) . "</td>";
                            echo "<td>
                                    <a href='editar-item.php?id=" . $item['id'] . "' class='btn-secondary'>Editar</a>
                                    <a href='excluir-item.php?id=" . $item['id'] . "' class='btn-secondary' onclick=\"return confirm('Tem certeza que deseja excluir este item?');\">Excluir</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='13'>Nenhum item encontrado.</td></tr>";
                    }

                } catch (PDOException $e) {
                    echo "<tr><td colspan='13'>Erro ao carregar itens: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
