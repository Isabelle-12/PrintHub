<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

include_once(__DIR__ . '/../../../config/conexao.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/../../../vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../../vendor/PHPMailer/src/SMTP.php';

$retorno = ['status' => 'nok', 'mensagem' => 'Erro interno', 'data' => []];

if (!$conexao) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro na conexão com o banco']);
    exit;
}

// recebe só o pedido_id — os emails serão buscados no banco
$pedido_id = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;

if (!$pedido_id) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'pedido_id não informado.']);
    exit;
}

// busca o email do cliente (via projetos) e do maker (via pedidos) em uma única query
$stmt = $conexao->prepare("
    SELECT
        c.email AS email_cliente,
        c.nome  AS nome_cliente,
        m.email AS email_maker,
        m.nome  AS nome_maker
    FROM pedidos p
    JOIN projetos pr ON pr.id = p.projeto_id
    JOIN usuarios c  ON c.id  = pr.cliente_id
    JOIN usuarios m  ON m.id  = p.maker_id
    WHERE p.id = ?
");


$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Pedido não encontrado']);
    $stmt->close();
    $conexao->close();
    exit;
}

$partes = $resultado->fetch_assoc();
$stmt->close();

$email_cliente = $partes['email_cliente'];
$email_maker   = $partes['email_maker'];
$nome_cliente  = $partes['nome_cliente'];
$nome_maker    = $partes['nome_maker'];

$titulo   = "Atraso no pedido #" . $pedido_id;
$mensagem = "Seu pedido #{$pedido_id} está com prazo expirado. Entre em contato para mais detalhes.";

// monta o link de acesso ao sistema
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$script    = $_SERVER['SCRIPT_NAME'];
$base_url  = $protocolo . '://' . $host . preg_replace('/\/app\/.*/', '/public', $script);
$link      = $base_url . "/index.php?rota=home";

// função auxiliar para enviar o email via PHPMailer e registrar na tabela notificacoes
function enviarEmailAtraso($conexao, $pedido_id, $titulo, $mensagem, $email_destino, $nome_destino, $link) {
    // registra no banco antes de tentar enviar
    $stmt = $conexao->prepare("
        INSERT INTO notificacoes (tipo, pedido_id, titulo, mensagem, email_destino)
        VALUES ('ATRASO', ?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $pedido_id, $titulo, $mensagem, $email_destino);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        return ['status' => 'nok', 'mensagem' => "Não foi possível registrar notificação para {$email_destino}"];
    }
    $stmt->close();

    // envia o email via PHPMailer
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'printlyi3d@gmail.com';
        $mail->Password   = 'tgzrauenhftrlamo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('printlyi3d@gmail.com', 'Printly');
        $mail->addAddress($email_destino, $nome_destino);

        $mail->Subject = 'Printly - Notificação de pedido atrasado/expirado';
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family: DM Sans, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='font-family: Syne, sans-serif; color: #212529;'>Printly</h2>
                <p>Olá, <strong>{$nome_destino}</strong>!</p>
                <p>Percebemos que o pedido <strong>#{$pedido_id}</strong> está com o prazo atrasado/expirado. Estamos trabalhando para resolver esse transtorno.</p>
                <p>Agradecemos a atenção,</p>
                <a href='{$link}'
                   style='display:inline-block; background:#212529; color:#fff; padding:10px 24px;
                          border-radius:6px; text-decoration:none; font-weight:600;'>
                    Cheque aqui seu pedido.
                </a>
                <p style='margin-top:20px; color:#6c757d; font-size:0.85rem;'>
                    Este e-mail foi enviado automaticamente pela plataforma Printly.
                </p>
            </div>
        ";
        $mail->AltBody = "Olá {$nome_destino}, o pedido #{$pedido_id} está com prazo expirado. Acesse: {$link}";

        $mail->send();
        return ['status' => 'ok', 'mensagem' => "E-mail enviado para {$email_destino}"];

    } catch (Exception $e) {
        return ['status' => 'nok', 'mensagem' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo];
    }
}

// dispara para cliente e maker
$resultadoCliente = enviarEmailAtraso($conexao, $pedido_id, $titulo, $mensagem, $email_cliente, $nome_cliente, $link);
$resultadoMaker   = enviarEmailAtraso($conexao, $pedido_id, $titulo, $mensagem, $email_maker,   $nome_maker,   $link);

$conexao->close();

if ($resultadoCliente['status'] === 'ok' && $resultadoMaker['status'] === 'ok') {
    echo json_encode([
        'status'   => 'ok',
        'mensagem' => 'Notificações registradas e e-mails enviados para cliente e fabricante.'
    ]);
} else {
    // retorna qual dos dois falhou para facilitar debug
    echo json_encode([
        'status'   => 'nok',
        'mensagem' => 'Falha parcial.',
        'cliente'  => $resultadoCliente,
        'maker'    => $resultadoMaker
    ]);
}
exit;