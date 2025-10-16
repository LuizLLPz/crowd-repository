<?php

namespace api\controllers;

use models\social\Usuario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;
use services\integrations\stripe\StripeService;

class StripeController extends ControllerBase
{
    #[HttpPost('/usuario/stripe/onboarding')]
    public function onboarding(): void
    {
        $usuario = Usuario::buscar_usuario_por_id(ControllerBase::$usuarioAutenticado->idUsuario);

        if (!$usuario || !$usuario->idUsuario) {
            Http::HttpResponse(401, "Usuário não autenticado.");
        }

        try {
            if (empty($usuario->stripe_account_id)) {
                $account = StripeService::createAccount($usuario->email);
                $usuario->stripe_account_id = $account->id;
                Usuario::atualizarStripeAccountId($usuario->idUsuario, $account->id);
            }

            // 2. Generate Account Link
            $refreshUrl = $_ENV['CORS_ORIGIN'] . '/perfil/recebimentos?reauth=true';
            $returnUrl = $_ENV['CORS_ORIGIN'] . '/perfil/recebimentos?success=true';

            $accountLink = StripeService::createAccountLink(
                $usuario->stripe_account_id,
                $refreshUrl,
                $returnUrl
            );

            Http::HttpResponse(200, "Link de onboarding gerado com sucesso.", ['url' => $accountLink->url]);

        } catch (
Exception $e) {
            Http::HttpResponse(500, "Erro ao iniciar onboarding Stripe: " . $e->getMessage());
        }
    }
}
