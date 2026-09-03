<?php

namespace App\Actions\Administrators;

use App\Actions\Audit\RecordActivity;
use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Enums\AuditSource;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateGlobalAdministrator
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Create the first global administrator and its audit trail atomically.
     *
     * @return array{0: User, 1: Affiliation}
     */
    public function execute(
        string $name,
        string $cpf,
        string $email,
        string $operationalEmail,
        string $password,
        AuditSource $source = AuditSource::Console,
        string $sourceDetail = 'chronocert:create-administrator',
    ): array {
        return DB::transaction(function () use ($name, $cpf, $email, $operationalEmail, $password, $source, $sourceDetail): array {
            $user = User::create([
                'name' => $name,
                'cpf' => $cpf,
                'email' => $email,
                'password' => $password,
            ]);
            $affiliation = Affiliation::create([
                'user_id' => $user->id,
                'type' => AffiliationType::Administrator,
                'email' => $operationalEmail,
                'starts_at' => today(),
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::UserCreated,
                subject: $user,
                references: ['user' => $user],
                changes: [
                    'name' => ['old' => null, 'new' => $user->name],
                    'cpf' => ['old' => null, 'new' => $user->cpf],
                    'email' => ['old' => null, 'new' => $user->email],
                ],
                source: $source,
                sourceDetail: $sourceDetail,
            );
            $this->recordActivity->execute(
                event: AuditEvent::AffiliationCreated,
                subject: $affiliation,
                references: [
                    'user' => $user,
                    'target_affiliation' => $affiliation,
                ],
                changes: [
                    'type' => ['old' => null, 'new' => $affiliation->type->label()],
                    'operational_email' => ['old' => null, 'new' => $affiliation->email],
                    'starts_at' => ['old' => null, 'new' => $affiliation->starts_at->toDateString()],
                ],
                source: $source,
                sourceDetail: $sourceDetail,
            );

            return [$user, $affiliation];
        });
    }
}
