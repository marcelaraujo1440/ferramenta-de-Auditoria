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

// Processar requisição POST (envio manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nc_id = intval($_POST['nc_id'] ?? 0);
    $destinatario = trim($_POST['destinatario'] ?? '');
    $rqa_responsavel = trim($_POST['rqa_responsavel'] ?? '');
    
    if ($nc_id <= 0 || empty($destinatario)) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ Dados inválidos para envio de email!"));
        exit;
    }
    
    // Buscar dados da NC
    if (!$conexao_erro) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM nao_conformidades WHERE id = ?");
            $stmt->execute([$nc_id]);
            $nc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$nc) {
                header("Location: nao_conformidades.php?msg=" . urlencode("❌ Não-conformidade não encontrada!"));
                exit;
            }
            
            // Tentar enviar email usando função nativa do PHP
            $sucesso_email = enviarEmailSimples($nc, $destinatario, $rqa_responsavel);
            
            if ($sucesso_email) {
                // Marcar como enviado
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS emails_enviados (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nc_id INT,
                            destinatario VARCHAR(255),
                            data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $stmt = $pdo->prepare("INSERT INTO emails_enviados (nc_id, destinatario) VALUES (?, ?)");
                    $stmt->execute([$nc_id, $destinatario]);
                } catch (PDOException $e) {
                    // Continuar mesmo se falhar ao registrar o envio
                }
                
                header("Location: nao_conformidades.php?msg=" . urlencode("✅ Email de escalonamento enviado com sucesso!"));
            } else {
                header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao enviar email! Verifique a configuração do servidor de email."));
            }
            exit;
            
        } catch (PDOException $e) {
            header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao buscar dados da NC!"));
            exit;
        }
    }
}

// Buscar NC pelo ID (GET)
$nc_id = intval($_GET['nc_id'] ?? 0);
$nc = null;

if ($nc_id > 0 && !$conexao_erro) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM nao_conformidades WHERE id = ?");
        $stmt->execute([$nc_id]);
        $nc = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_mensagem = $e->getMessage();
    }
}

function enviarEmailSimples($nc, $destinatario, $rqa_responsavel = '') {
    // Formatar datas
    $prazo_formatado = 'Não definido';
    if (!empty($nc['prazo_resolucao'])) {
        try {
            $data_prazo = new DateTime($nc['prazo_resolucao']);
            $prazo_formatado = $data_prazo->format('d/m/Y H:i');
        } catch (Exception $e) {
            $prazo_formatado = $nc['prazo_resolucao'];
        }
    }
    
    $abertura_formatada = 'Não definida';
    if (!empty($nc['data_abertura'])) {
        try {
            $data_abertura = new DateTime($nc['data_abertura']);
            $abertura_formatada = $data_abertura->format('d/m/Y H:i');
        } catch (Exception $e) {
            $abertura_formatada = $nc['data_abertura'];
        }
    }
    
    // Assunto do email
    $assunto = "ESCALONAMENTO - Não Conformidade #{$nc['id']} - " . $nc['titulo'];
    
    // Corpo do email (versão texto simples)
    $mensagem = "ESCALONAMENTO DE NÃO-CONFORMIDADE\n";
    $mensagem .= "=====================================\n\n";
    $mensagem .= "ID: #{$nc['id']}\n";
    $mensagem .= "Título: " . $nc['titulo'] . "\n";
    $mensagem .= "Status: " . $nc['status'] . "\n\n";
    $mensagem .= "DETALHES DA NÃO-CONFORMIDADE:\n";
    $mensagem .= "Descrição: " . $nc['descricao'] . "\n";
    $mensagem .= "Responsável: " . $nc['responsavel'] . "\n";
    $mensagem .= "Data de Abertura: " . $abertura_formatada . "\n";
    $mensagem .= "Prazo de Resolução: " . $prazo_formatado . "\n\n";
    
    if (!empty($nc['observacoes'])) {
        $mensagem .= "Observações: " . $nc['observacoes'] . "\n\n";
    }
    
    if (!empty($rqa_responsavel)) {
        $mensagem .= "RQA Responsável: " . $rqa_responsavel . "\n\n";
    }
    
    $mensagem .= "⚠️ Esta não-conformidade foi escalonada e requer ação imediata!\n\n";
    $mensagem .= "Este email foi gerado automaticamente pelo Sistema de Auditoria.";
    
    // Headers do email
    $headers = array();
    $headers[] = 'From: Sistema de Auditoria <noreply@auditoria.local>';
    $headers[] = 'Reply-To: noreply@auditoria.local';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    
    // Tentar enviar o email
    try {
        return mail($destinatario, $assunto, $mensagem, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Erro no envio de email: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Email - Não-Conformidade</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/add-item.css">
    <style>
        .email-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .nc-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .nc-info h3 {
            margin: 0 0 15px 0;
            color: #dc3545;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .email-method-info {
            background: #e2e3e5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .email-method-info h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
    </style>
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
    </div>

    <div class="email-container">
        <h1 style="text-align: center; color: #dc3545; margin-bottom: 30px;">
            📧 Enviar Email de Escalonamento
        </h1>
        
        <?php if ($conexao_erro): ?>
            <div class="alert alert-error">
                <strong>Erro de Conexão:</strong> <?= htmlspecialchars($erro_mensagem) ?>
            </div>
            <div class="form-actions">
                <a href="nao_conformidades.php" class="btn-secondary">Voltar</a>
            </div>
        <?php elseif (!$nc): ?>
            <div class="alert alert-error">
                <strong>Erro:</strong> Não-conformidade não encontrada ou ID inválido.
            </div>
            <div class="form-actions">
                <a href="nao_conformidades.php" class="btn-secondary">Voltar</a>
            </div>
        <?php else: ?>
            
            <!-- Informação sobre método de envio -->
            <div class="email-method-info">
                <h4>📋 Método de Envio</h4>
                <p>Este sistema usa a função nativa do PHP para envio de emails. Certifique-se de que o servidor está configurado para envio de emails.</p>
                <p><strong>Formato:</strong> Texto simples</p>
            </div>
            
            <!-- Informações da NC -->
            <div class="nc-info">
                <h3>Não-Conformidade #<?= htmlspecialchars($nc['id']) ?></h3>
                <p><strong>Título:</strong> <?= htmlspecialchars($nc['titulo']) ?></p>
                <p><strong>Responsável:</strong> <?= htmlspecialchars($nc['responsavel']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($nc['status']) ?></p>
                <p><strong>Prazo:</strong> <?= date('d/m/Y H:i', strtotime($nc['prazo_resolucao'])) ?></p>
            </div>
            
            <!-- Formulário de envio -->
            <form method="POST">
                <input type="hidden" name="nc_id" value="<?= htmlspecialchars($nc['id']) ?>">
                
                <div class="form-group">
                    <label for="destinatario">Email do Destinatário *</label>
                    <input type="email" id="destinatario" name="destinatario" required 
                           placeholder="email@exemplo.com">
                </div>
                
                <div class="form-group">
                    <label for="rqa_responsavel">RQA Responsável</label>
                    <input type="text" id="rqa_responsavel" name="rqa_responsavel" 
                           placeholder="Nome do RQA responsável (opcional)">
                </div>
                
                <div class="alert alert-info">
                    <strong>📋 Conteúdo do Email:</strong><br>
                    O email incluirá todos os detalhes da não-conformidade: título, descrição, responsável, prazos e observações.
                </div>
                
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">📧 Enviar Email</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
