<?php

namespace App\Actions\Users;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Delete an identity only when it has no institutional dependency.
     */
    public function execute(User $user, User $causer, Affiliation $activeAffiliation): void
    {
        DB::transaction(function () use ($user, $causer, $activeAffiliation): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($user->affiliations()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Este usuário possui vínculos institucionais e não pode ser excluído.',
                ]);
            }

            $this->recordActivity->execute(
                event: AuditEvent::UserDeleted,
                subject: $user,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                references: ['user' => $user],
                changes: [
                    'name' => ['old' => $user->name, 'new' => null],
                    'cpf' => ['old' => $user->cpf, 'new' => null],
                    'email' => ['old' => $user->email, 'new' => null],
                ],
            );

            $user->delete();
        });
    }
}
