<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteAccReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof AccReview
            && ($this->user()?->can('update', $review) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isApproval = $this->input('decision') === 'approve';

        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'original_title' => [Rule::requiredIf($isApproval), 'nullable', 'string', 'max:255'],
            'normalized_title' => [Rule::requiredIf($isApproval), 'nullable', 'string', 'max:255'],
            'certificate_hours' => [Rule::requiredIf($isApproval), 'nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'is_area_related' => [Rule::requiredIf($isApproval), 'boolean'],
            'acc_category_id' => [Rule::requiredIf($isApproval), 'nullable', 'integer', 'exists:acc_categories,id'],
            'rejection_reason' => [Rule::requiredIf(! $isApproval), 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_area_related' => $this->boolean('is_area_related'),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'original_title.required' => 'Informe o título identificado no documento.',
            'normalized_title.required' => 'Informe o título acadêmico da atividade.',
            'certificate_hours.required' => 'Informe a carga horária indicada no certificado.',
            'certificate_hours.gt' => 'A carga horária do certificado deve ser maior que zero.',
            'acc_category_id.required' => 'Selecione a categoria aplicada.',
            'rejection_reason.required' => 'Informe o motivo da rejeição.',
        ];
    }
}
