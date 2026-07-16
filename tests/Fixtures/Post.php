<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUuids;

class Post extends Model
{
    use HasPrefixedUuids;

    protected $table = 'posts';

    protected $guarded = [];

    public function idPrefix(): string
    {
        return 'post';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
