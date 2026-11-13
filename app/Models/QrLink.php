<?php

namespace App\Models;

use App\QrShareRole;
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

    /**
     * @param  Builder<QrLink>  $query
     * @return Builder<QrLink>
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('user_id', $userId)
                ->orWhereHas('shares', function (Builder $shares) use ($userId): void {
                    $shares->where('user_id', $userId);
                });
        });
    }

    /**
     * @param  Builder<QrLink>  $query
     * @return Builder<QrLink>
     */
    public function scopeEditableBy(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('user_id', $userId)
                ->orWhereHas('shares', function (Builder $shares) use ($userId): void {
                    $shares->where('user_id', $userId)->where('role', QrShareRole::Edit->value);
                });
        });
    }

    public function shares(): HasMany
    {
        return $this->hasMany(QrShare::class);
    }

    public function accessFor(int $userId): string
    {
        if ((int) $this->user_id === $userId) {
            return 'owner';
        }

        $share = $this->relationLoaded('shares')
            ? $this->shares->firstWhere('user_id', $userId)
            : $this->shares()->where('user_id', $userId)->first();
        $role = $share?->role;

        return $role instanceof QrShareRole ? $role->value : QrShareRole::View->value;
    }

    /**
     * @return array<int, array{user_id: int, name: string, email: string, role: string}>
     */
    public function shareList(): array
    {
        $shares = $this->relationLoaded('shares')
            ? $this->shares
            : $this->shares()->with('user')->orderBy('id')->get();

        if ($shares->isNotEmpty() && ! $shares->first()->relationLoaded('user')) {
            $shares->load('user');
        }

        return $shares
            ->sortBy('id')
            ->map(function (QrShare $share): array {
                $role = $share->role;

                return [
                    'user_id' => $share->user_id,
                    'name' => $share->user?->name ?? '',
                    'email' => $share->user?->email ?? '',
                    'role' => $role instanceof QrShareRole ? $role->value : (string) $role,
                ];
            })
            ->values()
            ->all();
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
