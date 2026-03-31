<?php
// leitura da tabela de usuários (somente clientes, sem fabricantes)
include_once(__DIR__ . '/../../../config/conexao.php');

$retorno = [
    'status'    => '', 
    'mensagem'  => '', 
    'data'      => []
];

// Seleciona apenas usuários do tipo CLIENTE que não têm registro como fabricante
$stmt = $conexao->prepare("
    SELECT u.*
    FROM usuarios u
    LEFT JOIN fabricantes f ON f.usuario_id = u.id
    WHERE u.tipo_perfil = 'CLIENTE' AND f.id IS NULL
    ORDER BY u.nome
");

$stmt->execute();
$resultado = $stmt->get_result();

$tabela = [];
if($resultado->num_rows > 0){
    while($linha = $resultado->fetch_assoc()){
        $tabela[] = $linha;
    }
    $retorno = [
        'status'    => 'ok', 
        'mensagem'  => 'Sucesso, usuários encontrados.', 
        'data'      => $tabela
    ];
}else{
    $retorno = [
        'status'    => 'vazio', 
        'mensagem'  => 'Não há usuários cadastrados.', 
        'data'      => []
    ];
}

$stmt->close();
$conexao->close();

header("Content-Type: application/json; charset=utf-8");
echo json_encode($retorno);