<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../../../config/conexao.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Não autorizado.']);
    exit;
}

$makerId = (int) $_SESSION['id'];

// Busca dados completos incluindo arquivo 3D, imagem de capa, descrição, partes e telefone
$sql = "SELECT
            p.id,
            p.status,
            p.valor_total,
            p.quantidade,
            p.material_escolhido,
            p.data_solicitacao,
            p.data_atualizacao,
            p.prazo_pedido,
            p.endereco_entrega,
            p.motivo_recusa,
            p.arquivo_caminho   AS capa_path,

            pr.id               AS projeto_id,
            pr.nome_projeto,
            pr.descricao,
            pr.formato,
            pr.arquivo_caminho  AS arquivo_3d,
            pr.volume_estimado_cm3,
            pr.peso_estimado_gramas,

            c.id                AS cliente_id,
            c.nome              AS cliente_nome,
            c.email             AS cliente_email,
            c.telefone          AS cliente_telefone,

            (SELECT COUNT(*) FROM partes_pedido pp WHERE pp.pedido_id = p.id) AS total_partes

        FROM pedidos p
        JOIN projetos pr ON pr.id = p.projeto_id
        JOIN usuarios c  ON c.id  = pr.cliente_id
        WHERE p.maker_id = ?
        ORDER BY p.data_atualizacao DESC";

$stmt = $conexao->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro no prepare: ' . $conexao->error]);
    exit;
}

$stmt->bind_param('i', $makerId);
$stmt->execute();
$resultado = $stmt->get_result();

$pedidos = [];
while ($row = $resultado->fetch_assoc()) {
    $pedidos[] = $row;
}

$stmt->close();
$conexao->close();

echo json_encode(['status' => 'ok', 'data' => $pedidos]);