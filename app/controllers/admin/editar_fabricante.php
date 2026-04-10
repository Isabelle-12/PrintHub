<?php
session_start();
include_once(__DIR__ . '/../../../config/conexao.php');

header('Content-Type: application/json; charset=UTF-8');

$retorno = ['status' => 'no', 'mensagem' => '', 'data' => []];

// 1. Coleta os IDs (Diferenciando Usuário de Fabricante)
$id_fabricante = (int) ($_POST['id'] ?? 0);          // ID da tabela 'fabricantes'
$id_usuario    = (int) ($_POST['usuario_id'] ?? 0); // ID da tabela 'usuarios'

if ($id_fabricante <= 0 || $id_usuario <= 0) {
    $retorno['mensagem'] = 'IDs de identificação ausentes.';
    echo json_encode($retorno);
    exit;
}

// 2. Coleta os demais dados
$nome      = $_POST['nome'] ?? '';
$email     = $_POST['email'] ?? '';
$cnpj      = $_POST['cnpj'] ?? '';
$tel_com   = $_POST['telefone_comercial'] ?? '';
$end_emp   = $_POST['endereco_empresa'] ?? '';
$perfil    = $_POST['tipo_perfil'] ?? 'MAKER';


// Inicia a transação para garantir que ou salva tudo ou não salva nada
$conexao->begin_transaction();

try {
    // UPDATE na tabela USUARIOS
    $sqlUser = "UPDATE usuarios SET nome = ?, email = ?, tipo_perfil = ? WHERE id = ?";
    $stmtU = $conexao->prepare($sqlUser);
    $stmtU->bind_param("sssi", $nome, $email, $perfil, $id_usuario);
    $stmtU->execute();

    if ($perfil === 'MAKER') {
        // Se continua sendo MAKER, faz o UPDATE normal ou INSERT se não existir
        if ($id_fabricante > 0) {
            $sqlFab = "UPDATE fabricantes SET cnpj = ?, telefone_comercial = ?, endereco_empresa = ? WHERE id = ?";
            $stmtF = $conexao->prepare($sqlFab);
            $stmtF->bind_param("sssi", $cnpj, $tel_com, $end_emp, $id_fabricante);
        } else {
            // Caso tenha mudado para MAKER agora e o ID_fabricante ainda não exista
            $sqlFab = "INSERT INTO fabricantes (usuario_id, cnpj, telefone_comercial, endereco_empresa) VALUES (?, ?, ?, ?)";
            $stmtF = $conexao->prepare($sqlFab);
            $stmtF->bind_param("isss", $id_usuario, $cnpj, $tel_com, $end_emp);
        }
        $stmtF->execute();
    }else {
        // Se o perfil mudou para CLIENTE ou ADMIN, removemos ele da tabela de fabricantes
        // Assim ele para de aparecer na lista de makers
        $sqlDel = "DELETE FROM fabricantes WHERE usuario_id = ?";
        $stmtD = $conexao->prepare($sqlDel);
        $stmtD->bind_param("i", $id_usuario);
        $stmtD->execute();
    }
    // Se chegou aqui, deu tudo certo
    $conexao->commit();
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Perfil de Fabricante atualizado com sucesso!';

} catch (Exception $e) {
    // Se der erro em qualquer um, desfaz as alterações
    $conexao->rollback();
    $retorno['status'] = 'erro';
    $retorno['mensagem'] = 'Erro ao salvar: ' . $e->getMessage();
}

echo json_encode($retorno);
$conexao->close();