<?php

namespace App\Actions\Fortify;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        DB::transaction(function () use ($input, $user): void {
            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();

            $this->recordActivity->execute(
                event: AuditEvent::UserPasswordChanged,
                subject: $user,
                references: ['user' => $user],
                changes: ['password' => ['changed' => true]],
            );
        });
    }
}
