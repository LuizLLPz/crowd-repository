<?php

namespace services\integrations\email\provider;

use SendGrid;
use SendGrid\Mail\Mail;
use models\core\Excecao;
use services\integrations\email\EmailProviderInterface;

class SendGridEmailProvider implements EmailProviderInterface
{
    private SendGrid $sg;
    private string $from_address;
    private string $from_name;

    public function __construct(array $config)
    {
        $apiKey = $config['api_key'] ?? '';
        if (empty($apiKey)) {
            throw new \Exception('SendGrid API Key não configurada.');
        }
        $this->sg = new SendGrid($apiKey);
        $this->from_address = $config['from_address'] ?? '';
        $this->from_name = $config['from_name'] ?? '';
    }

    public function enviar(string $destinatario, string $nomeDestinatario, string $assunto, string $mensagem): bool
    {
        $email = new Mail();
        try {
            $email->setFrom($this->from_address, $this->from_name);
            $email->setSubject($assunto);
            $email->addTo($destinatario, $nomeDestinatario);
            $email->addContent("text/plain", strip_tags($mensagem));
            $email->addContent("text/html", $mensagem);

            $response = $this->sg->send($email);

            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                return true;
            }
            
            $errorBody = $response->body();
            $errorException = new \Exception("Erro ao enviar email via SendGrid (Status: {$response->statusCode()}): " . (is_string($errorBody) ? $errorBody : json_encode($errorBody)));
            Excecao::salvar($errorException);
            error_log($errorException->getMessage());
            return false;
        } catch (\Exception $e) {
            Excecao::salvar($e);
            error_log("Erro ao enviar email via SendGrid (Exception): {$e->getMessage()}");
            return false;
        }
    }
}
