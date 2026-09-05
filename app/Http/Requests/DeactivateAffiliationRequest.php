<?php

namespace App\Http\Requests;

use App\Models\Affiliation;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateAffiliationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $affiliation = $this->route('affiliation');

        return $affiliation instanceof Affiliation && ($this->user()?->can('deactivate', $affiliation) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
