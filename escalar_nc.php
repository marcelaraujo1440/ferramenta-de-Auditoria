<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

$pdo = null;
$conexao_erro = false;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro de conexão: " . $e->getMessage()));
    exit;
}

// Buscar NC pelo ID
$nc_id = intval($_GET['id'] ?? 0);

if ($nc_id <= 0) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ ID da NC inválido!"));
    exit;
}

try {
    // Verificar se a NC existe e não está já escalonada
    $stmt = $pdo->prepare("SELECT * FROM nao_conformidades WHERE id = ?");
    $stmt->execute([$nc_id]);
    $nc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nc) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ Não-conformidade não encontrada!"));
        exit;
    }
    
    if ($nc['status'] === 'Escalonada') {
        header("Location: nao_conformidades.php?msg=" . urlencode("⚠️ Esta NC já foi escalonada!"));
        exit;
    }
    
    if ($nc['status'] === 'Resolvida') {
        header("Location: nao_conformidades.php?msg=" . urlencode("⚠️ Esta NC já foi resolvida!"));
        exit;
    }
    
    // Atualizar status para Escalonada
    $stmt = $pdo->prepare("UPDATE nao_conformidades SET status = 'Escalonada' WHERE id = ?");
    $stmt->execute([$nc_id]);
    
    // Redirecionar para envio de email
    header("Location: enviar-email-nc.php?nc_id=" . $nc_id);
    exit;
    
} catch (PDOException $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao escalonar NC: " . $e->getMessage()));
    exit;
}
?>
