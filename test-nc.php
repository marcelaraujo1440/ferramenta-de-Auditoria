<?php
// Teste simples para verificar NCs no banco
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Contar total de NCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM nao_conformidades");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de NCs: " . $count['total'] . "<br><br>";
    
    // Mostrar todas as NCs
    $stmt = $pdo->query("SELECT id, titulo, responsavel, status, data_abertura FROM nao_conformidades ORDER BY id");
    $ncs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Lista de NCs:</h3>";
    foreach ($ncs as $nc) {
        echo "ID: {$nc['id']} - Título: {$nc['titulo']} - Responsável: {$nc['responsavel']} - Status: {$nc['status']} - Data: {$nc['data_abertura']}<br>";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
