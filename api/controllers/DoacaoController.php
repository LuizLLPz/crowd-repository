<?php

namespace api\controllers;

use models\Doacao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;
use services\integrations\stripe\StripeService;

class DoacaoController extends ControllerBase
{
    private StripeService $stripeService;

    public function __construct()
    {
        parent::__construct();
        $this->stripeService = new StripeService();
    }

    #[HttpPost('/doacao')]
    public function criarDoacao(Doacao $doacao): void
    {
        try {
            $doacao->idUsuario = self::$usuarioAutenticado->idUsuario;
            $checkoutSession = $this->stripeService->createCheckoutSession($doacao);

            Http::HttpResponse(200, "Sessão de checkout criada com sucesso", ['checkout_url' => $checkoutSession->url]);
        } catch (\Exception $e) {
            Http::HttpResponse(500, "Erro ao criar a sessão de checkout: " . $e->getMessage());
        }
    }

    #[HttpPost('/doacao/confirm')]
    public function confirm(): void
    {
        $sessionId = $_POST['session_id'] ?? null;

        if (!$sessionId) {
            Http::HttpResponse(400, "ID da sessão de checkout não fornecido.");
            return;
        }

        try {
            $session = $this->stripeService->retrieveCheckoutSession($sessionId);

            if ($session->payment_status == 'paid') {
                $doacao = new Doacao();
                $doacao->idCampanha = $session->metadata->idCampanha;
                $doacao->idUsuario = $session->metadata->idUsuario;
                $doacao->valor = $session->amount_total / 100;
                $doacao->stripeTransactionId = $session->payment_intent;
                $doacao->status = 'completed';

                \services\campanha\DoacaoService::criarDoacao($doacao);

                Http::HttpResponse(200, "Doação confirmada com sucesso.");
            } else {
                Http::HttpResponse(400, "O pagamento não foi concluído.");
            }
        } catch (\Exception $e) {
            Http::HttpResponse(500, "Erro ao confirmar a doação: " . $e->getMessage());
        }
    }
}
