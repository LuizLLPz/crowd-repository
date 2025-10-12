<?php

namespace services\integrations\email;

interface EmailProviderInterface
{
    public function enviar(string $destinatario, string $nomeDestinatario, string $assunto, string $mensagem): bool;
}
