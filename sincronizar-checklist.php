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
    $conexao_erro = true;
    $erro_mensagem = $e->getMessage();
}

if ($conexao_erro) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro de conexão com o banco de dados!"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Requisição inválida!"));
    exit;
}

try {
    // Verificar se é para sincronizar todos os itens
    if (isset($_POST['sincronizar_todos'])) {
        // Buscar todos os itens do checklist que são "Não" e não têm NC
        $stmt = $pdo->prepare("
            SELECT c.id, c.nome, c.descricao, c.responsavel, c.data_identificacao, c.prazo, c.acao_corretiva_indicada, c.classificacao
            FROM checklist c
            LEFT JOIN nao_conformidades nc ON (
                nc.titulo = CONCAT('NC - ', c.nome) OR 
                nc.descricao LIKE CONCAT('%', c.nome, '%')
            )
            WHERE c.resultado = 'Não' 
            AND c.situacao != 'Resolvido'
            AND nc.id IS NULL
        ");
        $stmt->execute();
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $criadas = 0;
        $erros = 0;
        
        foreach ($itens as $item) {
            try {
                criarNCDoItem($pdo, $item);
                $criadas++;
            } catch (Exception $e) {
                $erros++;
            }
        }
        
        if ($criadas > 0) {
            $mensagem = "✅ {$criadas} não-conformidade(s) criada(s) com sucesso!";
            if ($erros > 0) {
                $mensagem .= " ({$erros} erro(s) encontrado(s))";
            }
        } else {
            $mensagem = "❌ Nenhuma não-conformidade foi criada.";
            if ($erros > 0) {
                $mensagem .= " {$erros} erro(s) encontrado(s).";
            }
        }
        
        header("Location: nao_conformidades.php?msg=" . urlencode($mensagem));
        exit;
    }
    
    // Sincronizar um item específico
    if (isset($_POST['item_id'])) {
        $item_id = intval($_POST['item_id']);
        
        if ($item_id <= 0) {
            header("Location: nao_conformidades.php?msg=" . urlencode("❌ ID do item inválido!"));
            exit;
        }
        
        // Buscar o item do checklist
        $stmt = $pdo->prepare("SELECT * FROM checklist WHERE id = ? AND resultado = 'Não'");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            header("Location: nao_conformidades.php?msg=" . urlencode("❌ Item do checklist não encontrado ou não é uma não-conformidade!"));
            exit;
        }
        
        // Verificar se já existe uma NC para este item
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM nao_conformidades 
            WHERE titulo = ? OR descricao LIKE ?
        ");
        $titulo_nc = "NC - " . $item['nome'];
        $descricao_like = "%" . $item['nome'] . "%";
        $stmt->execute([$titulo_nc, $descricao_like]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] > 0) {
            header("Location: nao_conformidades.php?msg=" . urlencode("⚠️ Já existe uma não-conformidade para este item!"));
            exit;
        }
        
        // Criar a não-conformidade
        criarNCDoItem($pdo, $item);
        
        header("Location: nao_conformidades.php?msg=" . urlencode("✅ Não-conformidade criada com sucesso a partir do checklist!"));
        exit;
    }
    
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Parâmetros inválidos!"));
    exit;
    
} catch (PDOException $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro no banco de dados: " . $e->getMessage()));
    exit;
} catch (Exception $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro: " . $e->getMessage()));
    exit;
}

function criarNCDoItem($pdo, $item) {
    // Mapear classificação do checklist
    $classificacao_map = [
        'Simples' => 'Baixa',
        'Média' => 'Média',
        'Complexa' => 'Alta'
    ];
    
    $classificacao = $classificacao_map[$item['classificacao']] ?? 'Média';
    
    // Calcular prazo baseado na classificação
    $prazo_dias = [
        'Baixa' => 7,
        'Média' => 14,
        'Alta' => 21
    ];
    
    $dias = $prazo_dias[$classificacao];
    $prazo_resolucao = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
    
    // Se o item tem prazo específico, usar ele
    if (!empty($item['prazo'])) {
        $prazo_resolucao = $item['prazo'];
    }
    
    // Dados da nova NC
    $titulo = "NC - " . $item['nome'];
    $descricao = "Não-conformidade identificada no checklist: " . $item['descricao'];
    
    if (!empty($item['acao_corretiva_indicada'])) {
        $descricao .= "\n\nAção corretiva indicada: " . $item['acao_corretiva_indicada'];
    }
    
    $observacoes = "Criada automaticamente a partir do item de checklist ID: " . $item['id'];
    $observacoes .= "\nClassificação original do checklist: " . ($item['classificacao'] ?: 'Não definida');
    $observacoes .= "\nPrazo calculado baseado na classificação: {$classificacao} ({$dias} dias)";
    
    $responsavel = $item['responsavel'] ?: 'A definir';
    $status = 'Aberta';
    $data_abertura = $item['data_identificacao'] ?: date('Y-m-d H:i:s');
    
    // Inserir a não-conformidade
    $stmt = $pdo->prepare("
        INSERT INTO nao_conformidades (
            titulo, descricao, responsavel, status, 
            data_abertura, prazo_resolucao, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $titulo,
        $descricao,
        $responsavel,
        $status,
        $data_abertura,
        $prazo_resolucao,
        $observacoes
    ]);
    
    $nc_id = $pdo->lastInsertId();
    
    // Opcionalmente, atualizar o item do checklist para indicar que foi processado
    // $stmt = $pdo->prepare("UPDATE checklist SET observacoes = CONCAT(COALESCE(observacoes, ''), '\nNC criada: #', ?) WHERE id = ?");
    // $stmt->execute([$nc_id, $item['id']]);
    
    return $nc_id;
}
?>
