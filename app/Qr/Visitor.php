<?php

namespace App\Qr;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class Visitor
{
    /**
     * A short browser and device label from a user agent.
     */
    public static function device(?string $agent): string
    {
        $agent = $agent ?? '';
        $browser = 'Peramban lain';

        if (str_contains($agent, 'Edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($agent, 'OPR/') || str_contains($agent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($agent, 'Chrome/')) {
            $browser = 'Chrome';
        } elseif (str_contains($agent, 'Firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($agent, 'Safari/')) {
            $browser = 'Safari';
        }

        $platform = 'perangkat lain';

        if (str_contains($agent, 'iPhone')) {
            $platform = 'iPhone';
        } elseif (str_contains($agent, 'iPad')) {
            $platform = 'iPad';
        } elseif (str_contains($agent, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($agent, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($agent, 'Mac OS')) {
            $platform = 'Mac';
        } elseif (str_contains($agent, 'Linux')) {
            $platform = 'Linux';
        }

        return $browser.' · '.$platform;
    }

    /**
     * Approximate city, region, and country for a public IP address.
     *
     * @return array{city: ?string, region: ?string, country: ?string}
     */
    public static function place(?string $ip): array
    {
        $empty = ['city' => null, 'region' => null, 'country' => null];

        if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $empty;
        }

        return Cache::remember('scan-place:'.$ip, now()->addDay(), function () use ($ip, $empty): array {
            try {
                $response = Http::timeout(2)
                    ->acceptJson()
                    ->get('http://ip-api.com/json/'.$ip, [
                        'fields' => 'status,country,regionName,city',
                    ]);

                if (! $response->ok() || $response->json('status') !== 'success') {
                    return $empty;
                }

                return [
                    'city' => self::clean($response->json('city')),
                    'region' => self::clean($response->json('regionName')),
                    'country' => self::clean($response->json('country')),
                ];
            } catch (Throwable) {
                return $empty;
            }
        });
    }

    private static function clean(mixed $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, 80);
    }
}
