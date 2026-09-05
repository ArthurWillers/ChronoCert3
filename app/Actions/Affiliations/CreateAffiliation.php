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

class CreateAffiliation
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Create an affiliation with database-backed duplicate protection and audit data.
     *
     * @param  array{type: AffiliationType, course_id: ?int, email: string, registration_number: ?string}  $data
     */
    public function execute(User $targetUser, array $data, ?User $causer, ?Affiliation $activeAffiliation): Affiliation
    {
        return DB::transaction(function () use ($targetUser, $data, $causer, $activeAffiliation): Affiliation {
            $type = $data['type'];
            $course = $this->resolveCourse($data['course_id']);

            $this->ensureShape($type, $course, $data['registration_number']);
            $this->ensureNoActiveDuplicate($targetUser, $type, $course);

            $affiliation = Affiliation::create([
                'user_id' => $targetUser->getKey(),
                'course_id' => $course?->getKey(),
                'type' => $type,
                'email' => $data['email'],
                'registration_number' => $data['registration_number'],
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::AffiliationCreated,
                subject: $affiliation,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $course?->getKey(),
                references: array_filter([
                    'user' => $targetUser,
                    'target_affiliation' => $affiliation,
                    'course' => $course,
                ]),
                changes: [
                    'type' => ['old' => null, 'new' => $type->label()],
                    'course' => ['old' => null, 'new' => $course?->name],
                    'registration_number' => ['old' => null, 'new' => $affiliation->registration_number],
                    'operational_email' => ['old' => null, 'new' => $affiliation->email],
                ],
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
                'course_id' => 'Não é possível criar vínculo em um curso inativo.',
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

    private function ensureNoActiveDuplicate(User $user, AffiliationType $type, ?Course $course): void
    {
        $query = Affiliation::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->where('type', $type);

        if ($course === null) {
            $query->whereNull('course_id');
        } else {
            $query->where('course_id', $course->getKey());
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'affiliation' => 'Este usuário já possui um vínculo ativo com esta atuação e curso.',
            ]);
        }
    }
}
