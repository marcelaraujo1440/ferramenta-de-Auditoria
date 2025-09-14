<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ferramenta de Auditoria</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li><a href="checklist.php" class="nav-link">Checklist</a></li>
                    <li><a href="pages/relatorios.php" class="nav-link">Relatórios</a></li>
                </ul>
            </nav>
        </div>
        <div class="page-header">
            <h2 class="page-title">Checklist de Auditoria</h2>
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
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script src="js/app.js"></script>
    </body>
</html>