<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Intrfce\PrefixedUuids\Codec;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('generates urls using the public id (ADR-0004)', function () {
    $user = User::create(['name' => 'Ada']);

    Route::get('/users/{user}', fn (User $user) => $user->public_id)->name('users.show');

    expect(route('users.show', $user))->toEndWith('/users/'.$user->public_id);
});

it('resolves an implicit route binding by decoding the public id', function () {
    $user = User::create(['name' => 'Ada']);

    Route::get('/users/{user}', fn (User $user) => $user->getKey())
        ->middleware(SubstituteBindings::class);

    $this->get('/users/'.$user->public_id)
        ->assertOk()
        ->assertSee($user->getKey());
});

it('404s when the route value has no matching record', function () {
    Route::get('/users/{user}', fn (User $user) => $user->getKey())
        ->middleware(SubstituteBindings::class);

    $ghost = 'user_'.Codec::encode('0192f8a1-9b2c-71d4-a716-446655440000');

    $this->get('/users/'.$ghost)->assertNotFound();
});

it('404s (does not 500) on a malformed or wrong-prefix route value', function (string $value) {
    Route::get('/users/{user}', fn (User $user) => $user->getKey())
        ->middleware(SubstituteBindings::class);

    $this->get('/users/'.$value)->assertNotFound();
})->with([
    'malformed' => 'not-a-valid-id',
    'wrong prefix' => 'cus_0000000000000000000000',
]);

it('resolves and rejects via resolveRouteBinding directly', function () {
    $user = User::create(['name' => 'Ada']);

    expect((new User)->resolveRouteBinding($user->public_id)?->getKey())->toBe($user->getKey())
        // wrong prefix -> null (a 404), never throws
        ->and((new User)->resolveRouteBinding('cus_0000000000000000000000'))->toBeNull()
        // malformed -> null
        ->and((new User)->resolveRouteBinding('garbage'))->toBeNull();
});
