<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUUID;
use Intrfce\PrefixedUuids\PrefixedId;

#[PrefixedId('post')]
class Post extends Model
{
    use HasPrefixedUUID;

    protected $table = 'posts';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
