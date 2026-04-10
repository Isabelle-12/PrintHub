<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../config/conexao.php";

$id = $_GET['id'] ?? null;
$decisao = $_GET['decisao'] ?? null;

if (!$id || !$decisao) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos"]);
    exit;
}

$status = ($decisao === 'aprovar') ? 'APROVADO' : 'REJEITADO';
$perfil = ($decisao === 'aprovar') ? 'MAKER' : 'CLIENTE';

//atualiza o status e tipo_perfil na tabela usuarios
$sql = "UPDATE usuarios SET status_fabricante = '$status', tipo_perfil = '$perfil' WHERE id = $id";
$conexao->query($sql);

//se aprovado, registra a data de aprovação na tabela fabricantes
if ($decisao === 'aprovar') {
    $conexao->query("UPDATE fabricantes SET data_aprovacao = NOW() WHERE usuario_id = $id");
}

//busca o email do usuário para a notificação
$res  = $conexao->query("SELECT email, nome FROM usuarios WHERE id = $id");
$user = $res->fetch_assoc();

//monta a mensagem conforme a decisão
if ($decisao === 'aprovar') {
    $titulo   = "Parabéns! Você é um Maker!";
    $mensagem = "Sua solicitação para ser Maker foi APROVADA. Você já pode acessar o painel do Maker.";
} else {
    $titulo   = "Solicitação de Maker recusada";
    $mensagem = "Infelizmente sua solicitação para ser Maker foi RECUSADA pelos administradores. Você pode tentar novamente a qualquer momento.";
}

//insere a notificação no banco
$stmtNotif = $conexao->prepare("
    INSERT INTO notificacoes (tipo, titulo, mensagem, email_destino)
    VALUES ('RETIFICACAO', ?, ?, ?)
");
$stmtNotif->bind_param("sss", $titulo, $mensagem, $user['email']);
$stmtNotif->execute();
$stmtNotif->close();

echo json_encode(["status" => "ok", "mensagem" => "Fabricante $status com sucesso!"]);

$conexao->close();
