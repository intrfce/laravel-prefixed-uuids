<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('reads the prefix declared by the model\'s idPrefix() method (ADR-0017)', function () {
    expect((new User)->idPrefix())->toBe('user')
        ->and((new Customer)->idPrefix())->toBe('cus');
});
