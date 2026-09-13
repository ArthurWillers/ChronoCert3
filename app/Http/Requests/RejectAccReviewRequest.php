<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use Illuminate\Foundation\Http\FormRequest;

class RejectAccReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof AccReview
            && ($this->user()?->can('reject', $review) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Informe o motivo da rejeição.',
            'rejection_reason.min' => 'O motivo da rejeição deve ter pelo menos 5 caracteres.',
        ];
    }
}
