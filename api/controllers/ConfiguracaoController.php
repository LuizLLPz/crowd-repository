<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\utils\Http;
use services\core\ConfigService;

class ConfiguracaoController extends ControllerBase
{
    #[HttpGet('/configuracoes', funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function get_configs(): void
    {
        $smtpConfig = ConfigService::getProviderConfig('smtp');
        $sendgridConfig = ConfigService::getProviderConfig('sendgrid');
        $sesConfig = ConfigService::getProviderConfig('ses');

        unset($smtpConfig['password']);
        unset($sendgridConfig['api_key']);
        unset($sesConfig['aws_secret_access_key']);

        $response = [
            'email_provider' => ConfigService::get('email', 'provider') ?? 'smtp',

            'smtp_host' => $smtpConfig['host'] ?? '',
            'smtp_port' => $smtpConfig['port'] ?? '',
            'smtp_username' => $smtpConfig['username'] ?? '',
            'smtp_encryption' => $smtpConfig['encryption'] ?? '',
            'smtp_from_address' => $smtpConfig['from_address'] ?? '',
            'smtp_from_name' => $smtpConfig['from_name'] ?? '',

            'sendgrid_from_address' => $sendgridConfig['from_address'] ?? '',
            'sendgrid_from_name' => $sendgridConfig['from_name'] ?? '',

            'aws_access_key_id' => $sesConfig['aws_access_key_id'] ?? '',
            'aws_region' => $sesConfig['aws_region'] ?? '',
            'ses_from_address' => $sesConfig['from_address'] ?? '',
        ];

        Http::HttpResponse(200, 'Configurações recuperadas com sucesso', $response);
    }

    #[HttpPut('/configuracoes', funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function save_configs(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Http::HttpResponse(400, 'JSON inválido');
        }

        try {
            ConfigService::set('email', 'provider', $data['email_provider'] ?? 'smtp', false);

            ConfigService::set('smtp', 'host', $data['smtp_host'] ?? null, false);
            ConfigService::set('smtp', 'port', $data['smtp_port'] ?? null, false);
            ConfigService::set('smtp', 'username', $data['smtp_username'] ?? null, true);
            ConfigService::set('smtp', 'encryption', $data['smtp_encryption'] ?? null, false);
            ConfigService::set('smtp', 'from_address', $data['smtp_from_address'] ?? null, false);
            ConfigService::set('smtp', 'from_name', $data['smtp_from_name'] ?? null, false);
            if (!empty($data['smtp_password'])) {
                ConfigService::set('smtp', 'password', $data['smtp_password'], true);
            }

            ConfigService::set('sendgrid', 'from_address', $data['sendgrid_from_address'] ?? null, false);
            ConfigService::set('sendgrid', 'from_name', $data['sendgrid_from_name'] ?? null, false);
            if (!empty($data['sendgrid_api_key'])) {
                ConfigService::set('sendgrid', 'api_key', $data['sendgrid_api_key'], true);
            }

            ConfigService::set('ses', 'aws_access_key_id', $data['aws_access_key_id'] ?? null, true);
            ConfigService::set('ses', 'aws_region', $data['aws_region'] ?? null, false);
            ConfigService::set('ses', 'from_address', $data['ses_from_address'] ?? null, false);
            if (!empty($data['aws_secret_access_key'])) {
                ConfigService::set('ses', 'aws_secret_access_key', $data['aws_secret_access_key'], true);
            }

            Http::HttpResponse(200, 'Configurações salvas com sucesso');

        } catch (\Exception $e) {
            Http::HttpResponse(500, 'Erro ao salvar configurações: ' . $e->getMessage());
        }
    }
}
