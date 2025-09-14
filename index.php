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
        
        <div class="main-content fade-in">
            <h1 class="page-title">Ferramenta de Auditoria</h1>
            
            <form class="audit-form">
                <div class="form-group">
                    <label for="checklist-name" class="form-label">Nome do Checklist</label>
                    <input type="text" id="checklist-name" class="form-input" placeholder="Insira o nome do seu checklist">
                </div>
                
                <button type="button" class="btn-primary">Criar Checklist</button>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>