<?php

namespace App\Enums;

enum AffiliationType: string
{
    case Student = 'student';
    case Coordinator = 'coordinator';
    case Administrator = 'administrator';

    /**
     * Obter o rótulo do tipo de vínculo em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Discente',
            self::Coordinator => 'Coordenação',
            self::Administrator => 'Administrador',
        };
    }

    /**
     * Retornar opções no formato [valor => rótulo].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
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
