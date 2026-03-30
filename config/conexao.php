<?php

$servidor   = "localhost:3306";
$usuario    = "root";
$senha      = "q81SL27@";
$nome_banco = "printly_db";

$conexao = new mysqli($servidor, $usuario, $senha, $nome_banco);

if ($conexao->connect_error) {
    header("Content-type: application/json;charset:utf-8");
    echo json_encode([
        "status"   => "nok",
        "mensagem" => "Erro de conexão: " . $conexao->connect_error,
        "data"     => []
    ]);
    exit;
}