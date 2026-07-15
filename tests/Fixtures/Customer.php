<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUUID;
use Intrfce\PrefixedUuids\PrefixedId;

#[PrefixedId('cus')]
class Customer extends Model
{
    use HasPrefixedUUID;
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = [];
}
