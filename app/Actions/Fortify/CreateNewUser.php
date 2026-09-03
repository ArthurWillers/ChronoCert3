<?php

namespace App\Actions\Fortify;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', new ValidCpf, Rule::unique(User::class)],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'cpf' => $input['cpf'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
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
            );

            return $user;
        });
    }
}
