<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectActiveAffiliationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to select an affiliation.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        return [
            'affiliation_id' => [
                'required',
                'integer',
                Rule::exists('affiliations', 'id')->where(function (Builder $query) use ($userId): void {
                    $query
                        ->where('user_id', $userId)
                        ->whereNull('deactivated_at');
                }),
            ],
        ];
    }
}
