<?php
// Carregar PHPMailer manualmente (sem Composer)
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

include "db/conexao_db.php";

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Requisição inválida!");
}

$id_item = isset($_POST['id_item']) ? intval($_POST['id_item']) : 0;
$destinatario = isset($_POST['destinatario']) ? trim($_POST['destinatario']) : '';

if ($id_item <= 0 || empty($destinatario)) {
    die("❌ Todos os campos são obrigatórios!");
}

// Buscar dados do item no checklist
$sql = "
    SELECT 
        id,
        nome AS nome_checklist,
        descricao,
        resultado,
        responsavel,
        classificacao,
        situacao,
        data_identificacao,
        prazo,
        data_escalonamento,
        data_conclusao,
        observacoes,
        acao_corretiva_indicada
    FROM checklist 
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_item);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) die("❌ Item não encontrado!");

// Verificar se é uma não conformidade (resultado = "Não")
if ($item['resultado'] !== 'Não') {
    die("❌ Este item não é uma não conformidade!");
}

// Usar dados do formulário ou do banco como fallback
$responsavel = !empty($_POST['responsavel']) ? trim($_POST['responsavel']) : ($item['responsavel'] ?? 'Não definido');
$rqa = !empty($_POST['rqa']) ? trim($_POST['rqa']) : 'Não definido';
$acao_corretiva = !empty($_POST['acao']) ? trim($_POST['acao']) : ($item['acao_corretiva_indicada'] ?? 'Não definida');
$prazo = !empty($_POST['prazo']) ? trim($_POST['prazo']) : ($item['prazo'] ?? 'Não definido');

// Formatar prazo se for uma data
if (!empty($item['prazo']) && $item['prazo'] !== 'Não definido') {
    try {
        $data_prazo = new DateTime($item['prazo']);
        $prazo = $data_prazo->format('d/m/Y H:i');
    } catch (Exception $e) {
        $prazo = $item['prazo'];
    }
}

// Formatar data de identificação
$data_identificacao_formatada = 'Não definida';
if (!empty($item['data_identificacao'])) {
    try {
        $data_id = new DateTime($item['data_identificacao']);
        $data_identificacao_formatada = $data_id->format('d/m/Y H:i');
    } catch (Exception $e) {
        $data_identificacao_formatada = $item['data_identificacao'];
    }
}

$remetente = 'bielzinralaqueijo@gmail.com';
$assunto = "Solicitação de Resolução de Não Conformidade #".$item['id'];

$mensagem = "
<html>
<head><meta charset='utf-8'></head>
<body>
📋 <strong>Solicitação de Resolução de Não Conformidade</strong><br><br>

✅ <strong>Checklist:</strong> ".htmlspecialchars($item['nome_checklist'])."<br>
📅 <strong>Data de Identificação:</strong> ".htmlspecialchars($data_identificacao_formatada)."<br>
👤 <strong>Responsável:</strong> ".htmlspecialchars($responsavel)."<br>
📌 <strong>RQA Responsável:</strong> ".htmlspecialchars($rqa)."<br>
⏰ <strong>Prazo de Resolução:</strong> ".htmlspecialchars($prazo)."<br>
📈 <strong>Situação:</strong> ".htmlspecialchars($item['situacao'] ?? 'Não definida')."<br>
📝 <strong>Descrição da NC:</strong> ".htmlspecialchars($item['descricao'])."<br>
🏷 <strong>Classificação:</strong> ".htmlspecialchars($item['classificacao'] ?? 'Não definida')."<br>";

if (!empty($item['observacoes'])) {
    $mensagem .= "💬 <strong>Observações:</strong> ".htmlspecialchars($item['observacoes'])."<br>";
}

$mensagem .= "⚙ <strong>Ação Corretiva Indicada:</strong> ".htmlspecialchars($acao_corretiva)."<br>
</body>
</html>
";

$mail = new PHPMailer();
$mail->CharSet = 'UTF-8';
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = $remetente;
$mail->Password = 'udtj zrfs cemz dqua';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom($remetente, 'Auditoria');
$mail->addAddress($destinatario);

$mail->isHTML(true);
$mail->Subject = $assunto;
$mail->Body    = $mensagem;

if (!$mail->send()) {
    header("Location: checklist.php?msg=" . urlencode("❌ Erro ao enviar email: " . $mail->ErrorInfo));
    exit;
} else {
    $conn->close();
    header("Location: checklist.php?msg=" . urlencode("✅ Email enviado com sucesso!"));
    exit;
}
?>