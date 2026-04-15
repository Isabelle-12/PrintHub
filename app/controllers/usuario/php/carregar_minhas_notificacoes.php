<?php
include_once '../../../../config/conexao.php';

$retorno = ["status" => "", "mensagem" => "", "data" => []];

session_start();
$emailUsuario = $_SESSION['email'] ?? null;

if (!$emailUsuario) {
    $retorno = ["status" => "nok", "mensagem" => "Sessão não encontrada", "data" => []];
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit;
}

$stmt = $conexao->prepare("SELECT titulo, mensagem, data_envio FROM notificacoes WHERE email_destino = ? ORDER BY data_envio DESC LIMIT 5");
$stmt->bind_param("s", $emailUsuario);
$stmt->execute();
$resultado = $stmt->get_result();

$tabela = [];
while ($linha = $resultado->fetch_assoc()) {
    $tabela[] = $linha;
}

$retorno = ["status" => "ok", "mensagem" => count($tabela) . " notificação(ões)", "data" => $tabela];
$stmt->close();
$conexao->close();

header("Content-type: application/json;charset:utf-8");
echo json_encode($retorno);