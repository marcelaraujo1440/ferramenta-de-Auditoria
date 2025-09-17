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

// Buscar NC pelo ID
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

// Verificar se PHPMailer está disponível
$phpmailer_disponivel = false;
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $phpmailer_disponivel = true;
} elseif (file_exists('vendor/autoload.php')) {
    // Tentar carregar via Composer
    require_once 'vendor/autoload.php';
    $phpmailer_disponivel = class_exists('PHPMailer\PHPMailer\PHPMailer');
} elseif (file_exists('phpmailer/PHPMailer.php')) {
    // Tentar carregar manualmente
    try {
        require_once 'phpmailer/PHPMailer.php';
        require_once 'phpmailer/SMTP.php';
        require_once 'phpmailer/Exception.php';
        $phpmailer_disponivel = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } catch (Exception $e) {
        $phpmailer_disponivel = false;
    }
}

// Verificar se a função mail() está disponível
$mail_nativo_disponivel = function_exists('mail');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Método de Envio - Email</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/add-item.css">
    <style>
        .email-container {
            max-width: 700px;
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
            margin-bottom: 30px;
            border-left: 4px solid #dc3545;
        }
        
        .method-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .method-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .method-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.1);
        }
        
        .method-card.available {
            background: #f8f9fa;
            border-color: #28a745;
        }
        
        .method-card.unavailable {
            background: #fff5f5;
            border-color: #dc3545;
            opacity: 0.7;
        }
        
        .method-card h3 {
            margin: 0 0 15px 0;
            font-size: 1.2rem;
        }
        
        .method-card.available h3 {
            color: #28a745;
        }
        
        .method-card.unavailable h3 {
            color: #dc3545;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .method-features {
            text-align: left;
            margin: 15px 0;
        }
        
        .method-features li {
            margin: 5px 0;
            font-size: 0.9rem;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .method-cards {
                grid-template-columns: 1fr;
            }
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
            📧 Escolher Método de Envio de Email
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
            
            <!-- Informações da NC -->
            <div class="nc-info">
                <h3>📋 Não-Conformidade #<?= htmlspecialchars($nc['id']) ?></h3>
                <p><strong>Título:</strong> <?= htmlspecialchars($nc['titulo']) ?></p>
                <p><strong>Responsável:</strong> <?= htmlspecialchars($nc['responsavel']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($nc['status']) ?></p>
            </div>
            
            <?php if (!$phpmailer_disponivel && !$mail_nativo_disponivel): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Erro:</strong> Nenhum método de envio de email está disponível no servidor!
                </div>
            <?php else: ?>
                <p style="text-align: center; margin-bottom: 30px; color: #666;">
                    Escolha o método de envio de email que deseja usar:
                </p>
            <?php endif; ?>
            
            <div class="method-cards">
                <!-- Método PHPMailer -->
                <div class="method-card <?= $phpmailer_disponivel ? 'available' : 'unavailable' ?>">
                    <span class="status-badge <?= $phpmailer_disponivel ? 'badge-available' : 'badge-unavailable' ?>">
                        <?= $phpmailer_disponivel ? '✅ Disponível' : '❌ Indisponível' ?>
                    </span>
                    
                    <h3>📨 PHPMailer (SMTP)</h3>
                    <p>Envio profissional via SMTP</p>
                    
                    <ul class="method-features">
                        <li>✅ Email formatado em HTML</li>
                        <li>✅ Autenticação SMTP segura</li>
                        <li>✅ Suporte a Gmail, Outlook, etc.</li>
                        <li>✅ Controle total de configuração</li>
                        <li>✅ Melhor entregabilidade</li>
                    </ul>
                    
                    <?php if ($phpmailer_disponivel): ?>
                        <a href="enviar-email-nc.php?nc_id=<?= $nc['id'] ?>" class="btn-primary" style="margin-top: 15px;">
                            Usar PHPMailer
                        </a>
                    <?php else: ?>
                        <p style="color: #dc3545; font-size: 0.9rem; margin-top: 15px;">
                            Biblioteca PHPMailer não instalada
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Método PHP Nativo -->
                <div class="method-card <?= $mail_nativo_disponivel ? 'available' : 'unavailable' ?>">
                    <span class="status-badge <?= $mail_nativo_disponivel ? 'badge-available' : 'badge-unavailable' ?>">
                        <?= $mail_nativo_disponivel ? '✅ Disponível' : '❌ Indisponível' ?>
                    </span>
                    
                    <h3>📤 PHP Nativo</h3>
                    <p>Função mail() do PHP</p>
                    
                    <ul class="method-features">
                        <li>✅ Não requer bibliotecas externas</li>
                        <li>✅ Configuração simples</li>
                        <li>⚠️ Email em texto simples</li>
                        <li>⚠️ Depende do servidor</li>
                        <li>⚠️ Pode ir para spam</li>
                    </ul>
                    
                    <?php if ($mail_nativo_disponivel): ?>
                        <a href="enviar-email-simples.php?nc_id=<?= $nc['id'] ?>" class="btn-secondary" style="margin-top: 15px;">
                            Usar PHP Nativo
                        </a>
                    <?php else: ?>
                        <p style="color: #dc3545; font-size: 0.9rem; margin-top: 15px;">
                            Função mail() desabilitada
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($phpmailer_disponivel || $mail_nativo_disponivel): ?>
                <div class="alert alert-warning">
                    <strong>💡 Recomendação:</strong> 
                    <?php if ($phpmailer_disponivel): ?>
                        Use o PHPMailer para melhor confiabilidade e formatação profissional.
                    <?php else: ?>
                        Instale o PHPMailer para uma experiência melhor de envio de emails.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <div class="form-actions">
            <a href="nao_conformidades.php" class="btn-secondary">← Voltar às Não-Conformidades</a>
        </div>
    </div>
</body>
</html>
