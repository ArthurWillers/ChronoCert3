<?php

namespace App\Models;

use App\Enums\AffiliationType;
use Database\Factories\AccCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'description', 'max_hours', 'guidance'])]
class AccCategory extends Model
{
    /** @use HasFactory<AccCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_hours' => 'decimal:2',
            'deactivated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Affiliation, $this> */
    public function createdByAffiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'created_by_affiliation_id');
    }

    /** @param Builder<AccCategory> $query
     * @return Builder<AccCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    /** @param Builder<AccCategory> $query
     * @return Builder<AccCategory>
     */
    public function scopeVisibleTo(Builder $query, Affiliation $affiliation): Builder
    {
        if (! $affiliation->isActive() || $affiliation->course_id === null
            || ! in_array($affiliation->type, [AffiliationType::Coordinator, AffiliationType::Student], true)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('course_id', $affiliation->course_id);
    }

    /**
     * Call under the course lock before creating, renaming or reactivating a category.
     */
    public function ensureUniqueActiveName(): void
    {
        if ($this->deactivated_at !== null) {
            return;
        }

        if (static::query()->active()->where('course_id', $this->course_id)->where('name', $this->name)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))->exists()) {
            throw ValidationException::withMessages(['name' => 'Já existe uma categoria ativa com este nome neste curso.']);
        }
    }

    /**
     * Values for a future review to copy at decision time, never a live historical reference.
     *
     * @return array{id: int, name: string, course_id: int, course: array{id: int, name: string}, max_hours: string, accepts_multiple: bool, document_required: bool, allowed_mime_types: array<int, string>, max_file_size_bytes: int}
     */
    public function academicSnapshot(): array
    {
        $this->loadMissing('course');

        return [
            'id' => $this->getKey(), 'name' => $this->name,
            'course_id' => $this->course_id,
            'course' => ['id' => $this->course->getKey(), 'name' => $this->course->name],
            'max_hours' => $this->max_hours,
            'accepts_multiple' => config('acc.documents.accepts_multiple'),
            'document_required' => config('acc.documents.required'),
            'allowed_mime_types' => array_keys(config('acc.documents.mime_types')),
            'max_file_size_bytes' => config('acc.documents.max_file_size_bytes'),
        ];
    }
}
