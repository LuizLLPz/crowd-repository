<?php

namespace services\integrations\stripe;

use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Checkout\Session;
use models\campanha\Doacao;
use models\campanha\Campanha;

class StripeService
{
    public function __construct()
    {
    }

    public static function createAccount(string $email): Account
    {
        return Account::create([
            'type' => 'express',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);
    }

    public static function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): AccountLink
    {
        return AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);
    }

    public static function createCheckoutSession(Doacao $doacao, Campanha $campanha): Session
    {
        $donoCampanha = \models\social\Usuario::buscar_usuario($campanha->idUsuario);

        if (empty($donoCampanha->stripe_account_id)) {
            throw new \Exception("O dono da campanha não configurou a conta Stripe para recebimentos.");
        }

        $platformFeeRate = 0.05;
        $platformFeeAmount = (int)($doacao->valor * $platformFeeRate);

        if ($platformFeeAmount < 50) {
            $platformFeeAmount = 50;
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => [
                        'name' => 'Doação para ' . $campanha->titulo,
                    ],
                    'unit_amount' => $doacao->valor,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $_ENV['CORS_ORIGIN'] . '/pagamento/sucesso?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $_ENV['CORS_ORIGIN'] . '/pagamento/cancelado',
            'payment_intent_data' => [
                'application_fee_amount' => $platformFeeAmount,
                'transfer_data' => [
                    'destination' => $donoCampanha->stripe_account_id,
                ],
            ],
            'metadata' => [
                'idCampanha' => $doacao->idCampanha,
                'idUsuario' => $doacao->idUsuario,
            ],
        ]);
    }
}