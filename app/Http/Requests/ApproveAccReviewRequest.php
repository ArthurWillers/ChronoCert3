<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAccReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof AccReview
            && ($this->user()?->can('approve', $review) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
