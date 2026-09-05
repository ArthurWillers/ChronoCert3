<?php

namespace App\Http\Requests;

use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
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
            'cpf' => ['required', 'string', new ValidCpf, Rule::unique(User::class, 'cpf')],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')],
            'affiliation_type' => ['required', Rule::in(AffiliationType::values())],
            'course_id' => ['nullable', 'integer', Rule::exists(Course::class, 'id')->whereNull('deactivated_at')],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'operational_email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = AffiliationType::tryFrom((string) $this->input('affiliation_type'));

            if ($type === null) {
                return;
            }

            if ($type === AffiliationType::Administrator && $this->filled('course_id')) {
                $validator->errors()->add('course_id', 'Vínculos de administrador global não possuem curso.');
            }

            if ($type !== AffiliationType::Administrator && ! $this->filled('course_id')) {
                $validator->errors()->add('course_id', 'Selecione o curso do vínculo.');
            }

            if ($type === AffiliationType::Student && ! $this->filled('registration_number')) {
                $validator->errors()->add('registration_number', 'A matrícula é obrigatória para vínculos discentes.');
            }

            if ($type !== AffiliationType::Student && $this->filled('registration_number')) {
                $validator->errors()->add('registration_number', 'A matrícula é exclusiva de vínculos discentes.');
            }

            if ($type === AffiliationType::Student && $this->filled('course_id') && $this->filled('registration_number')) {
                $exists = Affiliation::query()
                    ->active()
                    ->where('type', AffiliationType::Student)
                    ->where('course_id', $this->integer('course_id'))
                    ->where('registration_number', $this->string('registration_number')->toString())
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
            'name' => trim((string) $this->input('name')),
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')) ?? '',
            'email' => Str::lower(trim((string) $this->input('email'))),
            'operational_email' => Str::lower(trim((string) $this->input('operational_email'))),
            'registration_number' => filled($this->input('registration_number')) ? trim((string) $this->input('registration_number')) : null,
        ]);
    }
}
