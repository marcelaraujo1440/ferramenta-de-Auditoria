<?php
    $servername = "localhost";
    $port = 3307; 
    $username = "root";
    $password = "";  // se necessário, ajuste a senha ou remova-a
    $dbname = "ferramenta_auditoria";

    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }
    
    // Define o charset para UTF-8
    $conn->set_charset("utf8");
?>