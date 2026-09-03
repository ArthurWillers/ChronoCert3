<?php

namespace App\Actions\Fortify;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private RecordActivity $recordActivity,
        private ActiveAffiliationContext $activeAffiliationContext,
    ) {}

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $activeAffiliation = $this->activeAffiliationContext->for($user);

        DB::transaction(function () use ($activeAffiliation, $input, $user): void {
            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();

            $this->recordActivity->execute(
                event: AuditEvent::UserPasswordChanged,
                subject: $user,
                causer: $user,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $activeAffiliation?->getAttribute('course_id'),
                references: ['user' => $user],
                changes: ['password' => ['changed' => true]],
            );
        });
    }
}
