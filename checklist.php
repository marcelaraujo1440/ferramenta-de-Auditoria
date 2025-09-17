<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$port = 3307; // Porta correta
$dbname = 'ferramenta_auditoria';
$username = 'root'; 
$password = '';

$checklist_nome = isset($_SESSION['checklist_nome']) ? $_SESSION['checklist_nome'] : 'Checklist de Auditoria';
$checklist_id = isset($_SESSION['checklist_id']) ? $_SESSION['checklist_id'] : null;

// Conectar ao banco de dados
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

// Verificar se há um checklist selecionado
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($checklist_nome); ?> - Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="./styles/style.css">
    <style>
        /* Estilos para mensagens de erro */
        .error-container {
            background-color: #ffebee;
            border: 1px solid #e57373;
            padding: 15px;
            margin: 20px;
            border-radius: 4px;
            color: #c62828;
        }
        .error-container h3 {
            margin-top: 0;
            color: #c62828;
        }
        .error-container ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .success-message {
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            color: #2e7d32;
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

    <?php if ($conexao_erro): ?>
        <div class="error-container">
            <h3>Erro de Conexão</h3>
            <p>Não foi possível conectar ao banco de dados.</p>
        </div>
    <?php else: ?>
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
                        // Buscar apenas os itens do checklist específico selecionado
                        if ($checklist_nome && $checklist_nome !== 'Checklist de Auditoria') {
                            $sqlItens = "SELECT * FROM checklist WHERE nome = ? AND (descricao IS NOT NULL AND descricao != '') ORDER BY id ASC";
                            $stmt = $pdo->prepare($sqlItens);
                            $stmt->execute([$checklist_nome]);
                        } else {
                            // Se não há checklist selecionado, não mostrar nenhum item
                            $stmt = $pdo->prepare("SELECT * FROM checklist WHERE 1=0");
                            $stmt->execute();
                        }
                        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($itens) {
                            $itens_exibidos = 0;
                            foreach ($itens as $item) {
                                // Verificar se tem apenas ID e nome preenchidos (esconder essas linhas)
                                $campos_preenchidos = 0;
                                if (!empty($item['id'])) $campos_preenchidos++;
                                if (!empty($item['nome'])) $campos_preenchidos++;
                                if (!empty($item['descricao'])) $campos_preenchidos++;
                                if (!empty($item['resultado'])) $campos_preenchidos++;
                                if (!empty($item['responsavel'])) $campos_preenchidos++;
                                if (!empty($item['classificacao'])) $campos_preenchidos++;
                                if (!empty($item['situacao'])) $campos_preenchidos++;
                                if (!empty($item['data_identificacao'])) $campos_preenchidos++;
                                if (!empty($item['prazo'])) $campos_preenchidos++;
                                if (!empty($item['data_escalonamento'])) $campos_preenchidos++;
                                if (!empty($item['data_conclusao'])) $campos_preenchidos++;
                                if (!empty($item['observacoes'])) $campos_preenchidos++;
                                if (!empty($item['acao_corretiva_indicada'])) $campos_preenchidos++;
                                
                                // Se tem mais que só ID e nome, mostrar a linha
                                if ($campos_preenchidos > 2) {
                                    $itens_exibidos++;
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($item['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['descricao'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['resultado'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['responsavel'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['classificacao'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['situacao'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['data_identificacao'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['prazo'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['data_escalonamento'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['data_conclusao'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['observacoes'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($item['acao_corretiva_indicada'] ?? '') . "</td>";
                                    echo "<td>
                                            <a href='editar-item.php?id=" . $item['id'] . "' class='btn-secondary'>Editar</a>
                                            <a href='excluir-item.php?id=" . $item['id'] . "' class='btn-secondary' onclick=\"return confirm('Tem certeza que deseja excluir este item?');\">Excluir</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            }
                            
                            // Se não há itens para exibir após o filtro
                            if ($itens_exibidos == 0) {
                                echo "<tr><td colspan='13' style='text-align: center; padding: 20px; color: #666;'>Nenhum item completo encontrado. <a href='adicionar-item.php'>Clique aqui para adicionar um item</a>.</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='13' style='text-align: center; padding: 20px; color: #666;'>Nenhum item encontrado. <a href='adicionar-item.php'>Clique aqui para adicionar o primeiro item</a>.</td></tr>";
                        }

                    } catch (PDOException $e) {
                        echo "<tr><td colspan='13' style='color: red; text-align: center; padding: 20px;'>Erro ao carregar itens: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <script src="js/app.js"></script>
    
    <script>
        // Script para melhorar a experiência do usuário
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar efeito hover nas linhas da tabela
            const rows = document.querySelectorAll('#checklist-table tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f5f5f5';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            // Melhorar confirmação de exclusão
            const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const itemId = this.href.split('id=')[1];
                    if (confirm(`Tem certeza que deseja excluir o item #${itemId}?\n\nEsta ação não pode ser desfeita.`)) {
                        window.location.href = this.href;
                    }
                });
                // Remove o onclick inline para usar o event listener
                link.removeAttribute('onclick');
            });
        });
    </script>
</body>
</html>