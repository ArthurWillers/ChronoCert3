<?php

namespace App\Console\Commands;

use App\Actions\Administrators\CreateGlobalAdministrator;
use App\Actions\Fortify\PasswordValidationRules;
use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('chronocert:create-administrator
    {--name= : Nome completo do administrador}
    {--cpf= : CPF do administrador}
    {--email= : E-mail de login do administrador}
    {--operational-email= : E-mail operacional do vínculo}
    {--password= : Senha inicial (evite passar por linha de comando)}')]
#[Description('Cria o primeiro usuário e vínculo global de administrador')]
class CreateInitialAdministrator extends Command
{
    use PasswordValidationRules;

    public function __construct(private CreateGlobalAdministrator $createGlobalAdministrator)
    {
        parent::__construct();
    }

    /**
     * Create the initial global administrator through interactive prompts.
     */
    public function handle(): int
    {
        if (Affiliation::query()->where('type', AffiliationType::Administrator)->exists()) {
            $this->components->error('Já existe um vínculo de administrador. Use a gestão institucional para os demais cadastros.');

            return self::FAILURE;
        }

        $name = $this->optionValue('name') ?? text('Nome completo do administrador', required: 'Informe o nome completo.');
        $cpf = $this->optionValue('cpf') ?? text(
            'CPF',
            placeholder: 'Somente números ou formatado',
            required: 'Informe o CPF.',
            validate: fn (string $value): ?string => $this->validationMessage('cpf', $value, [
                'required',
                'string',
                new ValidCpf,
                Rule::unique(User::class, 'cpf'),
            ]),
            transform: fn (string $value): string => preg_replace('/\D/', '', $value) ?? '',
        );
        $email = $this->optionValue('email') ?? text(
            'E-mail de login',
            placeholder: 'admin@instituicao.edu.br',
            required: 'Informe o e-mail de login.',
            validate: fn (string $value): ?string => $this->validationMessage('email', $value, [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ]),
            transform: fn (string $value): string => Str::lower(trim($value)),
        );
        $operationalEmail = $this->optionValue('operational-email') ?? text(
            'E-mail operacional do vínculo',
            default: $email,
            required: 'Informe o e-mail operacional.',
            validate: fn (string $value): ?string => $this->validationMessage('operational_email', $value, [
                'required',
                'email',
                'max:255',
            ]),
            transform: fn (string $value): string => Str::lower(trim($value)),
        );
        [$password, $passwordConfirmation] = $this->passwordValues();

        $data = [
            'name' => trim($name),
            'cpf' => preg_replace('/\D/', '', $cpf) ?? '',
            'email' => Str::lower(trim($email)),
            'operational_email' => Str::lower(trim($operationalEmail)),
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ];

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', new ValidCpf, Rule::unique(User::class, 'cpf')],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'operational_email' => ['required', 'email', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        [$user, $affiliation] = $this->createGlobalAdministrator->execute(
            name: $data['name'],
            cpf: $data['cpf'],
            email: $data['email'],
            operationalEmail: $data['operational_email'],
            password: $data['password'],
        );

        $this->components->info("Administrador criado: {$user->name} (vínculo #{$affiliation->id}).");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function passwordValues(): array
    {
        $optionPassword = $this->optionValue('password');

        if ($optionPassword !== null) {
            return [$optionPassword, $optionPassword];
        }

        $password = password(
            'Senha inicial',
            required: 'Informe a senha.',
            validate: fn (string $value): ?string => $this->validationMessage('password', $value, $this->passwordPromptRules()),
            hint: 'Use pelo menos 8 caracteres, com maiúscula, minúscula, número e símbolo.',
        );
        $confirmation = password('Confirme a senha', required: 'Confirme a senha.');

        if ($password !== $confirmation) {
            $this->components->warn('As senhas não conferem. Informe-as novamente.');

            return $this->passwordValues();
        }

        return [$password, $confirmation];
    }

    /**
     * @return array<int, mixed>
     */
    private function passwordPromptRules(): array
    {
        return array_values(array_filter(
            $this->passwordRules(),
            fn (mixed $rule): bool => $rule !== 'confirmed',
        ));
    }

    private function optionValue(string $option): ?string
    {
        $value = $this->option($option);

        return is_string($value) && filled($value) ? $value : null;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function validationMessage(string $attribute, string $value, array $rules): ?string
    {
        $validator = Validator::make([$attribute => $value], [$attribute => $rules]);

        return $validator->fails() ? $validator->errors()->first($attribute) : null;
    }
}
