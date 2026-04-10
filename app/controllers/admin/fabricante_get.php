<?php
include_once(__DIR__ . '/../../../config/conexao.php');

$retorno = [
    'status'    => '',
    'mensagem'  => '',
    'data'      => []
];

// Se vier um ID, buscamos apenas um fabricante específico
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conexao->prepare("
        SELECT 
            u.id AS usuario_id, 
            f.id AS fabricante_id, 
            u.nome, 
            u.email, 
            f.cnpj, 
            f.telefone_comercial,
            f.endereco_empresa,
            f.data_aprovacao,
            (SELECT GROUP_CONCAT(modelo SEPARATOR ', ') FROM impressoras WHERE maker_id = u.id) as impressoras,
            (SELECT GROUP_CONCAT(tipo_material SEPARATOR ', ') FROM materiais_maker WHERE maker_id = u.id) as materiais
        FROM usuarios u
        LEFT JOIN fabricantes f ON u.id = f.usuario_id
        WHERE u.id = ? AND u.tipo_perfil = 'MAKER'
    ");
    $stmt->bind_param("i", $id);
} else {
    // Caso contrário, listamos todos para a tabela
    $stmt = $conexao->prepare("
        SELECT 
            u.id AS usuario_id, 
            f.id AS fabricante_id, 
            u.nome, 
            u.email, 
            f.cnpj, 
            f.telefone_comercial,
            f.endereco_empresa,
            f.data_aprovacao,
            (SELECT GROUP_CONCAT(modelo SEPARATOR ', ') FROM impressoras WHERE maker_id = u.id) as impressoras
        FROM usuarios u
        LEFT JOIN fabricantes f ON u.id = f.usuario_id
        WHERE u.tipo_perfil = 'MAKER'
        ORDER BY u.nome
    ");
}

$stmt->execute();
$resultado = $stmt->get_result();

$tabela = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        $tabela[] = $linha;
    }
    $retorno = [
        'status'    => 'ok',
        'mensagem'  => 'Fabricantes encontrados.',
        'data'      => $tabela
    ];
} else {
    $retorno = [
        'status'    => 'No',
        'mensagem'  => 'Nenhum fabricante encontrado.',
        'data'      => []
    ];
}

$stmt->close();
$conexao->close();

header("Content-Type: application/json; charset=utf-8");
echo json_encode($retorno);