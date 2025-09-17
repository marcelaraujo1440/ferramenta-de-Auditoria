<?php
// Teste simples de envio de email
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste de Envio de Email</h2>";

// Verificar se PHPMailer existe
$phpmailer_paths = [
    'phpmailer/src/PHPMailer.php',
    'vendor/autoload.php',
    '../phpmailer/src/PHPMailer.php'
];

$phpmailer_loaded = false;
$path_usado = '';

foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Encontrado PHPMailer em: $path<br>";
        $path_usado = $path;
        
        if ($path === 'vendor/autoload.php') {
            require_once $path;
            $phpmailer_loaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
        } else {
            try {
                require_once dirname($path) . '/Exception.php';
                require_once $path;
                require_once dirname($path) . '/SMTP.php';
                $phpmailer_loaded = true;
            } catch (Exception $e) {
                echo "❌ Erro ao carregar: " . $e->getMessage() . "<br>";
            }
        }
        break;
    }
}

if (!$phpmailer_loaded) {
    echo "❌ PHPMailer não encontrado nos seguintes caminhos:<br>";
    foreach ($phpmailer_paths as $path) {
        echo "- $path<br>";
    }
    echo "<br><strong>Solução:</strong> Baixe o PHPMailer e coloque na pasta 'phpmailer/'<br>";
    echo "<a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank'>Download PHPMailer</a>";
    exit;
}

echo "✅ PHPMailer carregado com sucesso!<br><br>";

// Verificar se a classe existe
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ Classe PHPMailer\\PHPMailer\\PHPMailer encontrada<br>";
} elseif (class_exists('PHPMailer')) {
    echo "✅ Classe PHPMailer encontrada (versão antiga)<br>";
} else {
    echo "❌ Classe PHPMailer não encontrada<br>";
    exit;
}

// Teste de configuração SMTP
echo "<br><h3>🔧 Teste de Configuração SMTP</h3>";

try {
    // Usar namespace correto baseado na versão
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    } else {
        $mail = new PHPMailer(true);
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
    
    echo "✅ Configuração SMTP criada<br>";
    
    // Teste de conexão (sem enviar)
    echo "<br><h3>🌐 Teste de Conexão SMTP</h3>";
    echo "<pre>";
    
    $mail->setFrom('checklistes1@gmail.com', 'Teste Sistema');
    $mail->addAddress('teste@exemplo.com'); // Email de teste
    $mail->Subject = 'Teste de Configuração';
    $mail->Body = 'Este é um teste de configuração do PHPMailer.';
    
    echo "</pre>";
    echo "✅ Email configurado (não enviado)<br>";
    
} catch (Exception $e) {
    echo "❌ Erro na configuração: " . $e->getMessage() . "<br>";
}

echo "<br><h3>📋 Informações do Sistema</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅ Ativo' : '❌ Inativo') . "<br>";
echo "Sockets: " . (extension_loaded('sockets') ? '✅ Ativo' : '❌ Inativo') . "<br>";
echo "cURL: " . (extension_loaded('curl') ? '✅ Ativo' : '❌ Inativo') . "<br>";

// Verificar função mail()
echo "Função mail(): " . (function_exists('mail') ? '✅ Disponível' : '❌ Indisponível') . "<br>";

echo "<br><a href='nao_conformidades.php'>← Voltar</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>
