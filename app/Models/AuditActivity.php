<?php

namespace App\Models;

use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Enums\AuditSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class AuditActivity extends BaseActivity
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'attribute_changes',
        'properties',
        'context_course_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'context_course_id' => 'integer',
        ];
    }

    /**
     * @return MorphTo<User, $this>
     */
    public function causer(): MorphTo
    {
        return parent::causer();
    }

    /**
     * Limit the query to records visible to the active affiliation.
     *
     * @param  Builder<AuditActivity>  $query
     * @return Builder<AuditActivity>
     */
    public function scopeVisibleTo(Builder $query, Affiliation $affiliation): Builder
    {
        if ($this->isGlobalAdministrator($affiliation)) {
            return $query;
        }

        $courseId = $affiliation->getAttribute('course_id');

        if ($affiliation->type === AffiliationType::Coordinator && $courseId !== null) {
            return $query->where('context_course_id', $courseId);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine whether the active affiliation can inspect this activity.
     */
    public function isVisibleTo(Affiliation $affiliation): bool
    {
        if ($this->isGlobalAdministrator($affiliation)) {
            return true;
        }

        return $affiliation->type === AffiliationType::Coordinator
            && $affiliation->getAttribute('course_id') !== null
            && (int) $this->context_course_id === (int) $affiliation->getAttribute('course_id');
    }

    /**
     * Obtain the event definition when it is known by the application.
     */
    public function auditEvent(): ?AuditEvent
    {
        return AuditEvent::tryFrom((string) $this->event);
    }

    /**
     * Obtain the source definition stored with this event.
     */
    public function auditSource(): ?AuditSource
    {
        return AuditSource::tryFrom((string) $this->getProperty('source.type'));
    }

    /**
     * Obtain a readable snapshot stored for a reference.
     *
     * @return array<string, mixed>|null
     */
    public function reference(string $name): ?array
    {
        $reference = Arr::get($this->properties?->all() ?? [], "references.{$name}");

        return is_array($reference) ? $reference : null;
    }

    /**
     * Obtain every readable reference snapshot associated with this event.
     *
     * @return array<string, array<string, mixed>>
     */
    public function references(): array
    {
        $references = Arr::get($this->properties?->all() ?? [], 'references', []);

        return is_array($references) ? $references : [];
    }

    /**
     * Obtain the safe field changes associated with this event.
     *
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $changes = Arr::get($this->properties?->all() ?? [], 'changes', []);

        return is_array($changes) ? $changes : [];
    }

    /**
     * Build the title shown when the original subject no longer exists.
     */
    public function subjectLabel(): string
    {
        $subject = $this->reference('subject');

        if ($subject !== null) {
            return (string) (
                $subject['title']
                ?? $subject['name']
                ?? Arr::get($subject, 'user.name')
                ?? $subject['label']
                ?? 'Registro técnico #'.($subject['id'] ?? '—')
            );
        }

        return 'Registro técnico #'.$this->subject_id;
    }

    private function isGlobalAdministrator(Affiliation $affiliation): bool
    {
        return $affiliation->type === AffiliationType::Administrator
            && $affiliation->getAttribute('course_id') === null;
    }
}
