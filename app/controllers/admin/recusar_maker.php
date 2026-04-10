<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/conexao.php';

$retorno = ["status" => "", "mensagem" => ""];

if ($_SESSION['tipo'] !== 'ADMIN') {
    $retorno = ["status" => "nok", "mensagem" => "Sem permissão."];
    echo json_encode($retorno); exit;
}

$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;

if (!$usuario_id) {
    $retorno = ["status" => "nok", "mensagem" => "usuario_id inválido."];
    echo json_encode($retorno); exit;
}

$stmt = $conexao->prepare("
    UPDATE usuarios
    SET status_fabricante = 'REJEITADO'
    WHERE id = ? AND status_fabricante = 'PENDENTE'
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$retorno = ["status" => "ok", "mensagem" => "Solicitação recusada."];
echo json_encode($retorno);