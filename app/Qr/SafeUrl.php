<?php

namespace App\Qr;

class SafeUrl
{
    public static function normalizeHttp(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            $value = 'https://'.$value;
        }

        if (! preg_match('#^https?://#i', $value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return $value;
    }

    public static function href(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return $value;
        }

        $http = self::normalizeHttp($value);

        if ($http) {
            return $http;
        }

        $schemes = ['mailto:', 'tel:', 'sms:', 'upi:', 'bitcoin:', 'ethereum:', 'litecoin:', 'solana:', 'geo:'];

        foreach ($schemes as $scheme) {
            if (str_starts_with(strtolower($value), $scheme)) {
                return $value;
            }
        }

        return null;
    }
}
