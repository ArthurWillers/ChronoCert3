<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('updateIdentity', $user) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', new ValidCpf, Rule::unique(User::class, 'cpf')->ignore($user)],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
            'current_password' => ['required', 'current_password:web'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')) ?? '',
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }
}
