<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Error = 'error';
    case Timeout = 'timeout';

    /**
     * Prioridade de exibição: aprovadas primeiro, depois reprovadas,
     * depois bancos que falharam (erro ou timeout).
     */
    public function rank(): int
    {
        return match ($this) {
            self::Approved => 0,
            self::Rejected => 1,
            self::Error, self::Timeout => 2,
        };
    }
}
