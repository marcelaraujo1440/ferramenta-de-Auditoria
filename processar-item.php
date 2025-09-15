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
    $descricao = trim($_POST['descricao']);
    $resultado = trim($_POST['resultado']);
    $responsavel = trim($_POST['responsavel']);
    $classificacao = trim($_POST['classificacao']);

    $data_identificacao = validar_datetime($_POST['data_identificacao']);
    $prazo = validar_datetime($_POST['prazo']);
    $data_escalonamento = validar_datetime($_POST['data_escalonamento']);
    $data_conclusao = validar_datetime($_POST['data_conclusao']);

    $observacoes = trim($_POST['observacoes']);
    $acao_corretiva_indicada = trim($_POST['acao_corretiva_indicada']);

    $sql_checklist = $conn->prepare("INSERT INTO checklist 
        (descricao, resultado, responsavel, classificacao, data_identificacao, prazo, data_escalonamento, data_conclusao, observacoes, acao_corretiva_indicada) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $sql_checklist->bind_param(
        "ssssssssss",
        $descricao, $resultado, $responsavel, $classificacao,
        $data_identificacao, $prazo, $data_escalonamento, $data_conclusao,
        $observacoes, $acao_corretiva_indicada
    );

    if ($sql_checklist->execute()) {
        echo "<script>alert('Cadastro realizado com sucesso!'); location.href='checklist.php';</script>";
    } else {
        echo "Erro: " . $sql_checklist->error;
    }
}
