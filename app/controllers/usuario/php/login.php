<?php
error_reporting(0); // Desativa a exibição de erros que quebram o JSON
header("Content-type: application/json; charset=utf-8");

include_once(__DIR__ . '/../../../../config/conexao.php');

// Inicie a sessão apenas se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$retorno = ['status' => 'nok', 'mensagem' => 'Erro interno', 'data' => []];

// Verifique se a conexão existe
if (!$conexao) {
    echo json_encode(['status' => 'nok', 'mensagem' => 'Erro de conexão com o banco']);
    exit;
}

// ... restante do código com a comparação de senha simples (==)
if (isset($_POST['email']) && isset($_POST['senha'])) {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        
        // Comparação em texto simples como você pediu
        if ($_POST['senha'] == $usuario['senha']) {
            $_SESSION['usuario'] = $usuario;
            $retorno = [
                'status' => 'ok',
                'mensagem' => 'Sucesso',
                'data' => $usuario
            ];
        } else {
            $retorno['mensagem'] = "Senha incorreta";
        }
    } else {
        $retorno['mensagem'] = "Usuário não encontrado";
    }
}

$stmt->close();
$conexao->close();

echo json_encode($retorno);