<?php

use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Jobs\SendUserInvitationJob;
use App\Models\Affiliation;
use App\Models\AuditActivity;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

test('a coordinator can preview comma or semicolon CSV with a UTF-8 BOM', function (string $delimiter, string $bom) {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $newUser = User::factory()->make(['email' => 'bulk-new@example.test']);
    $csv = $bom.implode($delimiter, ['cpf', 'nome', 'email', 'matricula'])."\r\n"
        .implode($delimiter, [$newUser->cpf, 'Estudante Novo', 'novo@example.test', '20260001'])."\r\n";

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->post(route('users.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])
        ->assertOk()
        ->assertSeeText('Estudante Novo')
        ->assertSeeText('Nova conta e vínculo')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['new_users'] === 1);
})->with([
    'semicolon' => [';', "\xEF\xBB\xBF"],
    'comma' => [',', ''],
]);

test('a confirmed new user import creates one account, a student affiliation, audit events, and an invitation', function () {
    Queue::fake();

    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $newUser = User::factory()->make(['email' => 'unused-login@example.test']);
    $csv = "cpf;nome;email;matricula\r\n{$newUser->cpf};Nova Pessoa;new-person@example.test;20260002\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);
    $token = $preview->viewData('token');

    $preview->assertOk();
    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $token])
        ->assertRedirect();

    $user = User::query()->where('cpf', $newUser->cpf)->firstOrFail();
    $affiliation = $user->affiliations()->sole();

    expect($user->email)->toBe('new-person@example.test')
        ->and($affiliation->type)->toBe(AffiliationType::Student)
        ->and($affiliation->course_id)->toBe($course->getKey())
        ->and($affiliation->email)->toBe($user->email)
        ->and($affiliation->registration_number)->toBe('20260002');

    Queue::assertPushed(SendUserInvitationJob::class);
    expect(AuditActivity::query()->where('event', AuditEvent::UserCreated->value)->count())->toBe(1)
        ->and(AuditActivity::query()->where('event', AuditEvent::AffiliationCreated->value)->count())->toBe(1);
});

test('an existing user is linked without changing their name or login email', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $existingUser = User::factory()->create([
        'name' => 'Nome Original',
        'email' => 'login-original@example.test',
    ]);
    $formattedCpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $existingUser->cpf);
    $csv = "cpf;nome;email;matricula\r\n{$formattedCpf};Nome Alterado;operacional@example.test;20260003\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);

    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $preview->viewData('token')])
        ->assertRedirect();

    $existingUser->refresh();

    expect($existingUser->name)->toBe('Nome Original')
        ->and($existingUser->email)->toBe('login-original@example.test')
        ->and($existingUser->affiliations()->sole()->email)->toBe('operacional@example.test')
        ->and(AuditActivity::query()->where('event', AuditEvent::UserCreated->value)->count())->toBe(0);
});

test('active student affiliations are skipped and inactive ones are updated and reactivated', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $activeStudent = Affiliation::factory()->student()->for($course)->create([
        'registration_number' => '20260004',
        'email' => 'active-old@example.test',
    ]);
    $inactiveStudent = Affiliation::factory()->student()->for($course)->inactive()->create([
        'registration_number' => '20260005',
        'email' => 'inactive-old@example.test',
    ]);
    $activeCpf = $activeStudent->user->cpf;
    $inactiveCpf = $inactiveStudent->user->cpf;
    $csv = "cpf;nome;email;matricula\r\n"
        ."{$activeCpf};Nome Ignorado;active-new@example.test;99999999\r\n"
        ."{$inactiveCpf};Nome Ignorado;inactive-new@example.test;20269999\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);

    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $preview->viewData('token')])
        ->assertRedirect();

    expect($activeStudent->fresh()->registration_number)->toBe('20260004')
        ->and($activeStudent->fresh()->email)->toBe('active-old@example.test')
        ->and($inactiveStudent->fresh()->registration_number)->toBe('20269999')
        ->and($inactiveStudent->fresh()->email)->toBe('inactive-new@example.test')
        ->and($inactiveStudent->fresh()->deactivated_at)->toBeNull()
        ->and(AuditActivity::query()->where('event', AuditEvent::AffiliationActivated->value)->count())->toBe(1);
});

test('duplicate CPF and registration numbers and invalid row data are marked in the preview', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $first = User::factory()->make(['email' => 'first@example.test']);
    $second = User::factory()->make(['email' => 'second@example.test']);
    $csv = "cpf;nome;email;matricula\r\n"
        ."{$first->cpf};Pessoa Um;first@example.test;DUP\r\n"
        ."{$first->cpf};Pessoa Dois;second@example.test;MAT-2\r\n"
        ."{$second->cpf};Pessoa Três;third@example.test;DUP\r\n"
        ."11111111111;Pessoa Quatro;fourth@example.test;MAT-4\r\n"
        ."{$second->cpf};Pessoa Cinco;not-an-email;\r\n";

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->post(route('users.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary): bool => $summary['invalid'] === 5)
        ->assertSeeText('Este CPF aparece mais de uma vez no arquivo.')
        ->assertSeeText('Esta matrícula aparece mais de uma vez no arquivo.')
        ->assertSeeText('O CPF informado é inválido.')
        ->assertSeeText('O e-mail informado é inválido.')
        ->assertSeeText('A matrícula é obrigatória.');
});

test('a login email or active course registration number already in use makes only that row invalid', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    Affiliation::factory()->student()->for($course)->create([
        'registration_number' => 'USED-REG',
    ]);
    $loginOwner = User::factory()->create(['email' => 'owned-login@example.test']);
    $first = User::factory()->make(['email' => 'first-placeholder@example.test']);
    $second = User::factory()->make(['email' => 'second-placeholder@example.test']);
    $third = User::factory()->make(['email' => 'third-placeholder@example.test']);
    $fourth = User::factory()->make(['email' => 'fourth-placeholder@example.test']);
    $csv = "cpf;nome;email;matricula\r\n"
        ."{$first->cpf};Pessoa Um;new-one@example.test;USED-REG\r\n"
        ."{$second->cpf};Pessoa Dois;owned-login@example.test;FREE-REG\r\n"
        ."{$loginOwner->cpf};Pessoa Três;operacional@example.test;ANOTHER-REG\r\n"
        ."{$third->cpf};Pessoa Quatro;same-new-login@example.test;NEW-REG-1\r\n"
        ."{$fourth->cpf};Pessoa Cinco;same-new-login@example.test;NEW-REG-2\r\n";

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->post(route('users.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])
        ->assertOk()
        ->assertSeeText('Esta matrícula já está ativa em outro vínculo deste curso.')
        ->assertSeeText('Este e-mail já é usado como e-mail de login por outra conta.')
        ->assertSeeText('Este e-mail aparece mais de uma vez para contas novas no arquivo.')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['invalid'] === 4 && $summary['new_affiliations'] === 1);
});

test('CSV upload rejects an empty file, an unsupported extension, a wrong header, and more than 500 rows', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);

    $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('empty.csv', ''),
    ])->assertSessionHasErrors('csv_file');

    $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('students.txt', "cpf;nome;email;matricula\n"),
    ])->assertSessionHasErrors('csv_file');

    $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('wrong.csv', "cpf;nome;email\n"),
    ])->assertSessionHasErrors('csv_file');

    $rows = array_fill(0, 501, '12345678909;Nome;nome@example.test;123');
    $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('too-many.csv', "cpf;nome;email;matricula\n".implode("\n", $rows)),
    ])->assertSessionHasErrors('csv_file');
});

test('only coordinators with an active affiliation can access the import workflow', function () {
    $course = Course::factory()->create();
    $administrator = Affiliation::factory()->administrator()->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $userWithoutAffiliation = User::factory()->create();
    $routes = [
        route('users.import.create'),
        route('users.import.template'),
        route('users.import.report', str_repeat('a', 64)),
    ];

    foreach ([$administrator->user, $student->user, $userWithoutAffiliation] as $user) {
        foreach ($routes as $route) {
            $this->actingAs($user)->get($route)->assertForbidden();
        }

        $this->actingAs($user)
            ->post(route('users.import.preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', "cpf;nome;email;matricula\n"),
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('users.import.confirm'), ['token' => str_repeat('a', 64)])
            ->assertForbidden();
    }
});

test('a preview token expires after confirmation and cannot be replayed', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $newUser = User::factory()->make(['email' => 'unused@example.test']);
    $csv = "cpf;nome;email;matricula\r\n{$newUser->cpf};Pessoa Nova;new@example.test;20260006\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);
    $token = $preview->viewData('token');

    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $token])->assertRedirect();
    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $token])
        ->assertSessionHasErrors('token');

    $expiredPreview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);
    Cache::forget('student-affiliation-import.preview.'.hash('sha256', $expiredPreview->viewData('token')));
    $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $expiredPreview->viewData('token')])
        ->assertSessionHasErrors('token');
});

test('a conflict introduced after preview fails only that row and other accepted rows still import', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $first = User::factory()->make(['email' => 'first-placeholder@example.test']);
    $second = User::factory()->make(['email' => 'second-placeholder@example.test']);
    $csv = "cpf;nome;email;matricula\r\n"
        ."{$first->cpf};Pessoa Um;claimed-login@example.test;20260007\r\n"
        ."{$second->cpf};Pessoa Dois;second-new@example.test;20260008\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);
    User::factory()->create(['email' => 'claimed-login@example.test']);

    $confirm = $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $preview->viewData('token')]);
    $confirm->assertRedirect();
    $reportUrl = $confirm->headers->get('Location');
    $report = $actingAsCoordinator->get($reportUrl);

    $report->assertOk()
        ->assertSeeText('Não processado')
        ->assertSeeText('Conta e vínculo criados')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['failed'] === 1 && $summary['users_created'] === 1);
});

test('report data is available only in the coordinator context that confirmed the import', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $otherCoordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $newUser = User::factory()->make(['email' => 'unused-report@example.test']);
    $csv = "cpf;nome;email;matricula\r\n{$newUser->cpf};Pessoa Nova;report@example.test;20260009\r\n";
    $actingAsCoordinator = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()]);
    $preview = $actingAsCoordinator->post(route('users.import.preview'), [
        'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
    ]);
    $confirm = $actingAsCoordinator->post(route('users.import.confirm'), ['token' => $preview->viewData('token')]);
    $reportUrl = $confirm->headers->get('Location');

    $this->actingAs($otherCoordinator->user)
        ->withSession(['active_affiliation_id' => $otherCoordinator->getKey()])
        ->get($reportUrl)
        ->assertNotFound();
});

test('a preview token cannot be confirmed from another coordinator affiliation', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $otherCoordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $newUser = User::factory()->make(['email' => 'unused-token@example.test']);
    $csv = "cpf;nome;email;matricula\r\n{$newUser->cpf};Pessoa Nova;token@example.test;20260010\r\n";
    $preview = $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->post(route('users.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ]);

    $this->actingAs($otherCoordinator->user)
        ->withSession(['active_affiliation_id' => $otherCoordinator->getKey()])
        ->post(route('users.import.confirm'), ['token' => $preview->viewData('token')])
        ->assertNotFound();

    expect(User::query()->where('cpf', $newUser->cpf)->exists())->toBeFalse();
});
