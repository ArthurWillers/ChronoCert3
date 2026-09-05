<?php

namespace App\Actions\Courses;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCourse
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Delete a course only when no domain record depends on it.
     */
    public function execute(Course $course, User $causer, Affiliation $activeAffiliation): void
    {
        DB::transaction(function () use ($course, $causer, $activeAffiliation): void {
            $course = Course::query()->lockForUpdate()->findOrFail($course->getKey());

            if (! $course->canBeDeleted()) {
                throw ValidationException::withMessages([
                    'course' => 'Este curso possui vínculos institucionais e deve ser inativado, não excluído.',
                ]);
            }

            $this->recordActivity->execute(
                event: AuditEvent::CourseDeleted,
                subject: $course,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                references: ['course' => $course],
                changes: [
                    'name' => ['old' => $course->name, 'new' => null],
                ],
            );

            $course->delete();
        });
    }
}
