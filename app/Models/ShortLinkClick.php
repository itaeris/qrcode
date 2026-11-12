<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkClick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'short_link_id',
        'ip',
        'user_agent',
        'city',
        'region',
        'country',
        'device',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }
}
