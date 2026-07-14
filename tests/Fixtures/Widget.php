<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedId;

/** Deliberately has NO #[PrefixedId] attribute — used to assert the misconfiguration error. */
class Widget extends Model
{
    use HasPrefixedId;

    protected $table = 'widgets';

    protected $guarded = [];
}
