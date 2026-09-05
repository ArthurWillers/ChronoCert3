<?php

namespace App\Http\Requests;

use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserAffiliationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('addAffiliation', $user) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
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
            $user = $this->route('user');

            if ($type === null || ! $user instanceof User) {
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
                $hasRegistration = Affiliation::query()
                    ->active()
                    ->where('type', AffiliationType::Student)
                    ->where('course_id', $this->integer('course_id'))
                    ->where('registration_number', $this->string('registration_number')->toString())
                    ->exists();
                $hasAffiliation = Affiliation::query()
                    ->active()
                    ->where('user_id', $user->getKey())
                    ->where('type', $type)
                    ->where('course_id', $this->integer('course_id'))
                    ->exists();

                if ($hasRegistration) {
                    $validator->errors()->add('registration_number', 'Esta matrícula já possui um vínculo ativo no curso selecionado.');
                }

                if ($hasAffiliation) {
                    $validator->errors()->add('course_id', 'Este usuário já possui um vínculo discente ativo neste curso.');
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
