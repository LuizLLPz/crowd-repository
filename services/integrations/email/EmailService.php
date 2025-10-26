<?php

namespace services\integrations\email;

use services\core\ConfigService;
use services\integrations\email\provider\PHPMailerEmailProvider;
use services\integrations\email\provider\SendGridEmailProvider;
use services\integrations\email\provider\AmazonSESEmailProvider;

class EmailService implements EmailProviderInterface
{
    private EmailProviderInterface $provider;

    public function __construct()
    {
        $providerName = ConfigService::get('email', 'provider') ?? 'smtp';

        if ($providerName === 'sendgrid') {
            $config = ConfigService::getProviderConfig('sendgrid');
            $providerConfig = [
                'api_key' => $config['api_key'] ?? null,
                'from_address' => $config['from_address'] ?? null,
                'from_name' => $config['from_name'] ?? null,
            ];
            $this->provider = new SendGridEmailProvider($providerConfig);
        } elseif ($providerName === 'ses') {
            $config = ConfigService::getProviderConfig('ses');
            $providerConfig = [
                'aws_access_key_id' => $config['aws_access_key_id'] ?? null,
                'aws_secret_access_key' => $config['aws_secret_access_key'] ?? null,
                'aws_region' => $config['aws_region'] ?? null,
                'from_address' => $config['from_address'] ?? null,
            ];
            $this->provider = new AmazonSESEmailProvider($providerConfig);
        } else {
            $config = ConfigService::getProviderConfig('smtp');
            $providerConfig = [
                'host' => $config['host'] ?? null,
                'port' => $config['port'] ?? null,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'encryption' => $config['encryption'] ?? null,
                'from_address' => $config['from_address'] ?? null,
                'from_name' => $config['from_name'] ?? null,
            ];
            $this->provider = new PHPMailerEmailProvider($providerConfig);
        }
    }

    public function enviar(string $destinatario, string $nomeDestinatario, string $assunto, string $mensagem): bool
    {
        return $this->provider->enviar($destinatario, $nomeDestinatario, $assunto, $mensagem);
    }
}
