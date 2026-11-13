<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrLink extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'secret',
        'type',
        'title',
        'kind',
        'payload',
        'style',
        'presentation',
        'target_url',
        'file_path',
        'file_name',
        'file_mime',
        'logo_path',
        'scans',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'style' => 'array',
            'presentation' => 'array',
            'scans' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<QrLink>  $query
     * @return Builder<QrLink>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scansLog(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public static function issueCode(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;

        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
