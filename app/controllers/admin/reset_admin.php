<?php

session_start();
header('Content-Type: application/json');
include_once '../../../config/conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/../../../vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../../vendor/PHPMailer/src/SMTP.php';

function resetarSenhaAdmin($conexao, $usuarioId, $adminId) {
    try {

        // 1. Gerar token
        $token = bin2hex(random_bytes(32));
        $dataExpiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // 2. Apagar token antigo
        $stmt = $conexao->prepare("DELETE FROM tokens_reset_senha WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $stmt->close();

        // 3. Inserir novo token
        $stmt = $conexao->prepare(
            "INSERT INTO tokens_reset_senha (usuario_id, token, data_expiracao) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $usuarioId, $token, $dataExpiracao);
        $stmt->execute();
        $stmt->close();

        // 4. Buscar email
        $stmt = $conexao->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $email = $usuario['email'] ?? null;
        $stmt->close();

        if (!$email) {
            throw new Exception("Usuário não encontrado.");
        }

        // 5. Gerar link (igual seu outro script 🔥)
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'];
        $script    = $_SERVER['SCRIPT_NAME'];
        $base_url  = $protocolo . '://' . $host . preg_replace('/\/app\/.*/', '/public', $script);

        $link = $base_url . "/index.php?rota=redefinir-senha&token=" . $token;

        // 6. Enviar e-mail
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pedrobfracaro@gmail.com';
        $mail->Password   = 'waoryklshxphauwo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('pedrobfracaro@gmail.com', 'Printly');
        $mail->addAddress($email);

        $mail->Subject = 'Printly - Redefinição de senha (Admin)';
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family: DM Sans, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color: #212529;'>Printly</h2>
                <p>Um administrador redefiniu sua senha.</p>
                <p>Clique abaixo para criar uma nova senha:</p>
                <a href='{$link}' style='background:#dc3545;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>
                    Redefinir senha
                </a>
                <p style='margin-top:20px;font-size:12px;color:#6c757d;'>
                    Válido por 1 hora. Se não reconhece essa ação, contate suporte.
                </p>
            </div>
        ";

        $mail->AltBody = "Acesse: {$link}";

        $mail->send();

        // 7. Notificação
        // 7. Notificação (Ajustado para o seu banco real)

        $stmt = $conexao->prepare("
            INSERT INTO notificacoes (tipo, titulo, mensagem, email_destino)
            VALUES (?, ?, ?, ?)
        ");

        $tipo = "RETIFICACAO"; // Usei um tipo que já existe no seu ENUM do banco
        $titulo = "Redefinição de senha";
        $mensagem = "Sua senha foi redefinida por um administrador. Verifique seu e-mail.";
        // $email já foi buscado no Passo 4 do seu script
        $stmt->bind_param("ssss", $tipo, $titulo, $mensagem, $email); 
        $stmt->execute();
        $stmt->close();

        // 8. Log
        $stmt = $conexao->prepare("
            INSERT INTO logs_sistema (usuario_id, acao, tabela_afetada)
            VALUES (?, ?, 'usuarios')
        ");
        $acao = "Resetou senha do usuário ID $usuarioId";
        $stmt->bind_param("is", $adminId, $acao);
        $stmt->execute();
        $stmt->close();

        return ['status' => 'ok'];

    } catch (Exception $e) {
        return ['status' => 'erro', 'mensagem' => $e->getMessage()];
    }
}

if (!isset($_POST['usuario_id'])) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'ID do usuário não enviado'
    ]);
    exit;
}

$usuarioId = $_POST['usuario_id'];
$adminId = $_SESSION['usuario_id'] ?? null;

$resultado = resetarSenhaAdmin($conexao, $usuarioId, $adminId);

echo json_encode($resultado);
exit;