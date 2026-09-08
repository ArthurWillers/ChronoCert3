<?php

namespace App\Actions\Categories;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\AccCategory;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function __construct(private RecordActivity $recordActivity, private CategoryDependencies $dependencies) {}

    public function execute(AccCategory $category, User $causer, Affiliation $activeAffiliation): void
    {
        try {
            DB::transaction(function () use ($category, $causer, $activeAffiliation): void {
                $course = Course::query()->lockForUpdate()->findOrFail($category->course_id);
                $category = AccCategory::query()->lockForUpdate()->findOrFail($category->getKey());
                $category->setRelation('course', $course);
                Gate::forUser($causer)->authorize('delete', $category);
                $dependencies = $this->dependencies->execute($category);

                if ($dependencies !== []) {
                    throw $this->dependencyException();
                }

                $this->recordActivity->execute(
                    event: AuditEvent::CategoryDeleted,
                    subject: $category,
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                    contextCourseId: $course->getKey(),
                    references: ['course' => $course, 'category' => $category],
                    changes: ['name' => ['old' => $category->name, 'new' => null]],
                    dependencyCheck: ['checked' => true, 'has_dependencies' => false],
                );
                $category->delete();
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) === '23503') {
                throw $this->dependencyException();
            }
            throw $exception;
        }
    }

    private function dependencyException(): ValidationException
    {
        return ValidationException::withMessages([
            'category' => 'Esta categoria possui registros relacionados. Inative-a para preservar o histórico.',
        ]);
    }
}
