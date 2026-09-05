<?php

namespace App\Actions\Courses;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateCourse
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Update the editable course data and preserve only effective changes.
     *
     * @param  array{name: string, required_acc_hours: ?string, minimum_area_percentage: ?string, has_area_requirement?: bool}  $data
     */
    public function execute(Course $course, array $data, User $causer, Affiliation $activeAffiliation): Course
    {
        return DB::transaction(function () use ($course, $data, $causer, $activeAffiliation): Course {
            $course = Course::query()->lockForUpdate()->findOrFail($course->getKey());
            $changes = [];

            foreach (['name', 'required_acc_hours', 'minimum_area_percentage'] as $attribute) {
                if ($course->getAttribute($attribute) !== $data[$attribute]) {
                    $changes[$attribute] = [
                        'old' => $course->getAttribute($attribute),
                        'new' => $data[$attribute],
                    ];
                }
            }

            if ($changes === []) {
                return $course;
            }

            $course->fill([
                'name' => $data['name'],
                'required_acc_hours' => $data['required_acc_hours'],
                'minimum_area_percentage' => $data['minimum_area_percentage'],
            ])->save();

            $this->recordActivity->execute(
                event: AuditEvent::CourseUpdated,
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
