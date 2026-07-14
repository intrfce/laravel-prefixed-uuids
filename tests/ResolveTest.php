<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Exceptions\UnknownPrefixException;
use Intrfce\PrefixedUuids\Facades\PrefixedId;
use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('resolves a public id to the right model instance (ADR-0011)', function () {
    $user = User::create(['name' => 'Ada']);
    $customer = Customer::create(['name' => 'Acme']);

    expect(PrefixedId::resolve($user->public_id))->toBeInstanceOf(User::class)
        ->and(PrefixedId::resolve($customer->public_id))->toBeInstanceOf(Customer::class)
        ->and(PrefixedId::resolve($user->public_id)->getKey())->toBe($user->getKey());
});

it('decodes a public id to its raw uuid without a query', function () {
    $user = User::create(['name' => 'Ada']);

    expect(PrefixedId::decode($user->public_id))->toBe($user->getKey());
});

it('returns null when the record does not exist', function () {
    $publicId = PrefixedId::encode('0192f8a1-9b2c-71d4-a716-446655440000', 'user');

    expect(PrefixedId::resolve($publicId))->toBeNull();
});

it('throws when resolving an unknown prefix', function () {
    PrefixedId::resolve('nope_3kQ4mZp0000000000000');
})->throws(UnknownPrefixException::class);
