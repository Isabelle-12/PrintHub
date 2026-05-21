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
$id       = (int) ($dados['id'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'ID inválido.']);
    exit;
}

// Garante que o material pertence ao maker logado antes de excluir
$s = $conexao->prepare("DELETE FROM materiais_maker WHERE id = ? AND maker_id = ?");
$s->bind_param('ii', $id, $maker_id);

if ($s->execute() && $s->affected_rows > 0) {
    echo json_encode(['status' => 'ok', 'mensagem' => 'Material excluído com sucesso.']);
} else {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Material não encontrado ou sem permissão.']);
}

$s->close();
$conexao->close();