<?php

namespace App\Models;

use App\Enums\AffiliationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affiliation extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'type',
        'email',
        'registration_number',
        'deactivated_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AffiliationType::class,
            'deactivated_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Limit the query to affiliations that are available for operation.
     *
     * @param  Builder<Affiliation>  $query
     * @return Builder<Affiliation>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->active();
    }

    /**
     * Limit the query to affiliations that have not been manually deactivated.
     *
     * @param  Builder<Affiliation>  $query
     * @return Builder<Affiliation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    /**
     * Determine whether the affiliation is available for operation.
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
