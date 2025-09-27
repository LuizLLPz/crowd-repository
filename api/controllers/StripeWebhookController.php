<?php

namespace api\controllers;

use models\campanha\Doacao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpPost;
use services\campanha\DoacaoService;
use Stripe\Webhook;

class StripeWebhookController extends ControllerBase
{
    #[HttpPost('/stripe/webhook')]
    public function handleWebhook(): void
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;

            $doacao = new Doacao();
            $doacao->idCampanha = $session->metadata->idCampanha;
            $doacao->idUsuario = $session->metadata->idUsuario;
            $doacao->valor = $session->amount_total / 100;
            $doacao->stripeTransactionId = $session->payment_intent;
            $doacao->status = 'completed';

            DoacaoService::criarDoacao($doacao);
        }

        http_response_code(200);
    }
}
