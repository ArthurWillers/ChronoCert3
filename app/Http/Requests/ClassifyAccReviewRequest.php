<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'original_hours' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'approved_hours' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'acc_category_id' => ['required', 'integer', 'exists:acc_categories,id'],
            'classification_justification' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $titleChanged = trim((string) $this->input('original_title')) !== trim((string) $this->input('normalized_title'));
            $originalHours = $this->input('original_hours');
            $hoursChanged = filled($originalHours)
                && (float) $originalHours !== (float) $this->input('approved_hours');

            if (($titleChanged || $hoursChanged) && blank($this->input('classification_justification'))) {
                $validator->errors()->add(
                    'classification_justification',
                    'Informe a justificativa para a correção do título ou das horas.',
                );
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'original_title.required' => 'Informe o título identificado no comprovante.',
            'normalized_title.required' => 'Informe o título acadêmico da atividade.',
            'approved_hours.required' => 'Informe as horas que serão aproveitadas.',
            'approved_hours.gt' => 'As horas aprovadas devem ser maiores que zero.',
            'acc_category_id.required' => 'Selecione a categoria aplicada.',
            'acc_category_id.exists' => 'A categoria selecionada não existe.',
        ];
    }
}
