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
        'type',
        'email',
        'starts_at',
        'ends_at',
        'deactivated_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AffiliationType::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'deactivated_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Limit the query to affiliations that are currently valid for operation.
     *
     * @param  Builder<Affiliation>  $query
     * @return Builder<Affiliation>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('deactivated_at')
            ->whereDate('starts_at', '<=', today())
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', today());
            });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
