<?php

namespace App\Http\Requests;

use App\Enums\AuditEvent;
use App\Models\AuditActivity;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AuditActivity::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'area' => ['nullable', 'string', Rule::in(array_unique(array_map(
                static fn (AuditEvent $event): string => $event->area(),
                AuditEvent::cases(),
            )))],
            'event' => ['nullable', 'string', Rule::in(AuditEvent::values())],
            'causer' => [
                'nullable',
                'string',
                'max:20',
                Rule::when(
                    $this->input('causer') !== null && $this->input('causer') !== 'system',
                    ['integer', Rule::exists(User::class, 'id')],
                ),
            ],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'course_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'oldest'])],
        ];
    }
}
