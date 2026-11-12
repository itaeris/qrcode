<?php

namespace App\Qr;

use Illuminate\Support\Str;

class PixPayload
{
    public static function make(string $key, string $name, string $city = '', string $amount = '', string $note = ''): string
    {
        $merchant = self::tlv('00', 'br.gov.bcb.pix').self::tlv('01', $key);
        $note = self::ascii($note, 40);

        if ($note !== '') {
            $merchant .= self::tlv('02', $note);
        }

        $name = self::ascii($name, 25);
        $city = self::ascii($city !== '' ? $city : 'BRASILIA', 15);

        if ($name === '') {
            $name = 'PAGAMENTO';
        }

        $payload = self::tlv('00', '01')
            .self::tlv('01', '11')
            .self::tlv('26', $merchant)
            .self::tlv('52', '0000')
            .self::tlv('53', '986');

        if ($amount !== '') {
            $payload .= self::tlv('54', $amount);
        }

        $payload .= self::tlv('58', 'BR')
            .self::tlv('59', $name)
            .self::tlv('60', $city)
            .self::tlv('62', self::tlv('05', '***'))
            .'6304';

        return $payload.self::crc16($payload);
    }

    public static function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($payload[$i]) << 8);

            for ($bit = 0; $bit < 8; $bit++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }

                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private static function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private static function ascii(string $value, int $limit): string
    {
        $value = Str::upper(Str::ascii($value));
        $value = preg_replace('/[^A-Z0-9 ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return substr($value, 0, $limit);
    }
}
