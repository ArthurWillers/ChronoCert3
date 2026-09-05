<?php

namespace App\Actions\Users;

use App\Actions\Affiliations\CreateAffiliation;
use App\Actions\Audit\RecordActivity;
use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInstitutionalUser
{
    public function __construct(
        private CreateAffiliation $createAffiliation,
        private RecordActivity $recordActivity,
    ) {}

    /**
     * Create an institutional identity and its mandatory first affiliation atomically.
     *
     * @param  array{name: string, cpf: string, email: string}  $identity
     * @param  array{type: AffiliationType, course_id: ?int, email: string, registration_number: ?string}  $affiliationData
     * @return array{user: User, affiliation: Affiliation}
     */
    public function execute(
        array $identity,
        array $affiliationData,
        User $causer,
        Affiliation $activeAffiliation,
    ): array {
        return DB::transaction(function () use ($identity, $affiliationData, $causer, $activeAffiliation): array {
            $user = User::create([
                ...$identity,
                'password' => Str::random(96),
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::UserCreated,
                subject: $user,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $affiliationData['course_id'],
                references: ['user' => $user],
                changes: [
                    'name' => ['old' => null, 'new' => $user->name],
                    'cpf' => ['old' => null, 'new' => $user->cpf],
                    'email' => ['old' => null, 'new' => $user->email],
                ],
            );

            $affiliation = $this->createAffiliation->execute(
                targetUser: $user,
                data: $affiliationData,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
            );

            return compact('user', 'affiliation');
        });
    }
}
