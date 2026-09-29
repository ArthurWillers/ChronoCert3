<?php

namespace App\Models;

use Database\Factories\AccReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'acc_submission_id',
    'reviewer_affiliation_id',
    'acc_category_id',
    'original_title',
    'normalized_title',
    'certificate_hours',
    'is_area_related',
    'classification_justification',
    'rejection_reason',
    'category_snapshot',
    'rules_snapshot',
    'started_at',
    'completed_at',
])]
class AccReview extends Model
{
    /** @use HasFactory<AccReviewFactory> */
    use HasFactory;

    /** @var array<string, bool> */
    protected $attributes = [
        'is_area_related' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'certificate_hours' => 'decimal:2',
            'is_area_related' => 'boolean',
            'category_snapshot' => 'array',
            'rules_snapshot' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AccSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AccSubmission::class, 'acc_submission_id');
    }

    /** @return BelongsTo<Affiliation, $this> */
    public function reviewerAffiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'reviewer_affiliation_id');
    }

    /** @return BelongsTo<AccCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AccCategory::class, 'acc_category_id');
    }
}
