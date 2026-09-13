<?php

namespace App\Http\Requests;

use App\Models\AccReview;
use App\Models\AccSubmission;
use Illuminate\Foundation\Http\FormRequest;

class StartAccReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof AccSubmission
            && ($this->user()?->can('create', [AccReview::class, $submission]) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
