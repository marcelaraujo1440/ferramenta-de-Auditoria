<?php
$message = '';
$success = false;

$host = 'localhost';
$dbname = 'ferramenta_auditoria';
$username = 'root'; 
$password = 'root';   //se necessário, ajuste a senha ou remova-a


if ($_POST && isset($_POST['checklist-name']) && !empty(trim($_POST['checklist-name']))) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $nome = trim($_POST['checklist-name']);

        $sql = "INSERT INTO checklist (nome) VALUES (:nome)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([':nome' => $nome]);
        
        if ($result) {
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
                <li><a href="pages/checklist.php" class="nav-link">Checklist</a></li>
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
            <h2>✓ Concluído!</h2>
            <p><?php echo $message; ?></p>
            <button class="modal-close" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>