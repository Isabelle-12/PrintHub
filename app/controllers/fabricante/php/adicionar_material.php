<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../../../config/conexao.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Não autorizado.']);
    exit;
}

$maker_id = (int) $_SESSION['id'];
$dados    = json_decode(file_get_contents('php://input'), true);

$tipo  = trim($dados['tipo']  ?? '');
$preco = isset($dados['preco']) ? (float) $dados['preco'] : 0;

if (!$tipo) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Selecione o tipo de material.']);
    exit;
}

if ($preco <= 0) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Informe um preço por grama válido.']);
    exit;
}

$s = $conexao->prepare(
    "INSERT INTO materiais_maker (maker_id, tipo_material, preco_por_grama) VALUES (?, ?, ?)"
);
$s->bind_param('isd', $maker_id, $tipo, $preco);

if ($s->execute()) {
    echo json_encode(['status' => 'ok', 'mensagem' => 'Material adicionado com sucesso.']);
} else {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro ao salvar: ' . $s->error]);
}

$s->close();
$conexao->close();