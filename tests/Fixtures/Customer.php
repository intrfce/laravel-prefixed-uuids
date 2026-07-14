<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedId;

class Customer extends Model
{
    use HasPrefixedId;
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = [];
}
