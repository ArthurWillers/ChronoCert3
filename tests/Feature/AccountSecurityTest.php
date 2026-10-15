<?php

use App\Actions\Fortify\InvalidateUserSessions;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

test('password confirmation attempts on account changes are rate limited', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->put(route('user-profile-information.update'), [
            'email' => 'novo@chronocert.test', 'current_password' => 'incorreta',
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');
    }
    $this->put(route('user-profile-information.update'), [
        'email' => 'novo@chronocert.test', 'current_password' => 'incorreta',
    ])->assertTooManyRequests();
});

test('changing the login email requires the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-profile-information.update'), ['email' => 'novo@chronocert.test'])
        ->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

    expect($user->refresh()->email)->not->toBe('novo@chronocert.test');

    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'email' => 'novo@chronocert.test',
        'current_password' => 'password',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('novo@chronocert.test');
});

test('changing the password keeps this session and rejects sessions holding the previous password hash', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);
    $student = Affiliation::factory()->student()->create();
    $user = $student->user;
    $previousHash = $user->password;

    $this->actingAs($user)->withSession(['active_affiliation_id' => $student->getKey()])
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'NovaSenha#2026',
            'password_confirmation' => 'NovaSenha#2026',
        ])->assertSessionHasNoErrors();

    expect(Hash::check('NovaSenha#2026', $user->refresh()->password))->toBeTrue();
    $this->get(route('dashboard'))->assertOk();
    $this->withSession(['password_hash_web' => $previousHash])->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('session revocation removes other database sessions and rotates remembered access', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $session = new Store('security-test', new ArraySessionHandler(120));
    $session->setId(str_repeat('a', 40));
    $session->start();
    $request = Request::create('/settings');
    $request->setLaravelSession($session);
    foreach ([[$session->getId(), $user->id], [str_repeat('b', 40), $user->id], [str_repeat('c', 40), $anotherUser->id]] as [$id, $userId]) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
    }
    $previousToken = $user->remember_token;
    (new InvalidateUserSessions($request))->execute($user, keepCurrentSession: true);

    expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe([$session->getId()])
        ->and(DB::table('sessions')->where('user_id', $anotherUser->id)->count())->toBe(1)
        ->and($user->refresh()->remember_token)->not->toBe($previousToken);

    (new InvalidateUserSessions($request))->execute($user);
    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
});

test('the password recovery flow changes the password and expires previous password sessions', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);
    $user = User::factory()->create();
    $previousHash = $user->password;
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'email' => $user->email,
        'token' => $token,
        'password' => 'Recuperada#2026',
        'password_confirmation' => 'Recuperada#2026',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Recuperada#2026', $user->refresh()->password))->toBeTrue();
    $this->actingAs($user)->withSession(['password_hash_web' => $previousHash])
        ->get(route('settings'))->assertRedirect(route('login'));
});
