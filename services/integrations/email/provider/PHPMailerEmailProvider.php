<?php

namespace services\integrations\email\provider;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use models\core\Excecao;
use services\integrations\email\EmailProviderInterface;

class PHPMailerEmailProvider implements EmailProviderInterface
{
    private PHPMailer $mail;

    public function __construct(array $config)
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host       = $config['host'] ?? '';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $config['username'] ?? '';
        $this->mail->Password   = $config['password'] ?? '';
        $this->mail->SMTPSecure = $config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = $config['port'] ?? 587;

        $this->mail->setFrom($config['from_address'] ?? '', $config['from_name'] ?? '');
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }

    public function enviar(string $destinatario, string $nomeDestinatario, string $assunto, string $mensagem): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($destinatario, $nomeDestinatario);
            $this->mail->Subject = $assunto;
            $this->mail->Body    = $mensagem;
            $this->mail->AltBody = strip_tags($mensagem);

            return $this->mail->send();
        } catch (Exception $e) {
            Excecao::salvar($e);
            error_log("Erro ao enviar email via PHPMailer/SMTP: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
