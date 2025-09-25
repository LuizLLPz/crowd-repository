<?php

namespace services\integrations\stripe;

use models\Doacao;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    public function createCheckoutSession(Doacao $doacao): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => [
                        'name' => 'Doação para a campanha',
                    ],
                    'unit_amount' => $doacao->valor * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $_ENV['STRIPE_SUCCESS_URL'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $_ENV['STRIPE_CANCEL_URL'],
            'metadata' => [
                'idCampanha' => $doacao->idCampanha,
                'idUsuario' => $doacao->idUsuario
            ]
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }
}
