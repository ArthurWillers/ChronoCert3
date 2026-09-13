<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Rejected = 'rejected';
    case Approved = 'approved';

    /**
     * Obter o rótulo do status em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Enviado',
            self::UnderReview => 'Em análise',
            self::Rejected => 'Rejeitado',
            self::Approved => 'Aprovado',
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
