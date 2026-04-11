<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json; charset=UTF-8");

// --- CÓDIGO DA CONEXÃO (Plano A e B que já funcionam) ---
$raiz_do_projeto = $_SERVER['DOCUMENT_ROOT'] . '/Printly';
$caminhoConexao = $raiz_do_projeto . '/config/conexao.php';

if (file_exists($caminhoConexao)) {
    require_once $caminhoConexao;
} else {
    $caminhoConexao = dirname(__FILE__, 4) . '/config/conexao.php';
    if (file_exists($caminhoConexao)) {
        require_once $caminhoConexao;
    } else {
        echo json_encode(["status" => "nok", "mensagem" => "Erro crítico: Conexão não encontrada."]);
        exit;
    }
}

// --- AJUSTE DA CHAVE DE SESSÃO ---
// Agora buscando exatamente o que foi salvo no seu login.php
$emailUsuario = $_SESSION['email'] ?? null; 

if (!$emailUsuario) {
    echo json_encode([
        "status" => "nok", 
        "mensagem" => "Sessao nao encontrada",
        "debug_nomes_na_sessao" => array_keys($_SESSION) 
    ]);
    exit;
}

try {
    // Busca as notificações para o e-mail logado
    $stmt = $conexao->prepare("SELECT titulo, mensagem, data_envio FROM notificacoes WHERE email_destino = ? ORDER BY data_envio DESC LIMIT 5");
    $stmt->bind_param("s", $emailUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $dados = [];
    while ($row = $resultado->fetch_assoc()) {
        $dados[] = $row;
    }

    echo json_encode(["status" => "ok", "data" => $dados]);

    $stmt->close();
    $conexao->close();

} catch (Exception $e) {
    echo json_encode(["status" => "nok", "mensagem" => $e->getMessage()]);
}