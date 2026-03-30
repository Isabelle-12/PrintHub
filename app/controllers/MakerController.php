<?php

include_once("conexao.php");
include_once("geo.php");

// Inicialização do array de retorno (padrão do grupo)
$retorno = [
    "status"   => "",
    "mensagem" => "",
    "data"     => []
];

// Aceita apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $retorno["status"]   = "nok";
    $retorno["mensagem"] = "Método não permitido. Use GET.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit;
}

// ── CORS ──────────────────────────────────────────────────────────────────────
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-type: application/json;charset:utf-8");

// ── Leitura dos parâmetros ────────────────────────────────────────────────────

// CEP: obrigatório
$cepRaw = isset($_GET['cep']) ? trim($_GET['cep']) : '';

// Raio: opcional, padrão 10 km
$raioKm = isset($_GET['raio']) ? (int)$_GET['raio'] : 10;
if ($raioKm <= 0) $raioKm = 10;
if ($raioKm > 500) $raioKm = 500;

// ── Validação do CEP ──────────────────────────────────────────────────────────
// Remove tudo que não for dígito e verifica se sobram exatamente 8 números
$cep = preg_replace('/\D/', '', $cepRaw);

if (strlen($cep) !== 8) {
    $retorno["status"]   = "nok";
    $retorno["mensagem"] = "CEP inválido. Informe 8 dígitos (ex: 80215180 ou 80215-180).";
    echo json_encode($retorno);
    exit;
}

// ── Converte CEP de origem em coordenadas ─────────────────────────────────────
$coordOrigem = cepParaCoordenadas($cep);

if ($coordOrigem === null) {
    $retorno["status"]   = "nok";
    $retorno["mensagem"] = "CEP não encontrado na base de regiões.";
    echo json_encode($retorno);
    exit;
}

$latOrigem = $coordOrigem[0];
$lonOrigem = $coordOrigem[1];

// ── Busca makers no banco ─────────────────────────────────────────────────────
// Traz apenas usuários com tipo_perfil = 'MAKER' e CEP preenchido.
// Lat/lon NÃO são armazenados — calculados dinamicamente via geo.php.
$stmt = $conexao->prepare("
    SELECT id, nome, endereco, cep, cidade, estado
    FROM usuarios
    WHERE tipo_perfil = 'MAKER'
      AND cep IS NOT NULL
      AND cep != ''
");
$stmt->execute();
$resultado = $stmt->get_result();

// ── Calcula distância e filtra pelo raio ──────────────────────────────────────
$makers = [];

if ($resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {

        // Normaliza o CEP do maker (remove hífen, espaços etc.)
        $cepMaker = preg_replace('/\D/', '', $linha['cep']);

        // Pula makers com CEP inválido no cadastro
        if (strlen($cepMaker) !== 8) continue;

        // Busca coordenadas do maker
        $coordMaker = cepParaCoordenadas($cepMaker);
        if ($coordMaker === null) continue;

        // Calcula distância usando Haversine
        $distancia = calcularDistanciaKm(
            $latOrigem, $lonOrigem,
            $coordMaker[0], $coordMaker[1]
        );

        // Inclui apenas quem está dentro do raio
        if ($distancia <= $raioKm) {
            $makers[] = [
                "id"           => $linha['id'],
                "nome"         => $linha['nome'],
                "endereco"     => $linha['endereco'],
                "cep"          => $cepMaker,
                "cidade"       => $linha['cidade'],
                "estado"       => $linha['estado'],
                "distancia_km" => $distancia
            ];
        }
    }
}

$stmt->close();
$conexao->close();

// ── Ordena do mais próximo ao mais distante ───────────────────────────────────
//usort, função nativa do PHP para ordenar arrays associativos usando uma função de comparação personalizada.
usort($makers, function($a, $b) {
    return $a['distancia_km'] <=> $b['distancia_km'];
});

// ── Monta retorno ─────────────────────────────────────────────────────────────
if (count($makers) > 0) {
    $retorno = [
        "status"   => "ok",
        "mensagem" => count($makers) . " maker(s) encontrado(s) em até " . $raioKm . " km.",
        "data"     => $makers
    ];
} else {
    $retorno = [
        "status"   => "nok",
        "mensagem" => "Nenhum maker encontrado em até " . $raioKm . " km do CEP informado.",
        "data"     => []
    ];
}

echo json_encode($retorno, JSON_UNESCAPED_UNICODE);