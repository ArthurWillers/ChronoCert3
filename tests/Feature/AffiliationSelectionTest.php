<?php

use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('a user can select one of their active affiliations', function () {
    $user = User::factory()->create();
    $firstAffiliation = Affiliation::factory()->student()->for($user)->for(Course::factory())->create();
    $selectedAffiliation = Affiliation::factory()->student()->for($user)->for(Course::factory())->create();

    $this->actingAs($user)
        ->post(route('affiliations.select.store'), ['affiliation_id' => $selectedAffiliation->getKey()])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('active_affiliation_id', $selectedAffiliation->getKey());

    expect($firstAffiliation->fresh()->last_used_at)->toBeNull()
        ->and($selectedAffiliation->fresh()->last_used_at)->not->toBeNull();
});
