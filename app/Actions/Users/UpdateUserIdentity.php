<?php

namespace App\Actions\Users;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserIdentity
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Update a user's identity from the institutional user administration.
     *
     * @param  array{name: string, cpf: string, email: string}  $data
     */
    public function execute(User $user, array $data, User $causer, Affiliation $activeAffiliation): User
    {
        return DB::transaction(function () use ($user, $data, $causer, $activeAffiliation): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $changes = [];

            foreach (['name', 'cpf', 'email'] as $attribute) {
                if ($user->getAttribute($attribute) !== $data[$attribute]) {
                    $changes[$attribute] = [
                        'old' => $user->getAttribute($attribute),
                        'new' => $data[$attribute],
                    ];
                }
            }

            if ($changes === []) {
                return $user;
            }

            $user->fill($data)->save();

            if (isset($changes['name'])) {
                $this->recordActivity->execute(
                    event: AuditEvent::UserUpdated,
                    subject: $user,
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                    references: ['user' => $user],
                    changes: ['name' => $changes['name']],
                );
            }

            if (isset($changes['cpf'])) {
                $this->recordActivity->execute(
                    event: AuditEvent::UserCpfChanged,
                    subject: $user,
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                    references: ['user' => $user],
                    changes: ['cpf' => $changes['cpf']],
                );
            }

            if (isset($changes['email'])) {
                $this->recordActivity->execute(
                    event: AuditEvent::UserLoginEmailChanged,
                    subject: $user,
                    causer: $causer,
                    activeAffiliation: $activeAffiliation,
                    references: ['user' => $user],
                    changes: ['email' => $changes['email']],
                );
            }

            return $user;
        });
    }
}
