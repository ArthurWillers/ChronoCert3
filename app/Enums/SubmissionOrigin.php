<?php

namespace App\Enums;

enum SubmissionOrigin: string
{
    case Student = 'student';
    case Coordinator = 'coordinator';

    /**
     * Obter o rótulo da origem em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Discente',
            self::Coordinator => 'Coordenação',
        };
    }

    /**
     * Retornar todos os valores persistidos do enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
