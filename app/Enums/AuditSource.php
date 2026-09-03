<?php

namespace App\Enums;

enum AuditSource: string
{
    case Web = 'web';
    case Console = 'console';
    case Scheduled = 'scheduled';

    /**
     * Obter o rótulo da origem do evento em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::Web => 'Interface web',
            self::Console => 'Linha de comando',
            self::Scheduled => 'Rotina agendada',
        };
    }
}
