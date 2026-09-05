<?php

namespace App\Actions\Affiliations;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeAffiliationStatus
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Activate or deactivate an affiliation without physically removing history.
     */
    public function execute(
        Affiliation $affiliation,
        bool $deactivate,
        User $causer,
        Affiliation $activeAffiliation,
        ?string $reason = null,
    ): Affiliation {
        return DB::transaction(function () use ($affiliation, $deactivate, $causer, $activeAffiliation, $reason): Affiliation {
            $affiliation = Affiliation::query()
                ->with(['user', 'course'])
                ->lockForUpdate()
                ->findOrFail($affiliation->getKey());

            if (! $deactivate && $affiliation->course?->deactivated_at !== null) {
                throw ValidationException::withMessages([
                    'affiliation' => 'Não é possível reativar um vínculo de curso inativo.',
                ]);
            }

            $previous = $affiliation->deactivated_at?->toIso8601String();
            $affiliation->forceFill(['deactivated_at' => $deactivate ? now() : null])->save();

            $this->recordActivity->execute(
                event: $deactivate ? AuditEvent::AffiliationDeactivated : AuditEvent::AffiliationActivated,
                subject: $affiliation,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $affiliation->course_id,
                references: array_filter([
                    'user' => $affiliation->user,
                    'target_affiliation' => $affiliation,
                    'course' => $affiliation->course,
                ]),
                changes: [
                    'deactivated_at' => [
                        'old' => $previous,
                        'new' => $affiliation->deactivated_at?->toIso8601String(),
                    ],
                ],
                reason: $deactivate ? $reason : null,
            );

            return $affiliation;
        });
    }
}
