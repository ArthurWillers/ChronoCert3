<?php

namespace App\Models;

use App\Enums\AffiliationType;
use App\Enums\SubmissionOrigin;
use App\Enums\SubmissionStatus;
use Database\Factories\AccSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'student_affiliation_id',
    'submitted_by_affiliation_id',
    'origin',
    'status',
    'submitted_at',
    'review_started_at',
    'reviewed_at',
    'rejected_at',
    'purge_at',
])]
class AccSubmission extends Model implements HasMedia
{
    public const EvidenceCollection = 'acc-evidence';

    /** @use HasFactory<AccSubmissionFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin' => SubmissionOrigin::class,
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    /**
     * Configure the private collection that owns the proof file.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::EvidenceCollection)
            ->useDisk('private')
            ->singleFile()
            ->acceptsMimeTypes(self::acceptedMimeTypes());
    }

    /**
     * @return BelongsTo<Affiliation, $this>
     */
    public function studentAffiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'student_affiliation_id');
    }

    /**
     * @return BelongsTo<Affiliation, $this>
     */
    public function submittedByAffiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'submitted_by_affiliation_id');
    }

    /**
     * Limit the query to submissions accessible from the active affiliation.
     *
     * @param  Builder<AccSubmission>  $query
     * @return Builder<AccSubmission>
     */
    public function scopeVisibleTo(Builder $query, Affiliation $affiliation): Builder
    {
        if (! $affiliation->isActive() || $affiliation->course_id === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($affiliation->type === AffiliationType::Student) {
            return $query->whereBelongsTo($affiliation, 'studentAffiliation');
        }

        if ($affiliation->type === AffiliationType::Coordinator) {
            return $query->whereHas(
                'studentAffiliation',
                fn (Builder $studentAffiliations): Builder => $studentAffiliations->where('course_id', $affiliation->course_id),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Obtain the course of the beneficiary affiliation without duplicating it in this model.
     */
    public function studentCourseId(): ?int
    {
        if ($this->relationLoaded('studentAffiliation')) {
            return $this->studentAffiliation?->course_id;
        }

        $courseId = $this->studentAffiliation()->value('course_id');

        return $courseId === null ? null : (int) $courseId;
    }

    /**
     * Determine whether a detected MIME type and supplied extension are accepted together.
     */
    public static function acceptsFile(string $mimeType, string $extension): bool
    {
        $definition = self::acceptedFileTypes()[$mimeType] ?? null;

        return is_array($definition)
            && in_array(Str::lower($extension), $definition['extensions'] ?? [], true);
    }

    /**
     * @return array<string, array{label: string, extensions: array<int, string>}>
     */
    public static function acceptedFileTypes(): array
    {
        /** @var array<string, array{label: string, extensions: array<int, string>}> $fileTypes */
        $fileTypes = config('acc.documents.accepted_file_types', []);

        return $fileTypes;
    }

    /**
     * @return array<int, string>
     */
    public static function acceptedMimeTypes(): array
    {
        return array_keys(self::acceptedFileTypes());
    }

    /**
     * @return array<int, string>
     */
    public static function acceptedExtensions(): array
    {
        return collect(self::acceptedFileTypes())
            ->pluck('extensions')
            ->flatten()
            ->filter(static fn (mixed $extension): bool => is_string($extension))
            ->map(static fn (string $extension): string => Str::lower($extension))
            ->unique()
            ->values()
            ->all();
    }
}
