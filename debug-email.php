<?php
session_start();

// Configuração de debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Email - Sistema de Auditoria</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .debug { background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; padding: 15px; font-family: monospace; white-space: pre-wrap; }
        h1, h2 { color: #333; }
        .btn { display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px 0; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Debug do Sistema de Email</h1>";

// 1. Verificar se PHPMailer está disponível
echo "<h2>1️⃣ Verificação do PHPMailer</h2>";

$phpmailer_paths = [
    'phpmailer/src/PHPMailer.php' => 'PHPMailer local',
    'vendor/autoload.php' => 'Composer',
    '../vendor/autoload.php' => 'Composer (pasta pai)'
];

$phpmailer_found = false;
$phpmailer_version = '';

foreach ($phpmailer_paths as $path => $desc) {
    if (file_exists($path)) {
        echo "<div class='status success'>✅ $desc encontrado em: $path</div>";
        
        try {
            if (strpos($path, 'autoload.php') !== false) {
                require_once $path;
            } else {
                require_once dirname($path) . '/Exception.php';
                require_once $path;
                require_once dirname($path) . '/SMTP.php';
            }
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailer_found = true;
                $phpmailer_version = 'v6+';
                echo "<div class='status success'>✅ PHPMailer v6+ carregado</div>";
            } elseif (class_exists('PHPMailer')) {
                $phpmailer_found = true;
                $phpmailer_version = 'v5';
                echo "<div class='status info'>ℹ️ PHPMailer v5 carregado</div>";
            }
            break;
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ Erro ao carregar $desc: " . $e->getMessage() . "</div>";
        }
    }
}

if (!$phpmailer_found) {
    echo "<div class='status error'>❌ PHPMailer não encontrado!</div>";
    echo "<div class='status info'>💡 Instruções para instalar:
    <br>1. Baixe PHPMailer: <a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank'>GitHub Releases</a>
    <br>2. Extraia na pasta 'phpmailer/' dentro do projeto
    <br>3. Ou use Composer: composer require phpmailer/phpmailer</div>";
}

// 2. Verificar extensões PHP
echo "<h2>2️⃣ Extensões PHP Necessárias</h2>";

$extensoes = [
    'openssl' => 'Para conexões SMTP seguras',
    'sockets' => 'Para conexões de rede',
    'curl' => 'Para requisições HTTP',
    'mbstring' => 'Para codificação de texto'
];

foreach ($extensoes as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "<div class='status success'>✅ $ext: $desc</div>";
    } else {
        echo "<div class='status error'>❌ $ext: $desc (FALTANDO)</div>";
    }
}

// 3. Verificar função mail()
echo "<h2>3️⃣ Função mail() do PHP</h2>";
if (function_exists('mail')) {
    echo "<div class='status success'>✅ Função mail() disponível</div>";
} else {
    echo "<div class='status error'>❌ Função mail() não disponível</div>";
}

// 4. Teste de conexão SMTP (se PHPMailer estiver disponível)
if ($phpmailer_found && isset($_GET['testar_smtp'])) {
    echo "<h2>4️⃣ Teste de Conexão SMTP</h2>";
    
    try {
        if ($phpmailer_version === 'v6+') {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        } else {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        }
        
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'checklistes1@gmail.com';
        $mail->Password = 'udtj zrfs cemz dqua';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 2;
        
        // Capturar debug
        ob_start();
        
        $mail->setFrom('checklistes1@gmail.com', 'Teste Sistema');
        $mail->addAddress('teste@exemplo.com');
        $mail->Subject = 'Teste de Conexão';
        $mail->Body = 'Teste de configuração SMTP';
        
        // Tentar conectar sem enviar
        $mail->getSMTPInstance()->connect();
        
        $debug_output = ob_get_clean();
        
        echo "<div class='status success'>✅ Conexão SMTP estabelecida com sucesso!</div>";
        echo "<div class='debug'>Debug SMTP:\n$debug_output</div>";
        
        $mail->getSMTPInstance()->quit();
        
    } catch (Exception $e) {
        $debug_output = ob_get_clean();
        echo "<div class='status error'>❌ Erro na conexão SMTP: " . $e->getMessage() . "</div>";
        echo "<div class='debug'>Debug SMTP:\n$debug_output</div>";
    }
}

// 5. Informações do sistema
echo "<h2>5️⃣ Informações do Sistema</h2>";
echo "<div class='status info'>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Sistema Operacional: " . php_uname() . "<br>";
echo "Servidor Web: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "<br>";
echo "Diretório atual: " . __DIR__ . "<br>";
echo "</div>";

// Botões de ação
echo "<h2>🎯 Ações</h2>";
if ($phpmailer_found) {
    echo "<a href='?testar_smtp=1' class='btn'>🧪 Testar Conexão SMTP</a>";
}
echo "<a href='enviar-email-simples.php' class='btn'>📧 Testar Email Simples</a>";
echo "<a href='nao_conformidades.php' class='btn'>← Voltar</a>";

echo "</div></body></html>";
?>
