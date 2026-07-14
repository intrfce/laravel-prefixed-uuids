<?php

declare(strict_types=1);

use Intrfce\PrefixedUuids\Codec;
use Intrfce\PrefixedUuids\Exceptions\InvalidPublicIdException;

it('round-trips a canonical uuid', function () {
    $uuid = '0192f8a1-9b2c-71d4-a716-446655440000';

    expect(Codec::decode(Codec::encode($uuid)))->toBe($uuid);
});

it('produces a fixed-width, url-safe tail', function () {
    $tail = Codec::encode('0192f8a1-9b2c-71d4-a716-446655440000');

    expect($tail)->toHaveLength(Codec::TAIL_LENGTH)
        ->and(ctype_alnum($tail))->toBeTrue();
});

it('round-trips the all-zero uuid to an all-zero tail', function () {
    $zero = '00000000-0000-0000-0000-000000000000';
    $tail = Codec::encode($zero);

    expect($tail)->toBe(str_repeat('0', Codec::TAIL_LENGTH))
        ->and(Codec::decode($tail))->toBe($zero);
});

it('round-trips uuids with leading zero bytes (the padding trap, ADR-0012)', function (string $uuid) {
    expect(Codec::decode(Codec::encode($uuid)))->toBe($uuid);
})->with([
    '00112233-4455-6677-8899-aabbccddeeff',
    '00000000-0000-0000-0000-000000000001',
    '000000ff-0000-0000-0000-000000000000',
]);

it('rejects a tail with non-base62 characters', function () {
    Codec::decode('!!!not-base62!!!');
})->throws(InvalidPublicIdException::class);

it('rejects a tail that decodes to more than 16 bytes', function () {
    // 23 'z' chars overflows 128 bits.
    Codec::decode(str_repeat('z', 25));
})->throws(InvalidPublicIdException::class);
