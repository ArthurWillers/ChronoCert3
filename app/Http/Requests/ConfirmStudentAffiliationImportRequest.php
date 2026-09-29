<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmStudentAffiliationImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('importStudents', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
        ];
    }
}
