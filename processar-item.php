<?php
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    session_start();
    include "db/conexao_db.php";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $descricao = trim($_POST['descricao']);
        $resultado = trim($_POST['resultado']);
        $responsavel = trim($_POST['responsavel']);
        $classificacao = trim($_POST['classificacao']);
        $data_identificacao = trim($_POST['data_identificacao']);
        $prazo = trim($_POST['prazo']);
        $data_escalonamento = trim($_POST['data_escalonamento']);
        $data_conclusao = trim($_POST['data_conclusao']);
        $observacoes = trim($_POST['observacoes']);
        $acao_corretiva_indicada = trim($_POST['acao_corretiva_indicada']);
    }

    $sql_checklist = $conn->prepare("INSERT INTO checklist (descricao, resultado, responsavel, classificacao, data_identificacao, prazo, data_escalonamento, data_conclusao, observacoes, acao_corretiva_indicada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $sql_checklist->bind_param("ssssssssss", $descricao, $resultado, $responsavel, $classificacao, $data_identificacao, $prazo, $data_escalonamento, $data_conclusao, $observacoes, $acao_corretiva_indicada);
    if ($sql_checklist->execute()) {
        echo "<script>alert('Cadastro realizado com sucesso!'); location.href='checklist.php';</script>";
    } else {
        echo "Erro: " . $sql_checklist->error;
    }