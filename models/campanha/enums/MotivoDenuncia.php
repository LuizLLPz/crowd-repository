<?php

namespace models\campanha\enums;

enum MotivoDenuncia: string
{
    case FRAUDE_ENGANOSO = 'FRDE';
    case DIREITOS_AUTORAIS = 'COPY';
    case CONTEUDO_INAPROPRIADO = 'ABUS';
    case SPAM_PUBLICIDADE = 'SPAM';
    case RISCO_SEGURANCA = 'SECU';
    case INFO_FALSAS = 'INFO';
    case OUTRO = 'OTHR';

    public function getLabel(): string
    {
        return match ($this) {
            self::FRAUDE_ENGANOSO => 'Fraude ou Campanha Enganosa',
            self::DIREITOS_AUTORAIS => 'Violação de Direitos Autorais / Propriedade Intelectual',
            self::CONTEUDO_INAPROPRIADO => 'Conteúdo Inapropriado ou Ofensivo',
            self::SPAM_PUBLICIDADE => 'Spam ou Publicidade',
            self::RISCO_SEGURANCA => 'Risco de Segurança (Phishing/Malware)',
            self::INFO_FALSAS => 'Informações Falsas ou Irreais',
            self::OUTRO => 'Outro Motivo',
        };
    }
}