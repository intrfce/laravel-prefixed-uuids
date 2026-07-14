<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Exceptions\MissingPrefixException;
use ReflectionClass;

/**
 * Declares a model's Public ID prefix, right on the model (ADR-0016):
 *
 *     #[PrefixedId('cus')]
 *     class Customer extends Model { use HasPrefixedId; }
 *
 * The prefix lives with the model, so there is no central registry to keep in
 * sync (this supersedes the morph-map-style registration of ADR-0011). A prefix
 * is immutable per class, so reads are reflected once and cached forever.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PrefixedId
{
    /** @var array<class-string<Model>, string> */
    private static array $cache = [];

    public function __construct(public readonly string $prefix) {}

    /**
     * The declared prefix for a model class.
     *
     * @param  class-string<Model>  $model
     *
     * @throws MissingPrefixException when the class has no #[PrefixedId] attribute.
     */
    public static function forModel(string $model): string
    {
        return self::$cache[$model] ??= self::read($model);
    }

    /** @param  class-string<Model>  $model */
    private static function read(string $model): string
    {
        $attributes = (new ReflectionClass($model))->getAttributes(self::class);

        if ($attributes === []) {
            throw MissingPrefixException::forModel($model);
        }

        return $attributes[0]->newInstance()->prefix;
    }
}
