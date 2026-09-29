<?php

use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('a coordinator finds an existing user by CPF before creating a student affiliation', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $existingUser = Affiliation::factory()->student()->create()->user;

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->get(route('users.index'))
        ->assertOk()
        ->assertSeeText('Vincular usuário existente');

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->get(route('users.lookup', ['cpf' => $existingUser->cpf]))
        ->assertOk()
        ->assertSeeText($existingUser->name)
        ->assertSeeText('Criar vínculo');
});

test('a coordinator profile does not expose the generic add affiliation action', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $student = Affiliation::factory()->student()->for($course)->create();

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->get(route('users.show', $student->user))
        ->assertOk()
        ->assertDontSeeText('Adicionar vínculo')
        ->assertSeeText('Registrar documento');
});

test('a coordinator is sent directly to the affiliation form when the CPF already belongs to a user', function () {
    $course = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $existingUser = Affiliation::factory()->student()->create()->user;

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->post(route('users.store'), [
            'cpf' => $existingUser->cpf,
            'affiliation_type' => 'student',
            'course_id' => $course->getKey(),
            'registration_number' => '20260001',
            'operational_email' => 'academico@chronocert.test',
        ])
        ->assertRedirect(route('users.affiliations.create', $existingUser))
        ->assertSessionHas('users.affiliation_target_user_id', $existingUser->getKey());
});

test('the administrator user view does not expose a student document summary', function () {
    $administrator = Affiliation::factory()->administrator()->create();
    $student = Affiliation::factory()->student()->create();

    $this->actingAs($administrator->user)
        ->withSession(['active_affiliation_id' => $administrator->getKey()])
        ->get(route('users.show', $student->user))
        ->assertOk()
        ->assertDontSeeText('Resumo de documentos');
});

test('an administrator can view an administrator affiliation without lazy loading its owner', function () {
    $administrator = Affiliation::factory()->administrator()->create();
    $targetAdministrator = Affiliation::factory()->administrator()->create();

    $this->actingAs($administrator->user)
        ->withSession(['active_affiliation_id' => $administrator->getKey()])
        ->get(route('users.show', $targetAdministrator->user))
        ->assertOk()
        ->assertSeeText('Vínculos');
});
