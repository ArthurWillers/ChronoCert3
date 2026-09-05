<?php

namespace App\Http\Requests;

use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAffiliationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $affiliation = $this->route('affiliation');

        return $affiliation instanceof Affiliation && ($this->user()?->can('update', $affiliation) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'operational_email' => ['required', 'email:rfc', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'course_id' => ['nullable', 'integer', Rule::exists(Course::class, 'id')],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $affiliation = $this->route('affiliation');

            if (! $affiliation instanceof Affiliation) {
                return;
            }

            if ($affiliation->type === AffiliationType::Student && ! $this->filled('registration_number')) {
                $validator->errors()->add('registration_number', 'A matrícula é obrigatória para vínculos discentes.');
            }

            if ($affiliation->type !== AffiliationType::Student && $this->filled('registration_number')) {
                $validator->errors()->add('registration_number', 'A matrícula é exclusiva de vínculos discentes.');
            }

            if ($affiliation->type === AffiliationType::Coordinator && ! $this->filled('course_id')) {
                $validator->errors()->add('course_id', 'Selecione o curso de atuação da coordenação.');
            }

            if ($affiliation->type !== AffiliationType::Coordinator && $this->filled('course_id')) {
                $validator->errors()->add('course_id', 'O curso deste tipo de vínculo não pode ser alterado.');
            }

            if ($affiliation->type === AffiliationType::Student && $this->filled('registration_number')) {
                $exists = Affiliation::query()
                    ->active()
                    ->where('type', AffiliationType::Student)
                    ->where('course_id', $affiliation->course_id)
                    ->where('registration_number', $this->string('registration_number')->toString())
                    ->whereKeyNot($affiliation->getKey())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('registration_number', 'Esta matrícula já possui um vínculo ativo no curso selecionado.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'operational_email' => Str::lower(trim((string) $this->input('operational_email'))),
            'registration_number' => filled($this->input('registration_number')) ? trim((string) $this->input('registration_number')) : null,
        ]);
    }
}
