<?php

namespace api\controllers;

use models\campanha\Doacao;
use models\campanha\Campanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;
use services\campanha\DoacaoService;
use services\integrations\stripe\StripeService;
use Stripe\Checkout\Session;

class DoacaoController extends ControllerBase
{
    private StripeService $stripeService;

    public function __construct()
    {
        parent::__construct();
        $this->stripeService = new StripeService();
    }

    #[HttpPost('/doacao/checkout')]
    public function criarDoacao(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json);

            $doacao = new Doacao();
            $doacao->idCampanha = $data->idCampanha;
            $doacao->valor = $data->valor;

            $doacao->idUsuario = self::$usuarioAutenticado->idUsuario;

            $campanha = Campanha::obter_campanha($doacao->idCampanha);
            if (!$campanha) {
                Http::HttpResponse(404, "Campanha não encontrada.");
            }
            $campanhaObj = new Campanha();
            foreach ($campanha as $key => $value) {
                if (property_exists($campanhaObj, $key)) {
                    $campanhaObj->$key = $value;
                }
            }

            $checkoutSession = $this->stripeService->createCheckoutSession($doacao, $campanhaObj);

            Http::HttpResponse(200, "Sessão de checkout criada com sucesso", ['sessionId' => $checkoutSession->id]);
        } catch (\Exception $e) {
            Http::HttpResponse(500, "Erro ao criar a sessão de checkout: " . $e->getMessage());
        }
    }

    #[HttpGet('/doacao/detalhes-checkout')]
    public function getCheckoutDetails(): void
    {
        $sessionId = $_GET['session_id'] ?? null;

        if (!$sessionId) {
            Http::HttpResponse(400, "ID da sessão não fornecido.");
            return;
        }

        try {
            $session = Session::retrieve($sessionId, [
                'expand' => ['payment_intent', 'line_items.data.price.product']
            ]);

            $lineItem = $session->line_items->data[0] ?? null;

            $details = [
                'idCampanha' => $session->metadata->idCampanha,
                'nomeCampanha' => $lineItem ? $lineItem->price->product->name : 'N/A',
                'valorTotal' => $session->amount_total,
                'moeda' => $session->currency,
                'stripeTransactionId' => is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent->id,
            ];

            Http::HttpResponse(200, "Detalhes da doação recuperados com sucesso.", $details);
        } catch (\Exception $e) {
            Http::HttpResponse(500, "Erro ao buscar detalhes da sessão de checkout: " . $e->getMessage());
        }
    }

    #[HttpPost('/doacao/confirm', auth: false)]
    public function confirm(): void
    {
        $payload = @file_get_contents('php://input');
        $event = json_decode($payload);

        if ($event && $event->type == 'checkout.session.completed') {
            $session = $event->data->object;

            if ($session->payment_status == 'paid') {
                try {
                    $doacao = new Doacao();
                    $doacao->idCampanha = $session->metadata->idCampanha;
                    $doacao->idUsuario = $session->metadata->idUsuario;
                    $doacao->valor = $session->amount_total;
                    $doacao->stripeTransactionId = $session->payment_intent;
                    $doacao->status = 'completed';

                    DoacaoService::criarDoacao($doacao);

                    Http::HttpResponse(200, "Doação confirmada com sucesso.");
                } catch (\Exception $e) {
                    Http::HttpResponse(500, "Erro ao salvar a doação: " . $e->getMessage());
                }
            } else {
                Http::HttpResponse(400, "O pagamento não foi concluído.");
            }
        } else {
            Http::HttpResponse(400, "Evento de webhook inválido.");
        }
    }
}
