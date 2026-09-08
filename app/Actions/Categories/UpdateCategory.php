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

class UpdateCategory
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{name: string, description: ?string, max_hours: string, guidance: ?string}  $data
     */
    public function execute(AccCategory $category, array $data, User $causer, Affiliation $activeAffiliation): AccCategory
    {
        return DB::transaction(function () use ($category, $data, $causer, $activeAffiliation): AccCategory {
            $course = Course::query()->lockForUpdate()->findOrFail($category->course_id);
            $category = AccCategory::query()->lockForUpdate()->findOrFail($category->getKey());
            $category->setRelation('course', $course);
            Gate::forUser($causer)->authorize('update', $category);
            $previous = clone $category;
            $category->fill($data);
            $category->ensureUniqueActiveName();
            $changes = [];

            foreach ($category->getDirty() as $attribute => $value) {
                $changes[$attribute] = ['old' => $previous->getAttribute($attribute), 'new' => $category->getAttribute($attribute)];
            }

            if ($changes === []) {
                return $category;
            }
            $category->save();

            $this->recordActivity->execute(
                event: AuditEvent::CategoryUpdated,
                subject: $category,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $course->getKey(),
                references: ['course' => $course, 'category' => $category],
                changes: $changes,
            );

            return $category;
        });
    }
}
