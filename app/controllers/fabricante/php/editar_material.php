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

$id    = (int) ($dados['id']    ?? 0);
$tipo  = trim($dados['tipo']    ?? '');
$preco = isset($dados['preco']) ? (float) $dados['preco'] : 0;

if (!$id || !$tipo) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Dados inválidos.']);
    exit;
}

if ($preco <= 0) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Informe um preço por grama válido.']);
    exit;
}

// Garante que o material pertence ao maker logado
$chk = $conexao->prepare("SELECT id FROM materiais_maker WHERE id = ? AND maker_id = ?");
$chk->bind_param('ii', $id, $maker_id);
$chk->execute();
$chk->store_result();

if ($chk->num_rows === 0) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Material não encontrado.']);
    exit;
}
$chk->close();

$s = $conexao->prepare(
    "UPDATE materiais_maker SET tipo_material = ?, preco_por_grama = ? WHERE id = ? AND maker_id = ?"
);
$s->bind_param('sdii', $tipo, $preco, $id, $maker_id);

if ($s->execute()) {
    echo json_encode(['status' => 'ok', 'mensagem' => 'Material atualizado com sucesso.']);
} else {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro ao atualizar: ' . $s->error]);
}

$s->close();
$conexao->close();