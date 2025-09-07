<?php

namespace services\integrations\email;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host       = $_ENV['MAIL_HOST'];
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['MAIL_USERNAME'];
        $this->mail->Password   = $_ENV['MAIL_PASSWORD'];
        $this->mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $this->mail->Port       = $_ENV['MAIL_PORT'];

        $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $this->mail->isHTML(true);
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
            error_log("Erro ao enviar email: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
