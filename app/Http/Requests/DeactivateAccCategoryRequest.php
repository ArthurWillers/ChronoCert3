<?php

namespace App\Http\Requests;

use App\Models\AccCategory;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateAccCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof AccCategory && ($this->user()?->can('deactivate', $category) ?? false);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['deactivation_reason' => ['required', 'string']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['deactivation_reason' => 'motivo da inativação'];
    }
}
