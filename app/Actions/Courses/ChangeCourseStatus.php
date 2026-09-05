<?php

namespace App\Actions\Courses;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeCourseStatus
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Activate or deactivate a course with a corresponding audit event.
     */
    public function execute(Course $course, bool $deactivate, User $causer, Affiliation $activeAffiliation): Course
    {
        return DB::transaction(function () use ($course, $deactivate, $causer, $activeAffiliation): Course {
            $course = Course::query()->lockForUpdate()->findOrFail($course->getKey());
            $previous = $course->deactivated_at?->toIso8601String();
            $course->forceFill(['deactivated_at' => $deactivate ? now() : null])->save();

            $this->recordActivity->execute(
                event: $deactivate ? AuditEvent::CourseInactivated : AuditEvent::CourseReactivated,
                subject: $course,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                references: ['course' => $course],
                changes: [
                    'deactivated_at' => [
                        'old' => $previous,
                        'new' => $course->deactivated_at?->toIso8601String(),
                    ],
                ],
            );

            return $course;
        });
    }
}
