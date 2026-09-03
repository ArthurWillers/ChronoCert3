<?php

namespace App\Actions\Fortify;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(
        private RecordActivity $recordActivity,
        private ActiveAffiliationContext $activeAffiliationContext,
    ) {}

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        if ($user->email === $input['email']) {
            return;
        }

        $previousEmail = $user->email;
        $activeAffiliation = $this->activeAffiliationContext->for($user);

        DB::transaction(function () use ($activeAffiliation, $input, $previousEmail, $user): void {
            $user->forceFill([
                'email' => $input['email'],
            ])->save();

            $this->recordActivity->execute(
                event: AuditEvent::UserLoginEmailChanged,
                subject: $user,
                causer: $user,
                activeAffiliation: $activeAffiliation,
                contextCourseId: $activeAffiliation?->getAttribute('course_id'),
                references: ['user' => $user],
                changes: ['email' => ['old' => $previousEmail, 'new' => $input['email']]],
            );
        });
    }
}
