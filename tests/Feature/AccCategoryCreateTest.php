<?php

use App\Models\Affiliation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

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

test('categories do not expose an intermediate detail page', function () {
    expect(Route::has('categories.show'))->toBeFalse();
});
