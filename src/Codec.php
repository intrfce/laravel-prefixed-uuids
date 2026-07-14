<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Intrfce\PrefixedUuids\Exceptions\InvalidPublicIdException;

/**
 * Reversible base62 codec over a UUID's 16 raw bytes (ADR-0001 / 0002).
 *
 * A UUID is treated as a 128-bit big-endian integer and converted to/from
 * base62 with pure integer arithmetic (no ext-gmp / ext-bcmath required). The
 * tail is fixed-width at TAIL_LENGTH characters, left-padded with the zero
 * digit, and decode always yields exactly 16 bytes (ADR-0012).
 */
final class Codec
{
    public const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /** ceil(128 / log2(62)) = 22 — the max digits a 128-bit number needs in base62. */
    public const TAIL_LENGTH = 22;

    private const BYTES = 16;

    /** Encode a canonical UUID string into its ~22-char base62 tail. */
    public static function encode(string $uuid): string
    {
        return self::bytesToBase62(self::uuidToBytes($uuid));
    }

    /** Decode a base62 tail back into a canonical UUID string. */
    public static function decode(string $tail): string
    {
        return self::bytesToUuid(self::base62ToBytes($tail));
    }

    // --- UUID <-> raw bytes -------------------------------------------------

    private static function uuidToBytes(string $uuid): string
    {
        $hex = str_replace('-', '', $uuid);

        if (strlen($hex) !== 32 || ! ctype_xdigit($hex)) {
            throw InvalidPublicIdException::badTail($uuid);
        }

        return hex2bin($hex);
    }

    private static function bytesToUuid(string $bytes): string
    {
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    // --- base conversion (big-endian digit arrays) -------------------------

    /** 16 base-256 digits -> fixed-width base62 string. */
    private static function bytesToBase62(string $bytes): string
    {
        $number = array_values(unpack('C*', $bytes));

        $out = '';
        while (self::hasValue($number)) {
            [$number, $remainder] = self::divmod($number, 256, 62);
            $out = self::ALPHABET[$remainder].$out;
        }

        return str_pad($out, self::TAIL_LENGTH, '0', STR_PAD_LEFT);
    }

    /** base62 string -> exactly 16 base-256 bytes. */
    private static function base62ToBytes(string $tail): string
    {
        if ($tail === '') {
            throw InvalidPublicIdException::badTail($tail);
        }

        $number = [];
        foreach (str_split($tail) as $char) {
            $digit = strpos(self::ALPHABET, $char);
            if ($digit === false) {
                throw InvalidPublicIdException::badTail($tail);
            }
            $number[] = $digit;
        }

        $bytes = [];
        while (self::hasValue($number)) {
            [$number, $remainder] = self::divmod($number, 62, 256);
            array_unshift($bytes, $remainder);
        }

        if (count($bytes) > self::BYTES) {
            throw InvalidPublicIdException::badTail($tail);
        }

        // Left-pad shorter results (leading zero bytes) up to a full 16 bytes.
        while (count($bytes) < self::BYTES) {
            array_unshift($bytes, 0);
        }

        return pack('C*', ...$bytes);
    }

    /**
     * Divide a big-endian digit array (given in $fromBase) by $divisor,
     * returning [quotientDigits, remainder]. Leading zeros are dropped from the
     * quotient. All intermediate products stay well within PHP's int range.
     */
    private static function divmod(array $number, int $fromBase, int $divisor): array
    {
        $quotient = [];
        $remainder = 0;

        foreach ($number as $digit) {
            $acc = $remainder * $fromBase + $digit;
            $q = intdiv($acc, $divisor);
            $remainder = $acc % $divisor;

            if ($quotient !== [] || $q !== 0) {
                $quotient[] = $q;
            }
        }

        return [$quotient, $remainder];
    }

    private static function hasValue(array $number): bool
    {
        return $number !== [];
    }
}
