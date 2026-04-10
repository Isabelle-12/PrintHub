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

//muda o tipo_perfil para MAKER, status_fabricante para APROVADO e status para ATIVO
$stmt = $conexao->prepare("
    UPDATE usuarios
    SET tipo_perfil = 'MAKER', status_fabricante = 'APROVADO', status = 'ATIVO'
    WHERE id = ? AND status_fabricante = 'PENDENTE'
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

// Atualiza a data de aprovação na tabela fabricantes
$stmt2 = $conexao->prepare("UPDATE fabricantes SET data_aprovacao = NOW() WHERE usuario_id = ?");
$stmt2->bind_param("i", $usuario_id);
$stmt2->execute();

$retorno = ["status" => "ok", "mensagem" => "Usuário aprovado como Maker!"];
echo json_encode($retorno);