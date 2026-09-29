<?php

namespace App\Actions\Users;

use App\Actions\Affiliations\ChangeAffiliationStatus;
use App\Actions\Affiliations\CreateAffiliation;
use App\Actions\Affiliations\UpdateAffiliation;
use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessStudentAffiliationCsvImport
{
    public function __construct(
        private PreviewStudentAffiliationCsv $previewStudentAffiliationCsv,
        private CreateInstitutionalUser $createInstitutionalUser,
        private CreateAffiliation $createAffiliation,
        private UpdateAffiliation $updateAffiliation,
        private ChangeAffiliationStatus $changeAffiliationStatus,
        private SendUserInvitation $sendUserInvitation,
    ) {}

    /**
     * Process each accepted row in its own transaction, keeping row failures isolated.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function execute(array $rows, User $causer, Affiliation $activeAffiliation): array
    {
        $rowsToRevalidate = array_values(array_filter($rows, static fn (array $row): bool => $row['status'] !== 'invalid'));
        $revalidated = $this->previewStudentAffiliationCsv->revalidate($rowsToRevalidate, $activeAffiliation)['rows'];
        $revalidatedByNumber = collect($revalidated)->keyBy('number');
        $results = [];

        foreach ($rows as $previewRow) {
            if ($previewRow['status'] === 'invalid') {
                $results[] = [...$previewRow, 'status' => 'invalid'];

                continue;
            }

            $row = $revalidatedByNumber->get($previewRow['number']);

            if ($row === null || $row['status'] === 'invalid') {
                $results[] = [
                    ...($row ?? $previewRow),
                    'status' => 'failed',
                    'errors' => $row['errors'] ?? ['Não foi possível revalidar esta linha.'],
                ];

                continue;
            }

            try {
                $outcome = $this->processRow($row, $causer, $activeAffiliation);
                $results[] = [...$row, 'status' => $outcome];
            } catch (ValidationException $exception) {
                $results[] = [
                    ...$row,
                    'status' => 'failed',
                    'errors' => collect($exception->errors())->flatten()->values()->all(),
                ];
            } catch (QueryException) {
                $results[] = [
                    ...$row,
                    'status' => 'failed',
                    'errors' => ['Os dados entraram em conflito com outra alteração. Revise a matrícula e tente novamente.'],
                ];
            }
        }

        return [
            'rows' => $results,
            'summary' => $this->summarize($results),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function processRow(array $row, User $causer, Affiliation $activeAffiliation): string
    {
        return DB::transaction(function () use ($row, $causer, $activeAffiliation): string {
            $course = Course::query()->active()->lockForUpdate()->find($activeAffiliation->course_id);

            if ($course === null) {
                throw ValidationException::withMessages(['course_id' => 'O curso do vínculo ativo está inativo.']);
            }

            $user = User::query()->where('cpf', $row['cpf'])->lockForUpdate()->first();

            if ($user === null) {
                if (User::query()->where('email', $row['email'])->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Este e-mail já é usado como e-mail de login por outra conta.',
                    ]);
                }

                $created = $this->createInstitutionalUser->execute(
                    identity: [
                        'name' => $row['name'],
                        'cpf' => $row['cpf'],
                        'email' => $row['email'],
                    ],
                    affiliationData: [
                        'type' => AffiliationType::Student,
                        'course_id' => $course->getKey(),
                        'email' => $row['email'],
                        'registration_number' => $row['registration_number'],
                    ],
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                );

                $this->sendUserInvitation->execute(
                    user: $created['user'],
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                );

                return 'new_user';
            }

            $affiliations = Affiliation::query()
                ->where('user_id', $user->getKey())
                ->where('type', AffiliationType::Student)
                ->where('course_id', $course->getKey())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            $activeStudentAffiliation = $affiliations->first(fn (Affiliation $affiliation): bool => $affiliation->deactivated_at === null);

            if ($activeStudentAffiliation !== null) {
                return 'ignored';
            }

            $this->ensureRegistrationNumberAvailable($course, $row['registration_number']);
            $inactiveStudentAffiliation = $affiliations->first(fn (Affiliation $affiliation): bool => $affiliation->deactivated_at !== null);

            if ($inactiveStudentAffiliation !== null) {
                $this->updateAffiliation->execute(
                    affiliation: $inactiveStudentAffiliation,
                    data: [
                        'email' => $row['email'],
                        'registration_number' => $row['registration_number'],
                        'course_id' => $course->getKey(),
                    ],
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                );
                $this->changeAffiliationStatus->execute(
                    affiliation: $inactiveStudentAffiliation,
                    deactivate: false,
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                );

                return 'reactivation';
            }

            $this->createAffiliation->execute(
                targetUser: $user,
                data: [
                    'type' => AffiliationType::Student,
                    'course_id' => $course->getKey(),
                    'email' => $row['email'],
                    'registration_number' => $row['registration_number'],
                ],
                causer: $causer,
                activeAffiliation: $activeAffiliation,
            );

            return 'new_affiliation';
        });
    }

    private function ensureRegistrationNumberAvailable(Course $course, string $registrationNumber): void
    {
        $exists = Affiliation::query()
            ->active()
            ->where('type', AffiliationType::Student)
            ->where('course_id', $course->getKey())
            ->where('registration_number', $registrationNumber)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'registration_number' => 'Esta matrícula já está ativa em outro vínculo deste curso.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarize(array $rows): array
    {
        $counts = collect($rows)->countBy('status');

        return [
            'users_created' => (int) $counts->get('new_user', 0),
            'affiliations_created' => (int) $counts->get('new_user', 0) + (int) $counts->get('new_affiliation', 0),
            'reactivated' => (int) $counts->get('reactivation', 0),
            'ignored' => (int) $counts->get('ignored', 0),
            'failed' => (int) $counts->get('failed', 0),
            'invalid' => (int) $counts->get('invalid', 0),
        ];
    }
}
