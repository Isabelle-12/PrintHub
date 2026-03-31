<?php

$servidor   = "localhost";
$usuario    = "printly_user";
$senha      = "123456";
$nome_banco = "printly_db";

$conexao = new mysqli($servidor, $usuario, $senha, $nome_banco);

if ($conexao->connect_error) {
    header("Content-type: application/json;charset=utf-8");
    echo json_encode([
        "status"   => "nok",
        "mensagem" => "Erro ao conectar ao banco de dados",
        "data"     => []
    ]);
    exit;
}


$conexao->set_charset("utf8mb4");