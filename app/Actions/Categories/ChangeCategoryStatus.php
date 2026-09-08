<?php

namespace App\Actions\Categories;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\AccCategory;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeCategoryStatus
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(AccCategory $category, bool $deactivate, User $causer, Affiliation $activeAffiliation, ?string $reason = null): AccCategory
    {
        return DB::transaction(function () use ($category, $deactivate, $causer, $activeAffiliation, $reason): AccCategory {
            $course = Course::query()->lockForUpdate()->findOrFail($category->course_id);
            $category = AccCategory::query()->lockForUpdate()->findOrFail($category->getKey());
            $category->setRelation('course', $course);
            Gate::forUser($causer)->authorize($deactivate ? 'deactivate' : 'reactivate', $category);

            if ($deactivate && blank($reason)) {
                throw ValidationException::withMessages(['deactivation_reason' => 'Informe o motivo da inativação.']);
            }

            $previousDate = $category->deactivated_at?->toIso8601String();
            $previousReason = $category->deactivation_reason;
            $category->forceFill([
                'deactivated_at' => $deactivate ? now() : null,
                'deactivation_reason' => $deactivate ? trim($reason) : null,
            ]);
            $category->ensureUniqueActiveName();
            $category->save();

            $this->recordActivity->execute(
                event: $deactivate ? AuditEvent::CategoryInactivated : AuditEvent::CategoryReactivated,
                subject: $category,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $course->getKey(),
                references: ['course' => $course, 'category' => $category],
                changes: [
                    'deactivated_at' => ['old' => $previousDate, 'new' => $category->deactivated_at?->toIso8601String()],
                    'deactivation_reason' => ['old' => $previousReason, 'new' => $category->deactivation_reason],
                ],
                reason: $reason,
            );

            return $category;
        });
    }
}
