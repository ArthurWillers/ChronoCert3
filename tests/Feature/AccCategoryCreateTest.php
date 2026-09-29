<?php

use App\Models\AccCategory;
use App\Models\Affiliation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('a coordinator can open the category creation form with only academic rules', function () {
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->create();
    $coordinatorAffiliation->load('user');

    $response = $this->actingAs($coordinatorAffiliation->user)
        ->withSession(['active_affiliation_id' => $coordinatorAffiliation->getKey()])
        ->get(route('categories.create'));

    $response
        ->assertOk()
        ->assertSeeText('Regras acadêmicas')
        ->assertSeeText('Limite de horas da categoria')
        ->assertDontSeeText('Orientação ao discente');
});

test('a coordinator can view category academic rules without student guidance', function () {
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->create();
    $category = AccCategory::factory()->create([
        'course_id' => $coordinatorAffiliation->course_id,
        'created_by_affiliation_id' => $coordinatorAffiliation->getKey(),
    ]);
    $coordinatorAffiliation->load('user');

    $this->actingAs($coordinatorAffiliation->user)
        ->withSession(['active_affiliation_id' => $coordinatorAffiliation->getKey()])
        ->get(route('categories.show', $category))
        ->assertOk()
        ->assertSeeText('Regras acadêmicas')
        ->assertSeeText('Limite de horas da categoria')
        ->assertDontSeeText('Orientação ao discente');
});
