<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Intrfce\PrefixedUuids\Exceptions\ModelNotRegisteredException;
use Intrfce\PrefixedUuids\Rules\PublicIdExists;
use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

function validate(mixed $value, mixed $rule): bool
{
    return Validator::make(['field' => $value], ['field' => $rule])->passes();
}

it('passes the string rule for an existing public id (ADR-0015)', function () {
    $customer = Customer::create(['name' => 'Acme']);

    expect(validate($customer->public_id, 'public_id_exists:customers'))->toBeTrue();
});

it('fails the string rule for a non-existent public id', function () {
    $ghost = \Intrfce\PrefixedUuids\Facades\PrefixedId::encode('0192f8a1-9b2c-71d4-a716-446655440000', 'cus');

    expect(validate($ghost, 'public_id_exists:customers'))->toBeFalse();
});

it('fails soft (no exception) on a malformed value', function () {
    expect(validate('not-an-id', 'public_id_exists:customers'))->toBeFalse();
});

it('fails soft on a wrong-model prefix rather than throwing', function () {
    $user = User::create(['name' => 'Ada']);

    // A user_ id validated against the customers table must fail, not throw.
    expect(validate($user->public_id, 'public_id_exists:customers'))->toBeFalse();
});

it('throws for a validation rule targeting an unregistered table (programmer error)', function () {
    validate('cus_whatever0000000000000', 'public_id_exists:widgets');
})->throws(ModelNotRegisteredException::class);

it('supports the fluent form with soft-delete awareness', function () {
    $customer = Customer::create(['name' => 'Acme']);

    $rule = fn () => PublicIdExists::for(Customer::class)->withoutTrashed();

    expect(validate($customer->public_id, $rule()))->toBeTrue();

    $customer->delete();

    expect(validate($customer->public_id, $rule()))->toBeFalse()
        // still findable when trashed rows are included
        ->and(validate($customer->public_id, PublicIdExists::for(Customer::class)))->toBeTrue();
});

it('supports fluent where() constraints', function () {
    $customer = Customer::create(['name' => 'Acme', 'active' => false]);

    expect(validate($customer->public_id, PublicIdExists::for(Customer::class)->where('active', true)))->toBeFalse()
        ->and(validate($customer->public_id, PublicIdExists::for(Customer::class)->where('active', false)))->toBeTrue();
});
