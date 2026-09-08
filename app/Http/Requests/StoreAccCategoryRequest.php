<?php

namespace App\Http\Requests;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Models\AccCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAny', AccCategory::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $category = $this->route('category');
        $courseId = $category instanceof AccCategory ? $category->course_id : app(ActiveAffiliationContext::class)->for($this->user())?->course_id;
        $nameRules = ['required', 'string', 'max:255'];

        if (! $category instanceof AccCategory || $category->deactivated_at === null) {
            $nameRules[] = Rule::unique(AccCategory::class, 'name')->where('course_id', $courseId)
                ->whereNull('deactivated_at')->ignore($category instanceof AccCategory ? $category : null);
        }

        return [
            'course_id' => ['prohibited'],
            'name' => $nameRules,
            'description' => ['nullable', 'string'],
            'max_hours' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999.99'],
            'accepts_multiple' => ['prohibited'],
            'document_required' => ['prohibited'],
            'allowed_mime_types' => ['prohibited'],
            'max_file_size_bytes' => ['prohibited'],
            'guidance' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['name', 'max_hours'] as $field) {
            if (is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }
        if (isset($data['max_hours'])) {
            $data['max_hours'] = str_replace(',', '.', $data['max_hours']);
        }
        $this->merge($data);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'course_id' => 'curso', 'name' => 'nome', 'description' => 'descrição',
            'max_hours' => 'limite de horas', 'accepts_multiple' => 'múltiplas submissões',
            'document_required' => 'exigência de documento', 'allowed_mime_types' => 'formatos aceitos',
            'allowed_mime_types.*' => 'tipo MIME', 'max_file_size_bytes' => 'tamanho máximo em bytes', 'guidance' => 'orientação',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma categoria ativa com este nome neste curso.',
            'course_id.prohibited' => 'O curso é definido pelo vínculo de coordenação selecionado.',
        ];
    }
}
