<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUuids;

class Customer extends Model
{
    use HasPrefixedUuids;
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = [];

    public function idPrefix(): string
    {
        return 'cus';
    }
}
