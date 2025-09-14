<?php
    $servername = "localhost:3307";
    $username = "root";
    $password = "";
    $dbname = "ferramentaauditoria";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }
?>