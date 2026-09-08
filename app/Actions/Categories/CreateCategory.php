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

class CreateCategory
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{name: string, description: ?string, max_hours: string, guidance: ?string}  $data
     */
    public function execute(Course $course, array $data, User $causer, Affiliation $activeAffiliation): AccCategory
    {
        return DB::transaction(function () use ($course, $data, $causer, $activeAffiliation): AccCategory {
            $course = Course::query()->lockForUpdate()->findOrFail($course->getKey());
            Gate::forUser($causer)->authorize('create', [AccCategory::class, $course]);

            $category = new AccCategory;
            $category->fill(collect($data)->only($category->getFillable())->all());
            $category->course()->associate($course);
            $category->createdByAffiliation()->associate($activeAffiliation);
            $category->ensureUniqueActiveName();
            $category->save();

            $changes = [];
            foreach ($category->getFillable() as $attribute) {
                $changes[$attribute] = ['old' => null, 'new' => $category->getAttribute($attribute)];
            }

            $this->recordActivity->execute(
                event: AuditEvent::CategoryCreated,
                subject: $category,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $course->getKey(),
                references: ['course' => $course, 'category' => $category, 'author_affiliation' => $activeAffiliation],
                changes: $changes,
            );

            return $category;
        });
    }
}
