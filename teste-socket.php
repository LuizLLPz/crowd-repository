<?php
require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

echo "=======================================\n";
echo "INICIANDO TESTE DA PONTE DE SOCKET\n";
echo "=======================================\n\n";

$url = 'http://127.0.0.1:8081/notify';

$payloadDeTeste = [
    [
        'idNotificacao' => 999,
        'idUsuario' => 40,
        'titulo' => 'TESTE DE SOCKET',
        'descricao' => 'Testando socket!',
        'tipoNotificacao' => 'teste',
        'lido' => false,
        'idItem' => 123
    ]
];

try {
    $client = new Client(['timeout' => 5.0, 'connect_timeout' => 5.0]);

    echo "Enviando requisicao POST para: {$url}\n";
    echo "Payload: " . json_encode($payloadDeTeste) . "\n\n";

    $response = $client->post($url, ['json' => $payloadDeTeste]);

    echo "---------------------------------------\n";
    echo "✅ SUCESSO! A PONTE FUNCIONA!\n";
    echo "---------------------------------------\n";
    echo "Resposta recebida do socket-server:\n";
    echo "Status HTTP: " . $response->getStatusCode() . "\n";
    echo "Corpo da Resposta: " . $response->getBody() . "\n";

} catch (\Throwable $e) {
    echo "\n---------------------------------------\n";
    echo "❌ FALHA NA COMUNICACAO!\n";
    echo "---------------------------------------\n";
    echo "Motivo: " . $e->getMessage() . "\n";
}