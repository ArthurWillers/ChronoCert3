<?php

namespace App\Http\Requests;

use App\Models\AccCategory;

class UpdateAccCategoryRequest extends StoreAccCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof AccCategory && ($this->user()?->can('update', $category) ?? false);
    }
}
