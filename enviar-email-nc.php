<?php
session_start();

// Verificar se PHPMailer existe
$phpmailer_exists = file_exists('phpmailer/src/PHPMailer.php');
$error_details = '';

if ($phpmailer_exists) {
    try {
        require_once 'phpmailer/src/Exception.php';
        require_once 'phpmailer/src/PHPMailer.php';
        require_once 'phpmailer/src/SMTP.php';
    } catch (Exception $e) {
        $phpmailer_exists = false;
        $error_details = "Erro ao carregar PHPMailer: " . $e->getMessage();
    }
}

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
            
            // Enviar email se PHPMailer estiver disponível
            if ($phpmailer_exists) {
                $resultado_email = enviarEmailNC($nc, $destinatario, $rqa_responsavel);
                
                if ($resultado_email === true) {
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
                    // Mostrar erro detalhado
                    $erro_detalhado = is_string($resultado_email) ? $resultado_email : "Erro desconhecido ao enviar email";
                    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao enviar email: " . $erro_detalhado));
                }
            } else {
                header("Location: nao_conformidades.php?msg=" . urlencode("❌ PHPMailer não está configurado!"));
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

function enviarEmailNC($nc, $destinatario, $rqa_responsavel = '') {
    // Verificar se a classe PHPMailer existe
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return "PHPMailer não está disponível";
    }
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'checklistes1@gmail.com';
        $mail->Password = 'udtj zrfs cemz dqua';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 2; // Ativar debug para ver erros
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
    
        $mail->setFrom('checklistes1@gmail.com', 'Sistema de Auditoria');
        $mail->addAddress($destinatario);
        
        // Validar email do destinatário
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return "Email do destinatário inválido: $destinatario";
        }
        
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
        
        $assunto = "🚨 ESCALONAMENTO - Não Conformidade #{$nc['id']} - " . $nc['titulo'];
        
        $mensagem = "
        <html>
        <head><meta charset='utf-8'></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                <h2 style='color: #d32f2f; text-align: center; margin-bottom: 30px;'>
                    🚨 ESCALONAMENTO DE NÃO-CONFORMIDADE
                </h2>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                    <h3 style='margin: 0 0 10px 0; color: #333;'>Identificação da NC</h3>
                    <p><strong>ID:</strong> #{$nc['id']}</p>
                    <p><strong>Título:</strong> " . htmlspecialchars($nc['titulo']) . "</p>
                    <p><strong>Status:</strong> " . htmlspecialchars($nc['status']) . "</p>
                </div>
                
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                    <h3 style='margin: 0 0 10px 0; color: #856404;'>Detalhes da Não-Conformidade</h3>
                    <p><strong>Descrição:</strong><br>" . nl2br(htmlspecialchars($nc['descricao'])) . "</p>
                    <p><strong>Responsável:</strong> " . htmlspecialchars($nc['responsavel']) . "</p>
                    <p><strong>Data de Abertura:</strong> {$abertura_formatada}</p>
                    <p><strong>Prazo de Resolução:</strong> {$prazo_formatado}</p>
                </div>";
        
        if (!empty($nc['observacoes'])) {
            $mensagem .= "
                <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                    <h3 style='margin: 0 0 10px 0; color: #1976d2;'>Observações</h3>
                    <p>" . nl2br(htmlspecialchars($nc['observacoes'])) . "</p>
                </div>";
        }
        
        if (!empty($rqa_responsavel)) {
            $mensagem .= "<p><strong>RQA Responsável:</strong> " . htmlspecialchars($rqa_responsavel) . "</p>";
        }
        
        $mensagem .= "
                <div style='background: #ffebee; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;'>
                    <p style='margin: 0; color: #c62828; font-weight: bold;'>
                        ⚠️ Esta não-conformidade foi escalonada e requer ação imediata!
                    </p>
                    <p style='margin: 10px 0 0 0; font-size: 14px; color: #666;'>
                        Este email foi gerado automaticamente pelo Sistema de Auditoria.
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;
        
        // Tentar enviar
        $enviado = $mail->send();
        
        if ($enviado) {
            return true;
        } else {
            return "Falha no envio: " . $mail->ErrorInfo;
        }
        
    } catch (Exception $e) {
        $erro_msg = "Erro PHPMailer: " . $e->getMessage();
        error_log($erro_msg);
        return $erro_msg;
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
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
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
        <?php elseif (!$phpmailer_exists): ?>
            <div class="alert alert-error">
                <strong>Erro:</strong> PHPMailer não está configurado. 
                <br>Para usar esta funcionalidade, é necessário instalar o PHPMailer.
            </div>
            <div class="form-actions">
                <a href="nao_conformidades.php" class="btn-secondary">Voltar</a>
            </div>
        <?php else: ?>
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
                
                <div class="form-actions">
                    <a href="nao_conformidades.php" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">📧 Enviar Email</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
