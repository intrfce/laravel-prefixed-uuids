<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Codec;
use Intrfce\PrefixedUuids\Exceptions\PrefixMismatchException;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('finds a record by its public id (ADR-0014)', function () {
    $user = User::create(['name' => 'Ada']);

    expect(User::find($user->public_id)?->getKey())->toBe($user->getKey());
});

it('still finds a record by its raw uuid', function () {
    $user = User::create(['name' => 'Ada']);

    expect(User::find($user->getKey())?->getKey())->toBe($user->getKey());
});

it('finds many by a mix of public ids', function () {
    $a = User::create(['name' => 'A']);
    $b = User::create(['name' => 'B']);

    expect(User::findMany([$a->public_id, $b->public_id]))->toHaveCount(2);
});

it('destroys a record by its public id', function () {
    $user = User::create(['name' => 'Ada']);

    User::destroy($user->public_id);

    expect(User::find($user->getKey()))->toBeNull();
});

it('throws when finding with another model\'s prefix (ADR-0014)', function () {
    $customerPublicId = 'cus_'.Codec::encode('0192f8a1-9b2c-71d4-a716-446655440000');

    User::find($customerPublicId);
})->throws(PrefixMismatchException::class);
