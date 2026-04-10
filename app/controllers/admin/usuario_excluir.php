<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../config/conexao.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["status" => "erro", "mensagem" => "ID não informado."]);
    exit;
}

try {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id); // "i" de integer para segurança

    if ($stmt->execute()) {
        echo json_encode(["status" => "ok", "mensagem" => "Usuário e todos os seus vínculos removidos com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao excluir: " . $conexao->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro de servidor: " . $e->getMessage()]);
}

$conexao->close();
