<?php
session_start();

$message = '';
$success = false;

// Configurações do banco
$host = 'localhost';
$port = '3307';
$dbname = 'ferramenta_auditoria';
$username = 'root'; 
$password = '';

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
            
            $success = true;
            $message = "Checklist '$nome' criado com sucesso!";
        }
    } catch (PDOException $e) {
        $message = "Erro ao criar checklist: " . $e->getMessage();
    }
}
?>

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
                <li><a href="pages/envio_comunicacao.php" class="nav-link">Envio e Comunicação</a></li>
            </ul>
        </nav>
        
        <div class="main-content fade-in">
            <h1 class="page-title">Ferramenta de Auditoria</h1>
            
            <form class="audit-form" method="POST">
                <div class="form-group">
                    <label for="checklist-name" class="form-label">Nome do Checklist</label>
                    <input type="text" id="checklist-name" name="checklist-name" class="form-input" placeholder="Insira o nome do seu checklist" required>
                </div>
                
                <button type="submit" class="btn-primary">Criar Checklist</button>
            </form>
        </div>
    </div>

    <div id="successModal" class="modal <?php echo $success ? 'show' : ''; ?>">
        <div class="modal-content">
            <h2><?php echo $success ? '✓ Concluído!' : '❌ Erro'; ?></h2>
            <p><?php echo $message; ?></p>
            <?php if ($success): ?>
                <a href="checklist.php" class="modal-close btn-primary">Ver Checklist</a>
            <?php else: ?>
                <button class="modal-close" onclick="closeModal()">OK</button>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function closeModal() {
            document.getElementById('successModal').classList.remove('show');
        }
    </script>
</body>
</html>