<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../../../config/conexao.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Não autorizado.']);
    exit;
}

$makerId = (int) $_SESSION['id'];
$dados   = json_decode(file_get_contents('php://input'), true);

$pedidoId    = (int)($dados['id']          ?? 0);
$novoStatus  = trim($dados['status']       ?? '');
$observacao  = trim($dados['observacao']   ?? '');

// Validação de dados
if ($pedidoId <= 0 || !$novoStatus) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Dados inválidos.']);
    exit;
}

// Mapa de transições permitidas por status atual
// Garante que o maker só pode avançar o pedido em ordem lógica
$transicoesPermitidas = [
    'AGUARDANDO_CONFIRMACAO' => ['ACEITO', 'NEGADO'],
    'ARQUIVO_VALIDADO'       => ['ACEITO', 'NEGADO'],
    'ACEITO'                 => ['EM_PRODUCAO', 'NEGADO'],
    'EM_PRODUCAO'            => ['CONCLUIDO'],
    'CONCLUIDO'              => ['ENTREGUE'],
    'ENTREGUE'               => [],
    'NEGADO'                 => [],
    'CANCELADO'              => [],
];

if (!array_key_exists($novoStatus, array_merge(...array_values($transicoesPermitidas)) ? [] : [])) {
    // Apenas verifica se o novoStatus é um valor conhecido
    $todosStatus = array_unique(array_merge(
        array_keys($transicoesPermitidas),
        ...array_values($transicoesPermitidas)
    ));
    if (!in_array($novoStatus, $todosStatus)) {
        echo json_encode(['status' => 'nok', 'mensagem' => 'Status inválido.']);
        exit;
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao->begin_transaction();

    // 1. Verifica se o pedido pertence ao maker logado e busca dados para notificação
    $stmtBusca = $conexao->prepare(
        "SELECT p.status AS status_atual,
                pr.nome_projeto,
                u.email AS cliente_email,
                u.nome  AS cliente_nome
         FROM pedidos p
         JOIN projetos pr ON pr.id = p.projeto_id
         JOIN usuarios u  ON u.id  = pr.cliente_id
         WHERE p.id = ? AND p.maker_id = ?"
    );
    $stmtBusca->bind_param('ii', $pedidoId, $makerId);
    $stmtBusca->execute();
    $pedido = $stmtBusca->get_result()->fetch_assoc();
    $stmtBusca->close();

    if (!$pedido) {
        throw new Exception('Pedido não encontrado ou sem permissão.');
    }

    $statusAnterior = $pedido['status_atual'];
    $nomeProjeto    = $pedido['nome_projeto'];

    // Valida se a transição de status é permitida
    $proximosPermitidos = $transicoesPermitidas[$statusAnterior] ?? [];
    if (!in_array($novoStatus, $proximosPermitidos)) {
        throw new Exception("Transição inválida: não é possível mudar de \"{$statusAnterior}\" para \"{$novoStatus}\".");
    }
    $clienteEmail   = $pedido['cliente_email'];

    // Se o status não mudou, não faz nada
    if ($statusAnterior === $novoStatus) {
        echo json_encode(['status' => 'ok', 'mensagem' => 'Status não alterado (já era o mesmo).']);
        exit;
    }

    // 2. Atualiza o status no pedido — registra data_atualizacao automaticamente via ON UPDATE
    // Quando NEGADO, salva a observação também em motivo_recusa para exibição ao cliente
    $obsGravada = $observacao ?: null;

    if ($novoStatus === 'NEGADO' && empty($obsGravada)) {
        throw new Exception('O motivo da recusa é obrigatório ao negar um pedido.');
    }

    $motivoRecusa = ($novoStatus === 'NEGADO') ? $obsGravada : null;

    $stmtUpdate = $conexao->prepare(
        "UPDATE pedidos SET status = ?, data_atualizacao = NOW(),
         motivo_recusa = COALESCE(?, motivo_recusa)
         WHERE id = ? AND maker_id = ?"
    );
    $stmtUpdate->bind_param('ssii', $novoStatus, $motivoRecusa, $pedidoId, $makerId);
    $stmtUpdate->execute();
    if ($stmtUpdate->affected_rows === 0) {
        throw new Exception('Nenhuma alteração realizada.');
    }
    $stmtUpdate->close();

    // 3. Registra no histórico de status com data/hora da atualização
    $stmtHist = $conexao->prepare(
        "INSERT INTO historico_status_pedido (pedido_id, status_anterior, status_novo, alterado_por, observacao, data_hora)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmtHist->bind_param('issss', $pedidoId, $statusAnterior, $novoStatus, $makerId, $obsGravada);
    $stmtHist->execute();
    $stmtHist->close();

    // 4. Envia notificação ao cliente sobre a atualização
    $labelsStatus = [
        'ACEITO'       => 'Aceito',
        'EM_PRODUCAO'  => 'Em Produção',
        'CONCLUIDO'    => 'Concluído',
        'ENTREGUE'     => 'Entregue',
        'CANCELADO'    => 'Cancelado',
        'NEGADO'       => 'Negado',
        'AGUARDANDO_CONFIRMACAO' => 'Aguardando Confirmação',
    ];
    $labelNovo = $labelsStatus[$novoStatus] ?? $novoStatus;
    $titulo    = "Atualização do Pedido #{$pedidoId}";
    $mensagem  = "O status do seu pedido \"{$nomeProjeto}\" foi atualizado para: {$labelNovo}.";
    if ($observacao) {
        $mensagem .= " Observação do fabricante: {$observacao}";
    }
    $tipo = 'RETIFICACAO';

    $stmtNotif = $conexao->prepare(
        "INSERT INTO notificacoes (tipo, pedido_id, titulo, mensagem, email_destino, data_envio)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmtNotif->bind_param('sisss', $tipo, $pedidoId, $titulo, $mensagem, $clienteEmail);
    $stmtNotif->execute();
    $stmtNotif->close();

    $conexao->commit();

    echo json_encode([
        'status'   => 'ok',
        'mensagem' => 'Status atualizado com sucesso!'
    ]);

} catch (Exception $e) {
    $conexao->rollback();
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro: ' . $e->getMessage()]);
}

$conexao->close();