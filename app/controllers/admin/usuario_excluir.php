<?php
include_once(__DIR__ . '/../../../config/conexao.php');

$retorno = [
    'status' => '',
    'mensagem' => '',
    'data' => []
];

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $retorno = [
            "status" => "Ok",
            "mensagem" => $stmt->affected_rows . " registro(s) excluído(s).",
            "data" => []
        ];
    } else {
        $retorno = [
            "status" => "No",
            "mensagem" => "Nenhum registro foi excluído.",
            "data" => []
        ];
    }

    $stmt->close();
} else {
    $retorno = [
        "status" => "No",
        "mensagem" => "É necessário informar um ID para excluir.",
        "data" => []
    ];
}

$conexao->close();
header("Content-Type: application/json; charset=utf-8");
echo json_encode($retorno);