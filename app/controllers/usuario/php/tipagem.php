<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
$resposta = [];

if (!isset($_SESSION['tipo'])) {
    echo json_encode([
        "tipo" => false
    ]);
    exit;
}

$resposta = [ 
    "tipo" => true,
    "tipos" => $_SESSION['tipo']
];
    
echo json_encode($resposta);
