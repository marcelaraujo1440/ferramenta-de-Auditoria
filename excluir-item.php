<?php
session_start();
include "db/conexao_db.php";

if (!isset($_GET['id'])) {
    die("ID do item não informado.");
}

$id = intval($_GET['id']); 

$sql = $conn->prepare("DELETE FROM checklist WHERE id = ?");
$sql->bind_param("i", $id);

if ($sql->execute()) {
    echo "<script>alert('Item excluído com sucesso!'); window.location.href='checklist.php';</script>";
} else {
    echo "Erro ao excluir o item: " . $sql->error;
}
?>
