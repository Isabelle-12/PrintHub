<?php

// ── Base interna: prefixo de CEP → [latitude, longitude, cidade, estado] ──────
// Coordenadas baseadas nos centróides municipais do IBGE (domínio público).
// A busca vai do prefixo mais longo (mais preciso) ao mais curto (fallback).
function cepParaCoordenadas($cep) {
    $base = [
        // Paraná
        '80' => [-25.4297, -49.2711, 'Curitiba',             'PR'],
        '81' => [-25.4297, -49.2711, 'Curitiba',             'PR'],
        '82' => [-25.4297, -49.2711, 'Curitiba',             'PR'],
        '83' => [-25.4297, -49.2711, 'Curitiba',             'PR'],
        '84' => [-25.0993, -50.1620, 'Ponta Grossa',         'PR'],
        '85' => [-25.3844, -51.4627, 'Guarapuava',           'PR'],
        '86' => [-23.3045, -51.1696, 'Londrina',             'PR'],
        '87' => [-23.4273, -51.9375, 'Maringá',              'PR'],
        // Santa Catarina
        '88' => [-27.5935, -48.5580, 'Florianópolis',        'SC'],
        '89' => [-26.9195, -49.0661, 'Blumenau',             'SC'],
        // São Paulo
        '01' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '02' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '03' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '04' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '05' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '06' => [-23.5089, -46.8483, 'Osasco',               'SP'],
        '07' => [-23.4736, -46.5337, 'Guarulhos',            'SP'],
        '08' => [-23.5505, -46.6333, 'São Paulo',            'SP'],
        '09' => [-23.6543, -46.5283, 'Santo André',          'SP'],
        '11' => [-23.9541, -46.3337, 'Santos',               'SP'],
        '12' => [-23.1896, -45.8841, 'São José dos Campos',  'SP'],
        '13' => [-22.9056, -47.0608, 'Campinas',             'SP'],
        '14' => [-21.1775, -47.8101, 'Ribeirão Preto',       'SP'],
        '15' => [-20.8197, -49.3795, 'São José do Rio Preto','SP'],
        // Rio de Janeiro
        '20' => [-22.9068, -43.1729, 'Rio de Janeiro',       'RJ'],
        '21' => [-22.9068, -43.1729, 'Rio de Janeiro',       'RJ'],
        '22' => [-22.9068, -43.1729, 'Rio de Janeiro',       'RJ'],
        '23' => [-22.9068, -43.1729, 'Rio de Janeiro',       'RJ'],
        '24' => [-22.9035, -43.0812, 'Niterói',              'RJ'],
        '25' => [-22.7526, -43.3401, 'Duque de Caxias',      'RJ'],
        '26' => [-22.8038, -43.2519, 'Nova Iguaçu',          'RJ'],
        '27' => [-22.5249, -44.1003, 'Volta Redonda',        'RJ'],
        '28' => [-22.0193, -42.0278, 'Campos dos Goytacazes','RJ'],
        // Espírito Santo
        '29' => [-20.3222, -40.3381, 'Vitória',              'ES'],
        // Minas Gerais
        '30' => [-19.9191, -43.9386, 'Belo Horizonte',       'MG'],
        '31' => [-19.9191, -43.9386, 'Belo Horizonte',       'MG'],
        '32' => [-19.9678, -44.1981, 'Contagem',             'MG'],
        '33' => [-19.7781, -43.8535, 'Sabará',               'MG'],
        '36' => [-21.7636, -43.3496, 'Juiz de Fora',         'MG'],
        '38' => [-18.9188, -48.2773, 'Uberlândia',           'MG'],
        // Bahia
        '40' => [-12.9714, -38.5014, 'Salvador',             'BA'],
        '41' => [-12.9714, -38.5014, 'Salvador',             'BA'],
        '44' => [-12.2013, -38.9656, 'Feira de Santana',     'BA'],
        '45' => [-14.8603, -40.8450, 'Vitória da Conquista', 'BA'],
        // Sergipe
        '49' => [-10.9472, -37.0731, 'Aracaju',              'SE'],
        // Pernambuco
        '50' => [ -8.0476, -34.8770, 'Recife',               'PE'],
        '51' => [ -8.0476, -34.8770, 'Recife',               'PE'],
        '52' => [ -8.1132, -34.9056, 'Olinda',               'PE'],
        '55' => [ -7.9030, -38.3556, 'Caruaru',              'PE'],
        // Alagoas
        '57' => [ -9.6658, -35.7350, 'Maceió',               'AL'],
        // Paraíba
        '58' => [ -7.1195, -34.8450, 'João Pessoa',          'PB'],
        // Rio Grande do Norte
        '59' => [ -5.7945, -35.2110, 'Natal',                'RN'],
        // Ceará
        '60' => [ -3.7172, -38.5433, 'Fortaleza',            'CE'],
        '61' => [ -3.7172, -38.5433, 'Fortaleza',            'CE'],
        '62' => [ -4.0347, -38.9982, 'Caucaia',              'CE'],
        '63' => [ -7.2154, -39.3182, 'Juazeiro do Norte',    'CE'],
        // Piauí
        '64' => [ -5.0892, -42.8019, 'Teresina',             'PI'],
        // Maranhão
        '65' => [ -2.5297, -44.3028, 'São Luís',             'MA'],
        // Pará / Amapá
        '66' => [ -1.4558, -48.5039, 'Belém',                'PA'],
        '67' => [ -1.4558, -48.5039, 'Belém',                'PA'],
        '68' => [ -1.4558, -48.5039, 'Belém',                'PA'],
        // Amazonas / Roraima / Acre / Rondônia
        '69' => [ -3.1019, -60.0250, 'Manaus',               'AM'],
        // Distrito Federal
        '70' => [-15.7801, -47.9292, 'Brasília',             'DF'],
        '71' => [-15.7801, -47.9292, 'Brasília',             'DF'],
        '72' => [-15.7801, -47.9292, 'Brasília',             'DF'],
        '73' => [-15.7801, -47.9292, 'Brasília',             'DF'],
        // Goiás
        '74' => [-16.6864, -49.2643, 'Goiânia',              'GO'],
        '75' => [-16.6864, -49.2643, 'Goiânia',              'GO'],
        // Tocantins
        '77' => [-10.1841, -48.3336, 'Palmas',               'TO'],
        // Mato Grosso
        '78' => [-15.6010, -56.0974, 'Cuiabá',               'MT'],
        // Mato Grosso do Sul
        '79' => [-20.4697, -54.6201, 'Campo Grande',         'MS'],
        // Rio Grande do Sul
        '90' => [-30.0346, -51.2177, 'Porto Alegre',         'RS'],
        '91' => [-30.0346, -51.2177, 'Porto Alegre',         'RS'],
        '92' => [-29.6844, -51.1383, 'Canoas',               'RS'],
        '93' => [-29.6896, -51.5079, 'Novo Hamburgo',        'RS'],
        '95' => [-29.1678, -51.1794, 'Caxias do Sul',        'RS'],
        '96' => [-31.7654, -52.3376, 'Pelotas',              'RS'],
        '97' => [-29.6868, -53.8023, 'Santa Maria',          'RS'],
        // Rondônia
        '76' => [ -8.7612, -63.9039, 'Porto Velho',          'RO'],
    ];

    // Tenta do prefixo mais longo (mais preciso) ao mais curto (fallback)
    for ($len = 5; $len >= 2; $len--) {
        $prefixo = substr($cep, 0, $len);
        if (isset($base[$prefixo])) {
            return $base[$prefixo]; // [lat, lon, cidade, estado]
        }
    }

    return null; // CEP fora da base
}

// ── Fórmula de Haversine ───────────────────────────────────────────────────────
// Calcula a distância em km entre dois pontos geográficos.
// Raio médio da Terra: 6371 km.
function calcularDistanciaKm($lat1, $lon1, $lat2, $lon2) {
    $raioTerra = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($raioTerra * $c, 2);
}