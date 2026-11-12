<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortLink extends Model
{
    protected $fillable = [
        'code',
        'title',
        'url',
        'clicks',
    ];

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ShortLinkClick::class);
    }

    public static function issueCode(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;

        do {
            $code = '';

            for ($i = 0; $i < 7; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
