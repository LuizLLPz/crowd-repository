<?php
require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use models\core\Notificacao;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=======================================\n";
echo "INICIANDO TESTE DA PONTE DE SOCKET\n";
echo "=======================================\n\n";

$url = 'http://127.0.0.1:8081/notify';

$novaNotificacao = new Notificacao();
$novaNotificacao->idUsuario = 40;
$novaNotificacao->titulo = 'TESTE DE SOCKET REAL';
$novaNotificacao->descricao = 'Testando o envio de uma notificação persistida!';
$novaNotificacao->tipo = 'teste_real';

try {
    $notificacaoCriada = Notificacao::criar($novaNotificacao);
    echo "Notificação real criada no banco com ID: {$notificacaoCriada->idNotificacao}\n";
} catch (\Throwable $e) {
    echo "\n---------------------------------------\n";
    echo "❌ FALHA AO CRIAR NOTIFICAÇÃO NO BANCO!\n";
    echo "---------------------------------------\n";
    echo "Motivo: " . $e->getMessage() . "\n";
    exit;
}

$payloadDeTeste = [
    [
        'idNotificacao' => $notificacaoCriada->idNotificacao,
        'idUsuario' => $notificacaoCriada->idUsuario,
        'titulo' => $notificacaoCriada->titulo,
        'descricao' => $notificacaoCriada->descricao,
        'tipoNotificacao' => $notificacaoCriada->tipo,
        'lido' => false,
        'idItem' => null
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
    echo "❌ FALHA NA COMUNICACAO COM SOCKET!\n";
    echo "---------------------------------------\n";
    echo "Motivo: " . $e->getMessage() . "\n";
}