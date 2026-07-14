<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

use RuntimeException;

/**
 * Base type for every exception this package throws. Catch this to handle all
 * of them uniformly, or catch a specific subtype.
 */
class PrefixedUuidException extends RuntimeException
{
}
