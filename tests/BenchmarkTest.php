<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Intrfce\PrefixedUuids\Codec;

/**
 * Informational benchmark (ADR-0009): times encoding and decoding 1000 IDs and
 * prints the result. It never asserts a threshold, so it never fails the build.
 */
it('benchmarks encoding and decoding 1000 ids', function () {
    $count = 1000;

    $uuids = [];
    for ($i = 0; $i < $count; $i++) {
        $uuids[] = (string) Str::uuid7();
    }

    $start = hrtime(true);
    $tails = array_map(fn (string $uuid) => Codec::encode($uuid), $uuids);
    $encodeMs = (hrtime(true) - $start) / 1_000_000;

    $start = hrtime(true);
    $decoded = array_map(fn (string $tail) => Codec::decode($tail), $tails);
    $decodeMs = (hrtime(true) - $start) / 1_000_000;

    // Sanity: the round trip must be lossless.
    expect($decoded)->toBe($uuids);

    fwrite(STDOUT, sprintf(
        "\n  [benchmark] encode %d: %.2f ms (%.4f ms/op) | decode %d: %.2f ms (%.4f ms/op)\n",
        $count,
        $encodeMs,
        $encodeMs / $count,
        $count,
        $decodeMs,
        $decodeMs / $count,
    ));
})->group('benchmark');
