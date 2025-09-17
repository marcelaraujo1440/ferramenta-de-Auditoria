<?php
session_start();

$message = '';
$success = false;
$checklist_name = '';

$host = 'localhost';
$port = '3307';
$dbname = 'ferramenta_auditoria';
$username = 'root'; 
$password = 'root';

if ($_POST && isset($_POST['checklist-name']) && !empty(trim($_POST['checklist-name']))) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $nome = trim($_POST['checklist-name']);

        $sql = "INSERT INTO checklist (nome) VALUES (:nome)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([':nome' => $nome]);
        
        if ($result) {
            $_SESSION['checklist_nome'] = $nome;
            $_SESSION['checklist_id'] = $pdo->lastInsertId();
            
            // Redirecionar com parâmetros de sucesso para mostrar o modal
            header("Location: index.php?success=true&name=" . urlencode($nome));
            exit;
        }
    } catch (PDOException $e) {
        $message = "Erro ao criar checklist: " . $e->getMessage();
    }
}

// Verificar se é uma requisição de sucesso
$show_modal = false;
if (isset($_GET['success']) && $_GET['success'] === 'true' && isset($_GET['name'])) {
    $show_modal = true;
    $checklist_name = urldecode($_GET['name']);
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramenta de Auditoria</title>
    <link rel="stylesheet" href="./styles/style.css">
    <link rel="stylesheet" href="./styles/modal.css">

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
        
        <div class="main-content fade-in">
            <h1 class="page-title">Ferramenta de Auditoria</h1>
            
            <?php if (!empty($message) && !$success): ?>
            <div class="error-message">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <form class="audit-form" method="POST" id="checklist-form-main">
                <div class="form-group">
                    <label for="checklist-name" class="form-label">Nome do Checklist</label>
                    <input type="text" id="checklist-name" name="checklist-name" class="form-input" placeholder="Insira o nome do seu checklist" required>
                </div>
                
                <button type="submit" class="btn-primary">Criar Checklist</button>
            </form>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="successModal" class="modal <?= $show_modal ? 'show' : '' ?>">
        <div class="modal-content">
            <div class="modal-icon">✓</div>
            <h2 class="modal-title">Checklist Criado!</h2>
            <p class="modal-message">Seu checklist foi criado com sucesso:</p>
            <div class="modal-checklist-name" id="modal-checklist-name">
                <?= htmlspecialchars($checklist_name) ?>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="closeModal()">Fechar</button>
                <a href="checklist.php" class="btn-modal-primary">Ver Checklist</a>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/modal.js"></script>
</body>
</html>