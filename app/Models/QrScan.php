<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScan extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'qr_link_id',
        'ip',
        'user_agent',
        'city',
        'region',
        'country',
        'device',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(QrLink::class, 'qr_link_id');
    }
}
