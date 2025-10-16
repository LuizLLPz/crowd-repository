<?php

namespace api\controllers;

use models\campanha\Campanha;
use models\campanha\Doacao;
use models\social\Usuario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpPost;
use services\campanha\DoacaoService;
use services\integrations\email\EmailService;
use Stripe\Webhook;

class StripeWebhookController extends ControllerBase
{
    #[HttpPost('/stripe/webhook', auth: false)]
    public function handleWebhook(): void
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
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
            $doacao->valor = $session->amount_total;
            $doacao->stripeTransactionId = $session->payment_intent;
            $doacao->status = 'completed';

            DoacaoService::criarDoacao($doacao);

            try {
                $idDonoCampanha = Campanha::obter_idUsuario($doacao->idCampanha);

                if ($idDonoCampanha && $idDonoCampanha != $doacao->idUsuario) {
                    $donoCampanha = Usuario::buscar_usuario($idDonoCampanha);
                    $doador = Usuario::buscar_usuario($doacao->idUsuario);
                    $tituloCampanha = Campanha::obter_Titulo($doacao->idCampanha);

                    if ($donoCampanha && $doador) {
                        $emailService = new EmailService();
                        $template = file_get_contents(__DIR__ . '/../../services/integrations/email/templates/nova_doacao.html');

                        $valorFormatado = 'R$ ' . number_format($doacao->valor / 100, 2, ',', '.');
                        $linkCampanha = $_ENV['CORS_ORIGIN'] . "/campanha/{$doacao->idCampanha}";

                        $conteudoEmail = str_replace(
                            ['{nomeDonoCampanha}', '{nomeCampanha}', '{valorDoacao}', '{nomeDoador}', '{linkCampanha}'],
                            [$donoCampanha->nomeUsuario, $tituloCampanha, $valorFormatado, $doador->nomeUsuario, $linkCampanha],
                            $template
                        );

                        $emailService->enviar(
                            $donoCampanha->email,
                            $donoCampanha->nomeUsuario,
                            "Parabéns! Sua campanha {$tituloCampanha} recebeu uma nova doação!",
                            $conteudoEmail
                        );
                    }
                }
            } catch (\Exception $e) {
                error_log("Falha ao enviar e-mail de nova doação: " . $e->getMessage());
            }
        }

        http_response_code(200);
    }
}
