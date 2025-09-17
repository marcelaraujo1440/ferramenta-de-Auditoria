<?php
session_start();

// Verificar se PHPMailer existe
$phpmailer_exists = false;
$phpmailer_paths = [
    'phpmailer/src/PHPMailer.php',
    'vendor/autoload.php',
    '../vendor/autoload.php'
];

foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        try {
            if (strpos($path, 'autoload.php') !== false) {
                require_once $path;
            } else {
                require_once dirname($path) . '/Exception.php';
                require_once $path;
                require_once dirname($path) . '/SMTP.php';
            }
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailer_exists = true;
                break;
            }
        } catch (Exception $e) {
            continue;
        }
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

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Requisição inválida!"));
    exit;
}

// Validar dados recebidos
$id_nc = isset($_POST['id_nc']) ? intval($_POST['id_nc']) : 0;
$destinatario = isset($_POST['destinatario']) ? trim($_POST['destinatario']) : '';
$responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
$rqa = isset($_POST['rqa']) ? trim($_POST['rqa']) : '';
$acao_corretiva = isset($_POST['acao']) ? trim($_POST['acao']) : '';
$prazo_custom = isset($_POST['prazo']) ? trim($_POST['prazo']) : '';

if ($id_nc <= 0 || empty($destinatario)) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ ID da NC e email do destinatário são obrigatórios!"));
    exit;
}

// Validar email
if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Email do destinatário inválido!"));
    exit;
}

// Buscar dados da não-conformidade
if ($conexao_erro) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro de conexão com o banco de dados!"));
    exit;
}

try {
    // Buscar dados da NC na estrutura atual
    $sql = "SELECT * FROM nao_conformidades WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_nc]);
    $nc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nc) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ Não conformidade não encontrada!"));
        exit;
    }
    
    // Usar dados fornecidos ou padrões da NC
    $responsavel_final = !empty($responsavel) ? $responsavel : ($nc['responsavel'] ?? 'Não definido');
    $rqa_final = !empty($rqa) ? $rqa : 'Não definido';
    $acao_final = !empty($acao_corretiva) ? $acao_corretiva : 'A definir';
    
    // Formatação de datas
    $data_criacao = 'Não definida';
    if (!empty($nc['data_abertura'])) {
        try {
            $data_obj = new DateTime($nc['data_abertura']);
            $data_criacao = $data_obj->format('d/m/Y H:i');
        } catch (Exception $e) {
            $data_criacao = $nc['data_abertura'];
        }
    }
    
    $prazo_final = 'Não definido';
    if (!empty($prazo_custom)) {
        // Usar prazo personalizado
        try {
            $prazo_obj = new DateTime($prazo_custom);
            $prazo_final = $prazo_obj->format('d/m/Y H:i');
        } catch (Exception $e) {
            $prazo_final = $prazo_custom;
        }
    } elseif (!empty($nc['prazo_resolucao'])) {
        // Usar prazo da NC
        try {
            $prazo_obj = new DateTime($nc['prazo_resolucao']);
            $prazo_final = $prazo_obj->format('d/m/Y H:i');
        } catch (Exception $e) {
            $prazo_final = $nc['prazo_resolucao'];
        }
    }
    
    // Configurar email
    $remetente = 'checklistes1@gmail.com';
    $assunto = "Solicitação de Resolução de Não Conformidade #{$nc['id']}";
    
    $mensagem = "
    <html>
    <head><meta charset='utf-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
            <h2 style='color: #d32f2f; text-align: center; margin-bottom: 30px;'>
                📋 Solicitação de Resolução de Não Conformidade
            </h2>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <h3 style='margin: 0 0 10px 0; color: #333;'>Identificação da NC</h3>
                <p><strong>🆔 ID:</strong> #{$nc['id']}</p>
                <p><strong>📝 Título:</strong> " . htmlspecialchars($nc['titulo']) . "</p>
                <p><strong>📈 Estado:</strong> " . htmlspecialchars($nc['status']) . "</p>
                <p><strong>📅 Data da Solicitação:</strong> {$data_criacao}</p>
            </div>
            
            <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <h3 style='margin: 0 0 10px 0; color: #856404;'>Detalhes da Não-Conformidade</h3>
                <p><strong>📝 Descrição:</strong><br>" . nl2br(htmlspecialchars($nc['descricao'])) . "</p>
                <p><strong>👤 Responsável:</strong> " . htmlspecialchars($responsavel_final) . "</p>
                <p><strong>📌 RQA Responsável:</strong> " . htmlspecialchars($rqa_final) . "</p>
                <p><strong>⏰ Prazo de Resolução:</strong> {$prazo_final}</p>
            </div>";
    
    if (!empty($nc['observacoes'])) {
        $mensagem .= "
            <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <h3 style='margin: 0 0 10px 0; color: #1976d2;'>Observações</h3>
                <p>" . nl2br(htmlspecialchars($nc['observacoes'])) . "</p>
            </div>";
    }
    
    $mensagem .= "
            <div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <h3 style='margin: 0 0 10px 0; color: #2e7d32;'>Ação Corretiva Indicada</h3>
                <p>" . nl2br(htmlspecialchars($acao_final)) . "</p>
            </div>
            
            <div style='background: #ffebee; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;'>
                <p style='margin: 0; color: #c62828; font-weight: bold;'>
                    ⚠️ Esta solicitação requer ação e resposta!
                </p>
                <p style='margin: 10px 0 0 0; font-size: 14px; color: #666;'>
                    Este email foi gerado automaticamente pelo Sistema de Auditoria.
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    // Enviar email com PHPMailer
    if (!$phpmailer_exists) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ PHPMailer não está configurado!"));
        exit;
    }
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $remetente;
        $mail->Password = 'udtj zrfs cemz dqua';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Desabilitar debug para produção
        
        $mail->setFrom($remetente, 'Sistema de Auditoria');
        $mail->addAddress($destinatario);
        
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;
        
        $enviado = $mail->send();
        
        if ($enviado) {
            // Registrar email enviado
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS emails_enviados (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nc_id INT,
                        destinatario VARCHAR(255),
                        remetente VARCHAR(255),
                        assunto TEXT,
                        tipo_email VARCHAR(50) DEFAULT 'solicitacao',
                        data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                $stmt = $pdo->prepare("INSERT INTO emails_enviados (nc_id, destinatario, remetente, assunto, tipo_email) VALUES (?, ?, ?, ?, 'solicitacao')");
                $stmt->execute([$id_nc, $destinatario, $remetente, $assunto]);
            } catch (PDOException $e) {
                // Continuar mesmo se falhar ao registrar
            }
            
            header("Location: nao_conformidades.php?msg=" . urlencode("✅ Email de solicitação enviado com sucesso!"));
            exit;
        } else {
            header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao enviar email: " . $mail->ErrorInfo));
            exit;
        }
        
    } catch (Exception $e) {
        header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro no envio de email: " . $e->getMessage()));
        exit;
    }
    
} catch (PDOException $e) {
    header("Location: nao_conformidades.php?msg=" . urlencode("❌ Erro ao buscar dados da NC: " . $e->getMessage()));
    exit;
}
?>
