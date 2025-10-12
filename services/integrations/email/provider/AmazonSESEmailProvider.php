<?php

namespace services\integrations\email\provider;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use models\core\Excecao;
use services\integrations\email\EmailProviderInterface;

class AmazonSESEmailProvider implements EmailProviderInterface
{
    private SesClient $sesClient;
    private string $from_address;

    public function __construct(array $config)
    {
        if (empty($config['aws_access_key_id']) || empty($config['aws_secret_access_key']) || empty($config['aws_region'])) {
            throw new \Exception('Credenciais da AWS para o SES não estão configuradas corretamente.');
        }

        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region'  => $config['aws_region'],
            'credentials' => [
                'key'    => $config['aws_access_key_id'],
                'secret' => $config['aws_secret_access_key'],
            ],
        ]);

        $this->from_address = $config['from_address'] ?? '';
    }

    public function enviar(string $destinatario, string $nomeDestinatario, string $assunto, string $mensagem): bool
    {
        $charset = 'UTF-8';

        try {
            $result = $this->sesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$destinatario],
                ],
                'Source' => $this->from_address,
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => $charset,
                            'Data' => $mensagem,
                        ],
                        'Text' => [
                            'Charset' => $charset,
                            'Data' => strip_tags($mensagem),
                        ],
                    ],
                    'Subject' => [
                        'Charset' => $charset,
                        'Data' => $assunto,
                    ],
                ],
            ]);
            return true;
        } catch (AwsException $e) {
            Excecao::salvar($e);
            error_log("Erro ao enviar email via AWS SES: " . $e->getAwsErrorMessage());
            return false;
        } catch (\Exception $e) {
            Excecao::salvar($e);
            error_log("Erro ao enviar email via AWS SES (Exception): " . $e->getMessage());
            return false;
        }
    }
}