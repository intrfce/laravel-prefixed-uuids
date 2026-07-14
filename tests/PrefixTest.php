<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Exceptions\MissingPrefixException;
use Intrfce\PrefixedUuids\PrefixedId;
use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;
use Intrfce\PrefixedUuids\Tests\Fixtures\Widget;

it('reads the prefix declared by the #[PrefixedId] attribute (ADR-0016)', function () {
    expect(PrefixedId::forModel(User::class))->toBe('user')
        ->and(PrefixedId::forModel(Customer::class))->toBe('cus')
        ->and((new User)->idPrefix())->toBe('user');
});

it('throws when a model has no #[PrefixedId] attribute', function () {
    PrefixedId::forModel(Widget::class);
})->throws(MissingPrefixException::class);

it('surfaces the missing-attribute error through the model too', function () {
    (new Widget)->idPrefix();
})->throws(MissingPrefixException::class);
