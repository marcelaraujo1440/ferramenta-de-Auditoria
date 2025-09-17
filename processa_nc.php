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

// Conectar ao banco de dados
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro de conexão: " . $e->getMessage()));
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Método de requisição inválido!"));
    exit;
}

// Capturar e validar dados do formulário
$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    // Criar nova NC
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $data_abertura = $_POST['data_abertura'] ?? '';
    $prazo_resolucao = $_POST['prazo_resolucao'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Validações
    $erros = [];
    
    if (empty($titulo)) {
        $erros[] = "Título é obrigatório";
    }
    
    if (empty($descricao)) {
        $erros[] = "Descrição é obrigatória";
    }
    
    if (empty($responsavel)) {
        $erros[] = "Responsável é obrigatório";
    }
    
    if (empty($data_abertura)) {
        $erros[] = "Data de abertura é obrigatória";
    }
    
    if (empty($prazo_resolucao)) {
        $erros[] = "Prazo de resolução é obrigatório";
    }
    
    // Validar datas
    if (!empty($data_abertura) && !empty($prazo_resolucao)) {
        try {
            $dt_abertura = new DateTime($data_abertura);
            $dt_prazo = new DateTime($prazo_resolucao);
            
            if ($dt_prazo <= $dt_abertura) {
                $erros[] = "Prazo de resolução deve ser posterior à data de abertura";
            }
        } catch (Exception $e) {
            $erros[] = "Formato de data inválido";
        }
    }
    
    if (!empty($erros)) {
        $mensagem_erro = "❌ Erros encontrados: " . implode(", ", $erros);
        header("Location: nao_conformidades.php?msg=" . urlencode($mensagem_erro));
        exit;
    }
    
    // Inserir no banco de dados
    try {
        $sql = "
            INSERT INTO nao_conformidades 
            (titulo, descricao, responsavel, data_abertura, prazo_resolucao, observacoes, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Aberta')
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $titulo,
            $descricao,
            $responsavel,
            $data_abertura,
            $prazo_resolucao,
            $observacoes
        ]);
        
        $nc_id = $pdo->lastInsertId();
        header("Location: nao_conformidades.php?msg=" . urlencode("✅ Não-conformidade #$nc_id registrada com sucesso!"));
        exit;
        
    } catch (PDOException $e) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao registrar NC: " . $e->getMessage()));
        exit;
    }
    
} elseif ($action === 'update') {
    // Atualizar NC existente
    $id = intval($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $data_abertura = $_POST['data_abertura'] ?? '';
    $prazo_resolucao = $_POST['prazo_resolucao'] ?? '';
    $status = $_POST['status'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    if ($id <= 0) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ ID da NC inválido!"));
        exit;
    }
    
    // Validações
    $erros = [];
    
    if (empty($titulo)) {
        $erros[] = "Título é obrigatório";
    }
    
    if (empty($descricao)) {
        $erros[] = "Descrição é obrigatória";
    }
    
    if (empty($responsavel)) {
        $erros[] = "Responsável é obrigatório";
    }
    
    if (empty($data_abertura)) {
        $erros[] = "Data de abertura é obrigatória";
    }
    
    if (empty($prazo_resolucao)) {
        $erros[] = "Prazo de resolução é obrigatório";
    }
    
    if (empty($status)) {
        $erros[] = "Status é obrigatório";
    }
    
    if (!in_array($status, ['Aberta', 'Em andamento', 'Resolvida', 'Escalonada'])) {
        $erros[] = "Status inválido";
    }
    
    if (!empty($erros)) {
        $mensagem_erro = "❌ Erros encontrados: " . implode(", ", $erros);
        header("Location: editar_nc.php?id=$id&msg=" . urlencode($mensagem_erro));
        exit;
    }
    
    // Atualizar no banco de dados
    try {
        $sql = "
            UPDATE nao_conformidades 
            SET titulo = ?, descricao = ?, responsavel = ?, data_abertura = ?, 
                prazo_resolucao = ?, status = ?, observacoes = ?
            WHERE id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $titulo,
            $descricao,
            $responsavel,
            $data_abertura,
            $prazo_resolucao,
            $status,
            $observacoes,
            $id
        ]);
        
        header("Location: nao_conformidades.php?msg=" . urlencode("✅ Não-conformidade #$id atualizada com sucesso!"));
        exit;
        
    } catch (PDOException $e) {
        header("Location: editar_nc.php?id=$id&msg=" . urlencode("❌ Erro ao atualizar NC: " . $e->getMessage()));
        exit;
    }
    
} else {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Ação inválida!"));
    exit;
}
?>
