<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CompleteAccReviewRequest extends ClassifyAccReviewRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isApproval = $this->input('decision') === 'approve';
        $classificationRules = array_map(
            fn (array $rules): array => [Rule::excludeIf(! $isApproval), ...$rules],
            parent::rules(),
        );

        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            ...$classificationRules,
            'rejection_reason' => [Rule::excludeIf($isApproval), 'required', 'string', 'min:5', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'rejection_reason.required' => 'Informe o motivo da rejeição.',
            'rejection_reason.min' => 'O motivo da rejeição deve ter pelo menos 5 caracteres.',
        ];
    }
}
