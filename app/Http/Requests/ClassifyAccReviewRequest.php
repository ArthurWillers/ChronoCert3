<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use Illuminate\Foundation\Http\FormRequest;

class ClassifyAccReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof AccReview
            && ($this->user()?->can('update', $review) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'original_title' => ['required', 'string', 'max:255'],
            'normalized_title' => ['required', 'string', 'max:255'],
            'certificate_hours' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'is_area_related' => ['required', 'boolean'],
            'acc_category_id' => ['required', 'integer', 'exists:acc_categories,id'],
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
            'acc_category_id.exists' => 'A categoria selecionada não existe.',
        ];
    }
}
