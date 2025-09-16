<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include "db/conexao_db.php";

// Função para validar datetime do input
function validar_datetime($valor) {
    if (empty($valor)) {
        return null;
    }

    $valor = str_replace("T", " ", trim($valor)) . ":00";

    // Verifica se é uma data válida
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $valor);
    if ($d && $d->format('Y-m-d H:i:s') === $valor) {
        return $valor;
    }

    return null; // retorna NULL se inválido
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se há um checklist selecionado na sessão
    if (!isset($_SESSION['checklist_nome']) || empty($_SESSION['checklist_nome'])) {
        echo "<script>alert('Nenhum checklist selecionado. Redirecionando...'); location.href='listar-checklists.php';</script>";
        exit;
    }
    
    $checklist_nome = $_SESSION['checklist_nome'];
    
    $descricao = trim($_POST['descricao']);
    $resultado = trim($_POST['resultado']);
    $responsavel = trim($_POST['responsavel']);
    $classificacao = trim($_POST['classificacao']);
    $situacao = trim($_POST['situacao']);
    
    // Se classificação estiver vazia ou resultado for "Sim", definir como NULL
    if (empty($classificacao) || $resultado === 'Sim') {
        $classificacao = null;
    }
    
    // Se ação corretiva estiver vazia ou resultado for "Sim", definir como NULL
    if (empty($acao_corretiva_indicada) || $resultado === 'Sim') {
        $acao_corretiva_indicada = null;
    }
    
    // Se situação estiver vazia ou resultado for "Sim", definir como NULL
    if (empty($situacao) || $resultado === 'Sim') {
        $situacao = null;
    }
    
    // Se prazo estiver vazio ou resultado for "Sim", definir como NULL
    if ($resultado === 'Sim') {
        $prazo = null;
    }
    
    // Se data de escalonamento estiver vazia ou resultado for "Sim", definir como NULL
    if ($resultado === 'Sim') {
        $data_escalonamento = null;
    }

    $data_identificacao = validar_datetime($_POST['data_identificacao']);
    $prazo = validar_datetime($_POST['prazo']);
    $data_escalonamento = validar_datetime($_POST['data_escalonamento']);
    $data_conclusao = validar_datetime($_POST['data_conclusao']);

    $observacoes = trim($_POST['observacoes']);
    $acao_corretiva_indicada = trim($_POST['acao_corretiva_indicada']);

    $sql_checklist = $conn->prepare("INSERT INTO checklist 
        (nome, descricao, resultado, responsavel, classificacao, situacao, data_identificacao, prazo, data_escalonamento, data_conclusao, observacoes, acao_corretiva_indicada) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $sql_checklist->bind_param(
        "ssssssssssss",
        $checklist_nome, $descricao, $resultado, $responsavel, $classificacao, $situacao,
        $data_identificacao, $prazo, $data_escalonamento, $data_conclusao,
        $observacoes, $acao_corretiva_indicada
    );

    if ($sql_checklist->execute()) {
        echo "<script>alert('Item adicionado ao checklist com sucesso!'); location.href='checklist.php';</script>";
    } else {
        echo "Erro: " . $sql_checklist->error;
    }
}
