<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'required_acc_hours' => ['nullable', 'numeric', 'gt:0', 'max:99999.99'],
            'has_area_requirement' => ['nullable', 'boolean'],
            'minimum_area_percentage' => ['nullable', 'numeric', 'gt:0', 'max:100'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('has_area_requirement')) {
                return;
            }

            if (! $this->filled('required_acc_hours')) {
                $validator->errors()->add('required_acc_hours', 'Informe a carga horária total para exigir atividades na área.');
            }

            if (! $this->filled('minimum_area_percentage')) {
                $validator->errors()->add('minimum_area_percentage', 'Informe o percentual mínimo de atividades na área.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $hasAreaRequirement = $this->boolean('has_area_requirement');

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'required_acc_hours' => $this->normalizedDecimal('required_acc_hours'),
            'has_area_requirement' => $hasAreaRequirement,
            'minimum_area_percentage' => $hasAreaRequirement
                ? $this->normalizedDecimal('minimum_area_percentage')
                : null,
        ]);
    }

    private function normalizedDecimal(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        if ($value === '') {
            return null;
        }

        $normalizedValue = str_replace(',', '.', $value);

        return is_numeric($normalizedValue)
            ? number_format((float) $normalizedValue, 2, '.', '')
            : $normalizedValue;
    }
}
