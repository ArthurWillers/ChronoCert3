<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';

    /**
     * Obter o rótulo do status da submissão em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Enviado',
            self::UNDER_REVIEW => 'Em análise',
            self::REJECTED => 'Rejeitado',
            self::APPROVED => 'Aprovado',
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
