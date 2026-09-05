<?php

namespace App\Actions\Affiliations;

use App\Actions\Audit\RecordActivity;
use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAffiliation
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Update editable affiliation facts while retaining the established role.
     *
     * @param  array{email: string, registration_number: ?string, course_id: ?int}  $data
     */
    public function execute(Affiliation $affiliation, array $data, User $causer, Affiliation $activeAffiliation): Affiliation
    {
        return DB::transaction(function () use ($affiliation, $data, $causer, $activeAffiliation): Affiliation {
            $affiliation = Affiliation::query()
                ->with(['user', 'course'])
                ->lockForUpdate()
                ->findOrFail($affiliation->getKey());
            $previousCourse = $affiliation->course;
            $newCourse = $affiliation->type === AffiliationType::Coordinator
                ? (
                    (int) $data['course_id'] === (int) $affiliation->course_id
                        ? $previousCourse
                        : $this->resolveCourse($data['course_id'])
                )
                : $previousCourse;

            $this->ensureShape($affiliation->type, $newCourse, $data['registration_number']);
            $this->ensureNoActiveDuplicate($affiliation, $newCourse);

            $old = [
                'operational_email' => $affiliation->email,
                'registration_number' => $affiliation->registration_number,
                'course' => $previousCourse?->name,
            ];
            $new = [
                'operational_email' => $data['email'],
                'registration_number' => $data['registration_number'],
                'course' => $newCourse?->name,
            ];
            $changes = [];

            foreach ($new as $attribute => $value) {
                if ($old[$attribute] !== $value) {
                    $changes[$attribute] = ['old' => $old[$attribute], 'new' => $value];
                }
            }

            if ($changes === []) {
                return $affiliation;
            }

            $affiliation->fill([
                'email' => $data['email'],
                'registration_number' => $data['registration_number'],
                'course_id' => $newCourse?->getKey(),
            ])->save();
            $affiliation->setRelation('course', $newCourse);

            $this->recordActivity->execute(
                event: AuditEvent::AffiliationUpdated,
                subject: $affiliation,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $newCourse?->getKey(),
                references: array_filter([
                    'user' => $affiliation->user,
                    'target_affiliation' => $affiliation,
                    'previous_course' => $previousCourse,
                    'course' => $newCourse,
                ]),
                changes: $changes,
            );

            return $affiliation;
        });
    }

    private function resolveCourse(?int $courseId): ?Course
    {
        if ($courseId === null) {
            return null;
        }

        $course = Course::query()->lockForUpdate()->findOrFail($courseId);

        if ($course->deactivated_at !== null) {
            throw ValidationException::withMessages([
                'course_id' => 'Não é possível associar o vínculo a um curso inativo.',
            ]);
        }

        return $course;
    }

    private function ensureShape(AffiliationType $type, ?Course $course, ?string $registrationNumber): void
    {
        $valid = match ($type) {
            AffiliationType::Administrator => $course === null && $registrationNumber === null,
            AffiliationType::Coordinator => $course !== null && $registrationNumber === null,
            AffiliationType::Student => $course !== null && filled($registrationNumber),
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'affiliation' => 'Os dados informados não correspondem ao tipo de vínculo.',
            ]);
        }
    }

    private function ensureNoActiveDuplicate(Affiliation $affiliation, ?Course $course): void
    {
        if ($affiliation->deactivated_at !== null) {
            return;
        }

        $query = Affiliation::query()
            ->active()
            ->where('user_id', $affiliation->user_id)
            ->where('type', $affiliation->type)
            ->whereKeyNot($affiliation->getKey());

        if ($course === null) {
            $query->whereNull('course_id');
        } else {
            $query->where('course_id', $course->getKey());
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'Este usuário já possui um vínculo ativo com esta atuação e curso.',
            ]);
        }
    }
}
