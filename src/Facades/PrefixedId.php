<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Intrfce\PrefixedUuids\PrefixIdRegistry;

/**
 * @method static void map(array<string, class-string<Model>> $map)
 * @method static string encode(string $uuid, string $prefix)
 * @method static string encodeForModel(string $uuid, string $model)
 * @method static string decode(string $publicId)
 * @method static Model|null resolve(string $publicId)
 * @method static array{0: string, 1: string} parse(string $publicId)
 * @method static mixed normalizeKeyForModel(mixed $value, string $model)
 * @method static PrefixIdRegistry registry()
 *
 * @see \Intrfce\PrefixedUuids\PrefixedIdManager
 */
class PrefixedId extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'prefixed-id';
    }
}
