<?php

namespace App\Actions\Courses;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCourse
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Create a course and record its institutional audit event atomically.
     *
     * @param  array{name: string, required_acc_hours: ?string, minimum_area_percentage: ?string, has_area_requirement?: bool}  $data
     */
    public function execute(array $data, User $causer, Affiliation $activeAffiliation): Course
    {
        return DB::transaction(function () use ($data, $causer, $activeAffiliation): Course {
            $course = Course::create([
                'name' => $data['name'],
                'required_acc_hours' => $data['required_acc_hours'],
                'minimum_area_percentage' => $data['minimum_area_percentage'],
            ]);
            $changes = [
                'name' => ['old' => null, 'new' => $course->name],
            ];

            foreach (['required_acc_hours', 'minimum_area_percentage'] as $attribute) {
                if ($course->getAttribute($attribute) !== null) {
                    $changes[$attribute] = [
                        'old' => null,
                        'new' => $course->getAttribute($attribute),
                    ];
                }
            }

            $this->recordActivity->execute(
                event: AuditEvent::CourseCreated,
                subject: $course,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                references: ['course' => $course],
                changes: $changes,
            );

            return $course;
        });
    }
}
