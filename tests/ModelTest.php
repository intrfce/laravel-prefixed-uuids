<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Exceptions\PrefixMismatchException;
use Intrfce\PrefixedUuids\Facades\PrefixedId;
use Intrfce\PrefixedUuids\Tests\Fixtures\Post;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;

it('auto-assigns a uuid v7 key on create', function () {
    $user = User::create(['name' => 'Ada']);

    expect($user->getKey())->toMatch('/^[0-9a-f-]{36}$/')
        ->and($user->getKey()[14])->toBe('7'); // version nibble
});

it('exposes a prefixed public_id over the raw uuid key', function () {
    $user = User::create(['name' => 'Ada']);

    expect($user->public_id)->toStartWith('user_')
        ->and($user->public_id)->toBe('user_'.substr($user->public_id, 5))
        ->and(strlen($user->public_id))->toBe(strlen('user_') + 22)
        // the ORM key is untouched (ADR-0004)
        ->and($user->getKey())->not->toContain('_');
});

it('replaces id with the public id in array output and hides the raw uuid (ADR-0007)', function () {
    $user = User::create(['name' => 'Ada']);
    $array = $user->toArray();

    expect($array['id'])->toBe($user->public_id)
        ->and($array['id'])->toContain('_')
        ->and($array)->not->toContain($user->getKey());
});

it('uses the public id as the route key (ADR-0004)', function () {
    $user = User::create(['name' => 'Ada']);

    expect($user->getRouteKey())->toBe($user->public_id);
});

it('accepts a correctly-prefixed public id on assignment, storing the raw uuid (ADR-0008)', function () {
    $user = User::create(['name' => 'Ada']);
    $uuid = $user->getKey();
    $publicId = $user->public_id;

    $fresh = new User;
    $fresh->id = $publicId;

    expect($fresh->getKey())->toBe($uuid);
});

it('throws when assigning a public id with the wrong prefix (ADR-0003)', function () {
    $customerPublicId = PrefixedId::encode('0192f8a1-9b2c-71d4-a716-446655440000', 'cus');

    $user = new User;
    $user->id = $customerPublicId;
})->throws(PrefixMismatchException::class);

it('keeps relationships working through the raw uuid foreign key', function () {
    $user = User::create(['name' => 'Ada']);
    $user->posts()->create(['title' => 'Hello']);

    expect($user->posts)->toHaveCount(1)
        ->and($user->posts->first())->toBeInstanceOf(Post::class)
        ->and($user->posts->first()->user_id)->toBe($user->getKey());
});
