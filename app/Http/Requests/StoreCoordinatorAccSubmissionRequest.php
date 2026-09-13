<?php

namespace App\Http\Requests;

use App\Models\AccSubmission;
use App\Models\Affiliation;

class StoreCoordinatorAccSubmissionRequest extends StoreAccSubmissionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $studentAffiliation = $this->route('studentAffiliation');

        return $studentAffiliation instanceof Affiliation
            && ($this->user()?->can('createFor', [AccSubmission::class, $studentAffiliation]) ?? false);
    }
}
