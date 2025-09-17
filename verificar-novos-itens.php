<?php
header('Content-Type: application/json');

// Configurações do banco de dados
$host = 'localhost';
$port = 3307;
$dbname = 'ferramenta_auditoria';
$username = 'root';
$password = 'root';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro de conexão', 'novos_itens' => 0]);
    exit;
}

// Obter timestamp da última verificação
$input = json_decode(file_get_contents('php://input'), true);
$ultima_verificacao = isset($input['ultima_verificacao']) ? intval($input['ultima_verificacao']) : 0;

// Converter para formato MySQL
$data_ultima_verificacao = date('Y-m-d H:i:s', $ultima_verificacao / 1000);

try {
    // Contar itens do checklist criados após a última verificação
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as novos_itens
        FROM checklist c
        LEFT JOIN nao_conformidades nc ON (
            nc.titulo = CONCAT('NC - ', c.nome) OR 
            nc.descricao LIKE CONCAT('%', c.nome, '%')
        )
        WHERE c.resultado = 'Não' 
        AND c.situacao != 'Resolvido'
        AND nc.id IS NULL
        AND c.data_identificacao > ?
    ");
    
    $stmt->execute([$data_ultima_verificacao]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'novos_itens' => intval($resultado['novos_itens']),
        'timestamp' => time() * 1000,
        'ultima_verificacao' => $data_ultima_verificacao
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro na consulta', 'novos_itens' => 0]);
}
?>
