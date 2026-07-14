<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Exceptions\DuplicatePrefixException;
use Intrfce\PrefixedUuids\Exceptions\ModelNotRegisteredException;
use Intrfce\PrefixedUuids\Exceptions\UnknownPrefixException;
use Intrfce\PrefixedUuids\Facades\PrefixedId;
use Intrfce\PrefixedUuids\PrefixIdRegistry;
use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('throws when a prefix is remapped to a different model (ADR-0012)', function () {
    PrefixedId::map(['user' => Customer::class]);
})->throws(DuplicatePrefixException::class);

it('throws for an unregistered model', function () {
    app(PrefixIdRegistry::class)->prefixForModel(\stdClass::class);
})->throws(ModelNotRegisteredException::class);

it('throws for an unknown prefix', function () {
    app(PrefixIdRegistry::class)->modelForPrefix('nope');
})->throws(UnknownPrefixException::class);

it('maps a table back to its model for the validation rule', function () {
    expect(app(PrefixIdRegistry::class)->modelForTable('users'))->toBe(User::class);
});
