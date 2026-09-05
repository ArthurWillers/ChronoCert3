<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'required_acc_hours', 'minimum_area_percentage', 'deactivated_at'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
            'required_acc_hours' => 'decimal:2',
            'minimum_area_percentage' => 'decimal:2',
        ];
    }

    /**
     * Limit the query to courses available for new institutional relationships.
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    /**
     * @return HasMany<Affiliation, $this>
     */
    public function affiliations(): HasMany
    {
        return $this->hasMany(Affiliation::class);
    }

    /**
     * Determine whether domain records still depend on this course.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->affiliations()->exists();
    }

    /**
     * Determine whether the course has a total ACC workload requirement.
     */
    public function hasAccRequirement(): bool
    {
        return $this->required_acc_hours !== null;
    }

    /**
     * Determine whether the course requires part of its ACC workload in the area.
     */
    public function hasAreaRequirement(): bool
    {
        return $this->minimum_area_percentage !== null;
    }

    /**
     * Calculate the minimum ACC workload that must be recognized as in the area.
     */
    public function minimumAreaHours(): ?float
    {
        if (! $this->hasAccRequirement() || ! $this->hasAreaRequirement()) {
            return null;
        }

        return round(((float) $this->required_acc_hours * (float) $this->minimum_area_percentage) / 100, 2);
    }
}
