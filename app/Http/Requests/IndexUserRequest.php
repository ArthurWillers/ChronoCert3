<?php

namespace App\Http\Requests;

use App\Enums\AffiliationType;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', Rule::exists(Course::class, 'id')],
            'type' => ['nullable', Rule::in(AffiliationType::values())],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search')) ? trim((string) $this->input('search')) : null,
            'course_id' => filled($this->input('course_id')) ? (int) $this->input('course_id') : null,
            'type' => filled($this->input('type')) ? (string) $this->input('type') : null,
            'status' => filled($this->input('status')) ? (string) $this->input('status') : null,
        ]);
    }
}
