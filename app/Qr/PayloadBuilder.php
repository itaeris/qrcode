<?php

namespace App\Qr;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayloadBuilder
{
    /**
     * @param  array<string, mixed>  $fields
     */
    public function build(string $type, array $fields): BuiltQr
    {
        $built = match ($type) {
            'url', 'logo', 'shaped', 'smart' => $this->httpLink($this->string($fields, 'url'), $this->string($fields, 'label') ?: $this->host($this->string($fields, 'url')), 'url'),
            'appstore' => $this->appStore($fields),
            'text' => $this->plain($this->string($fields, 'body'), 'Teks'),
            'maps' => $this->maps($fields),
            'wifi' => $this->wifi($fields),
            'whatsapp' => $this->whatsapp($fields),
            'youtube' => $this->profile($this->string($fields, 'value'), 'https://www.youtube.com/@', 'YouTube'),
            'facebook' => $this->profile($this->string($fields, 'value'), 'https://www.facebook.com/', 'Facebook'),
            'instagram' => $this->profile($this->string($fields, 'value'), 'https://www.instagram.com/', 'Instagram'),
            'telegram' => $this->profile($this->string($fields, 'value'), 'https://t.me/', 'Telegram'),
            'twitter' => $this->profile($this->string($fields, 'value'), 'https://x.com/', 'X'),
            'snapchat' => $this->profile($this->string($fields, 'value'), 'https://www.snapchat.com/add/', 'Snapchat'),
            'linkedin' => $this->profile($this->string($fields, 'value'), 'https://www.linkedin.com/in/', 'LinkedIn'),
            'tiktok' => $this->profile($this->string($fields, 'value'), 'https://www.tiktok.com/@', 'TikTok'),
            'reddit' => $this->profile($this->string($fields, 'value'), 'https://www.reddit.com/user/', 'Reddit'),
            'soundcloud' => $this->profile($this->string($fields, 'value'), 'https://soundcloud.com/', 'SoundCloud'),
            'spotify' => $this->httpLink($this->string($fields, 'url'), 'Spotify', 'url'),
            'googledoc' => $this->httpLink($this->string($fields, 'url'), 'Google Doc', 'url'),
            'googlesheets' => $this->httpLink($this->string($fields, 'url'), 'Google Sheets', 'url'),
            'googleforms' => $this->httpLink($this->string($fields, 'url'), 'Google Forms', 'url'),
            'office' => $this->httpLink($this->string($fields, 'url'), 'Office 365', 'url'),
            'etsy' => $this->httpLink($this->string($fields, 'url'), 'Etsy', 'url'),
            'amazon' => $this->httpLink($this->string($fields, 'url'), 'Amazon', 'url'),
            'discord' => $this->httpLink($this->string($fields, 'url'), 'Discord', 'url'),
            'googlereview' => $this->review($fields),
            'email' => $this->email($fields),
            'phone' => $this->phone($fields),
            'sms' => $this->sms($fields),
            'vcard' => $this->vcard($fields),
            'calendar' => $this->calendar($fields),
            'paypal' => $this->paypal($fields),
            'venmo' => $this->venmo($fields),
            'revolut' => $this->httpLink('https://revolut.me/'.rawurlencode(trim($this->string($fields, 'username'), '@/ ')), 'Revolut', 'username'),
            'crypto' => $this->crypto($fields),
            'upi' => $this->upi($fields),
            'pix' => $this->pix($fields),
            'line' => $this->line($fields),
            'wechat' => $this->handleText($this->string($fields, 'value'), 'WeChat ID'),
            'kakaotalk' => $this->kakao($fields),
            'links' => $this->links($fields),
            'social' => $this->social($fields),
            'multipay' => $this->multipay($fields),
            'coupon' => $this->coupon($fields),
            'ticket' => $this->ticket($fields),
            'product' => $this->product($fields),
            'payment' => $this->payment($fields),
            'booking' => $this->booking($fields),
            'onlinebooking' => $this->onlineBooking($fields),
            'pcr' => $this->pcr($fields),
            'attendance' => $this->attendance($fields),
            'pdf', 'audio', 'image', 'png', 'video', 'pptx', 'excel', 'file' => $this->file($fields, (string) (Catalog::find($type)['label'] ?? 'Berkas')),
            default => throw ValidationException::withMessages(['type' => 'Jenis QR tidak dikenal.']),
        };

        if (strlen($built->text) > 1800) {
            throw ValidationException::withMessages([
                'fields' => 'Isi terlalu panjang untuk sebuah QR code.',
            ]);
        }

        return $built;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function appStore(array $fields): BuiltQr
    {
        $play = $this->optionalHttp($fields, 'play_url');
        $apple = $this->optionalHttp($fields, 'apple_url');
        $actions = [];

        if ($play) {
            $actions[] = ['label' => 'Google Play', 'href' => $play];
        }

        if ($apple) {
            $actions[] = ['label' => 'App Store', 'href' => $apple];
        }

        $primary = $play ?: $apple;

        return $this->content(
            $primary ?? 'Aplikasi',
            'Aplikasi',
            $this->present('Unduh aplikasi', null, null, [], $actions),
            count($actions) === 1 ? $primary : null,
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function maps(array $fields): BuiltQr
    {
        $lat = $this->string($fields, 'lat');
        $lng = $this->string($fields, 'lng');
        $query = $this->string($fields, 'query');

        if ($lat !== '' || $lng !== '') {
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                throw ValidationException::withMessages([
                    'fields.lat' => 'Isi lintang dan bujur dengan angka.',
                ]);
            }

            $query = $lat.','.$lng;
        }

        if ($query === '') {
            throw ValidationException::withMessages([
                'fields.query' => 'Isi alamat atau koordinat.',
            ]);
        }

        $url = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);

        return $this->content($url, $query, $this->present($query, 'Google Maps', null, [], [
            ['label' => 'Buka peta', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function wifi(array $fields): BuiltQr
    {
        $encryption = $this->string($fields, 'encryption');

        if (! in_array($encryption, ['WPA', 'WEP', 'nopass'], true)) {
            $encryption = 'WPA';
        }

        $ssid = $this->string($fields, 'ssid');
        $password = $encryption === 'nopass' ? '' : $this->string($fields, 'password');
        $hidden = filter_var($fields['hidden'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $text = 'WIFI:T:'.$encryption.';S:'.$this->wifiEscape($ssid).';P:'.$this->wifiEscape($password).';H:'.($hidden ? 'true' : 'false').';;';

        return $this->content($text, $ssid, $this->present($ssid, 'Jaringan Wi-Fi', 'Sandi ditampilkan supaya bisa disalin jika QR cerdas dipakai.', $this->rows([
            'Jaringan' => $ssid,
            'Keamanan' => $encryption === 'nopass' ? 'Tanpa sandi' : $encryption,
            'Sandi' => $password,
            'Tersembunyi' => $hidden ? 'Ya' : 'Tidak',
        ]), []));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function whatsapp(array $fields): BuiltQr
    {
        $phone = preg_replace('/\D+/', '', $this->string($fields, 'phone')) ?? '';

        if ($phone === '' || str_starts_with($phone, '0')) {
            throw ValidationException::withMessages([
                'fields.phone' => 'Gunakan kode negara, misalnya 628123456789.',
            ]);
        }

        $message = $this->string($fields, 'message');
        $url = 'https://wa.me/'.$phone.($message !== '' ? '?text='.rawurlencode($message) : '');

        return $this->content($url, 'WhatsApp '.$phone, $this->present('WhatsApp', '+'.$phone, $message !== '' ? $message : null, [], [
            ['label' => 'Buka WhatsApp', 'href' => $url],
        ]), $url);
    }

    private function profile(string $value, string $prefix, string $title): BuiltQr
    {
        $value = trim($value);

        if ($value === '') {
            throw ValidationException::withMessages(['fields.value' => 'Isi nama atau tautan.']);
        }

        if (preg_match('#^https?://#i', $value) || str_contains($value, '/')) {
            $url = $this->requireHttp($value, 'value', 'Tautan');
        } else {
            $handle = trim($value, "@/ \t");
            $url = $prefix.rawurlencode($handle);
        }

        return $this->content($url, $title, $this->present($title, $url, null, [], [
            ['label' => 'Buka '.$title, 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function review(array $fields): BuiltQr
    {
        $value = $this->string($fields, 'value');

        if (preg_match('#^https?://#i', $value) || str_contains($value, '/')) {
            $url = $this->requireHttp($value, 'value', 'Tautan');
        } else {
            $url = 'https://search.google.com/local/writereview?placeid='.rawurlencode($value);
        }

        return $this->content($url, 'Ulasan Google', $this->present('Tulis ulasan', null, null, [], [
            ['label' => 'Buka ulasan', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function email(array $fields): BuiltQr
    {
        $to = $this->string($fields, 'to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['fields.to' => 'Alamat surel tidak valid.']);
        }

        $query = array_filter([
            'subject' => $this->string($fields, 'subject'),
            'body' => $this->string($fields, 'body'),
        ], fn ($value) => $value !== '');

        $text = 'mailto:'.$to.($query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');

        return $this->content($text, $to, $this->present($to, 'Surel', $this->string($fields, 'body') ?: null, $this->rows([
            'Subjek' => $this->string($fields, 'subject'),
        ]), [
            ['label' => 'Tulis surel', 'href' => $text],
        ]));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function phone(array $fields): BuiltQr
    {
        $phone = $this->phoneNumber($this->string($fields, 'phone'));
        $text = 'tel:'.$phone;

        return $this->content($text, $phone, $this->present($phone, 'Panggilan telepon', null, [], [
            ['label' => 'Telepon', 'href' => $text],
        ]));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function sms(array $fields): BuiltQr
    {
        $phone = $this->phoneNumber($this->string($fields, 'phone'));
        $message = $this->string($fields, 'message');
        $text = 'sms:'.$phone.($message !== '' ? '?body='.rawurlencode($message) : '');

        return $this->content($text, $phone, $this->present($phone, 'SMS', $message !== '' ? $message : null, [], [
            ['label' => 'Tulis SMS', 'href' => $text],
        ]));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function vcard(array $fields): BuiltQr
    {
        $first = $this->string($fields, 'first_name');
        $last = $this->string($fields, 'last_name');
        $full = trim($first.' '.$last);
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$this->vcardEscape($last).';'.$this->vcardEscape($first).';;;',
            'FN:'.$this->vcardEscape($full),
        ];

        foreach ([
            'ORG' => $this->string($fields, 'organization'),
            'TITLE' => $this->string($fields, 'job'),
            'TEL;TYPE=CELL' => $this->string($fields, 'phone'),
            'EMAIL' => $this->string($fields, 'email'),
            'URL' => $this->optionalHttp($fields, 'website') ?? '',
            'NOTE' => $this->string($fields, 'note'),
        ] as $key => $value) {
            if ($value !== '') {
                $lines[] = $key.':'.$this->vcardEscape($value);
            }
        }

        $address = $this->string($fields, 'address');

        if ($address !== '') {
            $lines[] = 'ADR:;;'.$this->vcardEscape($address).';;;;';
        }

        $lines[] = 'END:VCARD';

        return $this->content(implode("\r\n", $lines), $full, $this->present($full, $this->string($fields, 'job') ?: $this->string($fields, 'organization'), $this->string($fields, 'note') ?: null, $this->rows([
            'Telepon' => $this->string($fields, 'phone'),
            'Surel' => $this->string($fields, 'email'),
            'Perusahaan' => $this->string($fields, 'organization'),
            'Situs' => $this->string($fields, 'website'),
            'Alamat' => $address,
        ]), []));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function calendar(array $fields): BuiltQr
    {
        $start = $this->icsDate($this->string($fields, 'start'), 'start');
        $endRaw = $this->string($fields, 'end');
        $end = $endRaw !== ''
            ? $this->icsDate($endRaw, 'end')
            : date('Ymd\THis', strtotime($this->string($fields, 'start').':00') + 3600);
        $title = $this->string($fields, 'title');
        $uid = Str::uuid()->toString().'@qr-generate';
        $stamp = gmdate('Ymd\THis\Z');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//QR Generate//ID',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$stamp,
            'DTSTART:'.$start,
            'DTEND:'.$end,
            'SUMMARY:'.$this->icsEscape($title),
        ];
        $location = $this->string($fields, 'location');
        $details = $this->string($fields, 'details');

        if ($location !== '') {
            $lines[] = 'LOCATION:'.$this->icsEscape($location);
        }

        if ($details !== '') {
            $lines[] = 'DESCRIPTION:'.$this->icsEscape($details);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return $this->content(implode("\r\n", $lines), $title, $this->present($title, $this->displayDate($this->string($fields, 'start')), $details ?: null, $this->rows([
            'Lokasi' => $location,
            'Selesai' => $endRaw !== '' ? $this->displayDate($endRaw) : '',
        ]), []));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function paypal(array $fields): BuiltQr
    {
        $user = trim($this->string($fields, 'username'), '@/ ');
        $amount = $this->string($fields, 'amount');
        $currency = strtoupper($this->string($fields, 'currency') ?: 'USD');
        $url = 'https://paypal.me/'.rawurlencode($user);

        if ($amount !== '') {
            $url .= '/'.rawurlencode($amount.$currency);
        }

        return $this->content($url, 'PayPal', $this->present('PayPal', $user, null, $this->rows([
            'Nominal' => $amount !== '' ? $amount.' '.$currency : '',
        ]), [
            ['label' => 'Bayar dengan PayPal', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function venmo(array $fields): BuiltQr
    {
        $user = trim($this->string($fields, 'username'), '@/ ');
        $query = array_filter([
            'txn' => 'pay',
            'amount' => $this->string($fields, 'amount'),
            'note' => $this->string($fields, 'note'),
        ], fn ($value) => $value !== '');
        $url = 'https://venmo.com/'.rawurlencode($user).'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $this->content($url, 'Venmo', $this->present('Venmo', $user, $this->string($fields, 'note') ?: null, $this->rows([
            'Nominal' => $this->string($fields, 'amount'),
        ]), [
            ['label' => 'Buka Venmo', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function crypto(array $fields): BuiltQr
    {
        $coin = strtoupper($this->string($fields, 'coin') ?: 'BTC');
        $scheme = match ($coin) {
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'LTC' => 'litecoin',
            default => 'bitcoin',
        };
        $address = $this->string($fields, 'address');

        if (strlen($address) < 8 || preg_match('/\s/', $address)) {
            throw ValidationException::withMessages(['fields.address' => 'Alamat dompet tidak valid.']);
        }

        $query = array_filter([
            'amount' => $this->string($fields, 'amount'),
            'label' => $this->string($fields, 'label'),
        ], fn ($value) => $value !== '');
        $text = $scheme.':'.$address.($query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');

        return $this->content($text, $coin.' '.$address, $this->present($coin, $address, null, $this->rows([
            'Jumlah' => $this->string($fields, 'amount'),
            'Label' => $this->string($fields, 'label'),
        ]), []));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function upi(array $fields): BuiltQr
    {
        $pa = $this->string($fields, 'pa');

        if (! str_contains($pa, '@')) {
            throw ValidationException::withMessages(['fields.pa' => 'UPI ID harus berisi @, misalnya nama@upi.']);
        }

        $query = array_filter([
            'pa' => $pa,
            'pn' => $this->string($fields, 'pn'),
            'am' => $this->string($fields, 'amount'),
            'cu' => 'INR',
            'tn' => $this->string($fields, 'note'),
        ], fn ($value) => $value !== '');
        $text = 'upi://pay?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $this->content($text, $this->string($fields, 'pn'), $this->present($this->string($fields, 'pn'), $pa, $this->string($fields, 'note') ?: null, $this->rows([
            'Nominal' => $this->string($fields, 'amount') !== '' ? $this->string($fields, 'amount').' INR' : '',
        ]), [
            ['label' => 'Bayar dengan UPI', 'href' => $text],
        ]));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function pix(array $fields): BuiltQr
    {
        $amount = $this->string($fields, 'amount');

        if ($amount !== '' && ! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['fields.amount' => 'Nominal PIX memakai titik, misalnya 25.00.']);
        }

        $text = PixPayload::make(
            $this->string($fields, 'key'),
            $this->string($fields, 'name'),
            $this->string($fields, 'city'),
            $amount,
            $this->string($fields, 'note'),
        );

        return $this->content($text, $this->string($fields, 'name'), $this->present($this->string($fields, 'name'), 'PIX', $this->string($fields, 'note') ?: null, $this->rows([
            'Kunci' => $this->string($fields, 'key'),
            'Kota' => $this->string($fields, 'city'),
            'Nominal' => $amount !== '' ? 'R$ '.$amount : '',
        ]), []));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function line(array $fields): BuiltQr
    {
        $value = $this->string($fields, 'value');

        if (preg_match('#^https?://#i', $value) || str_contains($value, '/')) {
            return $this->httpLink($value, 'LINE', 'value');
        }

        $url = 'https://line.me/ti/p/~'.rawurlencode(trim($value, '@/ '));

        return $this->content($url, 'LINE', $this->present('LINE', $value, null, [], [
            ['label' => 'Buka LINE', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function kakao(array $fields): BuiltQr
    {
        $value = $this->string($fields, 'value');

        if (preg_match('#^https?://#i', $value) || str_contains($value, '/')) {
            return $this->httpLink($value, 'KakaoTalk', 'value');
        }

        return $this->handleText($value, 'KakaoTalk');
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function links(array $fields): BuiltQr
    {
        $items = $this->linkItems($fields['links'] ?? []);
        $headline = $this->string($fields, 'headline') ?: 'Daftar tautan';
        $actions = array_map(fn ($item) => ['label' => $item['label'], 'href' => $item['url']], $items);
        $text = $items[0]['url'];

        return $this->content($text, $headline, $this->present($headline, count($items).' tautan', null, [], $actions));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function social(array $fields): BuiltQr
    {
        $map = [
            'instagram' => ['Instagram', 'https://www.instagram.com/'],
            'facebook' => ['Facebook', 'https://www.facebook.com/'],
            'tiktok' => ['TikTok', 'https://www.tiktok.com/@'],
            'youtube' => ['YouTube', 'https://www.youtube.com/@'],
            'x' => ['X', 'https://x.com/'],
        ];
        $actions = [];

        foreach ($map as $key => [$label, $prefix]) {
            $value = $this->string($fields, $key);

            if ($value === '') {
                continue;
            }

            $actions[] = ['label' => $label, 'href' => $this->profile($value, $prefix, $label)->url];
        }

        $website = $this->optionalHttp($fields, 'website');

        if ($website) {
            $actions[] = ['label' => 'Situs', 'href' => $website];
        }

        if ($actions === []) {
            throw ValidationException::withMessages(['fields' => 'Isi minimal satu akun.']);
        }

        $headline = $this->string($fields, 'headline') ?: 'Media sosial';

        return $this->content($actions[0]['href'], $headline, $this->present($headline, null, null, [], $actions));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function multipay(array $fields): BuiltQr
    {
        $actions = [];
        $paypal = trim($this->string($fields, 'paypal'), '@/ ');

        if ($paypal !== '') {
            $href = str_starts_with($paypal, 'http') ? $this->requireHttp($paypal, 'paypal', 'PayPal') : 'https://paypal.me/'.rawurlencode($paypal);
            $actions[] = ['label' => 'PayPal', 'href' => $href];
        }

        $upi = $this->string($fields, 'upi');

        if ($upi !== '') {
            $actions[] = ['label' => 'UPI '.$upi, 'href' => 'upi://pay?'.http_build_query(['pa' => $upi, 'cu' => 'INR'], '', '&', PHP_QUERY_RFC3986)];
        }

        $pix = $this->string($fields, 'pix');

        if ($pix !== '') {
            $actions[] = ['label' => 'PIX', 'href' => 'https://'];
        }

        $crypto = $this->string($fields, 'crypto');
        $url = $this->optionalHttp($fields, 'url');

        if ($url) {
            $actions[] = ['label' => 'Tautan bayar', 'href' => $url];
        }

        $rows = $this->rows([
            'PIX' => $pix,
            'Crypto' => $crypto,
        ]);
        $headline = $this->string($fields, 'headline') ?: 'Pembayaran';
        $actions = array_values(array_filter($actions, fn ($action) => $action['href'] !== 'https://'));
        $text = $url ?: ($actions[0]['href'] ?? $this->summary($headline, $rows, $this->string($fields, 'note')));

        return $this->content($text, $headline, $this->present($headline, null, $this->string($fields, 'note') ?: null, $rows, $actions));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function coupon(array $fields): BuiltQr
    {
        $name = $this->string($fields, 'name');
        $code = $this->string($fields, 'code');
        $url = $this->optionalHttp($fields, 'url');
        $text = $name."\nKode: ".$code;
        $expires = $this->string($fields, 'expires');

        if ($expires !== '') {
            $text .= "\nBerlaku sampai: ".$expires;
        }

        return $this->content($text, $name, $this->present($name, $expires !== '' ? 'Berlaku sampai '.$expires : null, $this->string($fields, 'note') ?: null, [], $url ? [['label' => 'Pakai kupon', 'href' => $url]] : [], $code));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function ticket(array $fields): BuiltQr
    {
        $event = $this->string($fields, 'event');
        $code = $this->string($fields, 'code');
        $text = $event."\nTiket: ".$code;

        return $this->content($text, $event, $this->present($event, $this->string($fields, 'when') ?: null, null, $this->rows([
            'Nama' => $this->string($fields, 'holder'),
            'Kursi' => $this->string($fields, 'seat'),
        ]), [], $code));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function product(array $fields): BuiltQr
    {
        $name = $this->string($fields, 'name');
        $price = trim($this->string($fields, 'price').' '.$this->string($fields, 'currency'));
        $url = $this->optionalHttp($fields, 'url');
        $rows = $this->rows([
            'SKU' => $this->string($fields, 'sku'),
            'Harga' => $price,
        ]);
        $text = $url ?: $this->summary($name, $rows, '');

        return $this->content($text, $name, $this->present($name, $price !== '' ? $price : null, null, $rows, $url ? [['label' => 'Lihat produk', 'href' => $url]] : []), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function payment(array $fields): BuiltQr
    {
        $name = $this->string($fields, 'name');
        $amount = trim($this->string($fields, 'amount').' '.$this->string($fields, 'currency'));
        $url = $this->optionalHttp($fields, 'url');
        $text = $url ?: trim($name."\n".$amount."\n".$this->string($fields, 'note'));

        return $this->content($text, $name, $this->present($name, $amount !== '' ? $amount : null, $this->string($fields, 'note') ?: null, [], $url ? [['label' => 'Bayar', 'href' => $url]] : []), $url);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function booking(array $fields): BuiltQr
    {
        $name = $this->string($fields, 'name');
        $url = $this->optionalHttp($fields, 'url');
        $rows = $this->rows([
            'Waktu' => $this->displayDate($this->string($fields, 'when')),
            'Jumlah' => $this->string($fields, 'party'),
            'Lokasi' => $this->string($fields, 'location'),
        ]);
        $onlyUrl = $url && $rows === [] && $this->string($fields, 'note') === '';

        return $this->content($url ?: $this->summary($name, $rows, $this->string($fields, 'note')), $name, $this->present($name, null, $this->string($fields, 'note') ?: null, $rows, $url ? [['label' => 'Buka pemesanan', 'href' => $url]] : []), $onlyUrl ? $url : null);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function onlineBooking(array $fields): BuiltQr
    {
        $service = $this->string($fields, 'service');
        $url = $this->optionalHttp($fields, 'url');
        $when = $this->displayDate($this->string($fields, 'when'));
        $onlyUrl = $url && $when === '' && $this->string($fields, 'note') === '';

        return $this->content($url ?: $this->summary($service, $this->rows(['Waktu' => $when]), $this->string($fields, 'note')), $service, $this->present($service, $when ?: null, $this->string($fields, 'note') ?: null, [], $url ? [['label' => 'Booking', 'href' => $url]] : []), $onlyUrl ? $url : null);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function pcr(array $fields): BuiltQr
    {
        $name = $this->string($fields, 'name');
        $code = $this->string($fields, 'code');

        return $this->content($this->summary($name, $this->rows([
            'Kode' => $code,
            'Hasil' => $this->string($fields, 'result'),
            'Tanggal' => $this->string($fields, 'date'),
        ]), $this->string($fields, 'note')), $name, $this->present($name, $this->string($fields, 'result') ?: null, $this->string($fields, 'note') ?: null, $this->rows([
            'Tanggal' => $this->string($fields, 'date'),
        ]), [], $code));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function attendance(array $fields): BuiltQr
    {
        $event = $this->string($fields, 'event');

        return $this->content($event, $event, $this->present($event, 'Isi nama untuk mencatat kehadiran', null, $this->rows([
            'Lokasi' => $this->string($fields, 'location'),
            'Waktu' => $this->string($fields, 'when'),
        ]), [], null, true));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function file(array $fields, string $label): BuiltQr
    {
        $name = $this->string($fields, 'name') ?: $label;
        $url = $this->optionalHttp($fields, 'url');

        if ($url) {
            return $this->content($url, $name, $this->present($name, $label, null, [], [
                ['label' => 'Buka berkas', 'href' => $url],
            ]), $url);
        }

        return $this->content($name, $name, $this->present($name, $label, 'Berkas tersedia di halaman ini.', [], []));
    }

    private function plain(string $text, string $title): BuiltQr
    {
        return $this->content($text, Str::limit($text, 48, ''), $this->present($title, null, $text, [], []));
    }

    private function handleText(string $value, string $label): BuiltQr
    {
        $text = $label.': '.$value;

        return $this->content($text, $value, $this->present($value, $label, null, [], []), null);
    }

    private function httpLink(string $value, string $title, string $field): BuiltQr
    {
        $url = $this->requireHttp($value, $field, 'URL');
        $headline = $title !== '' ? $title : $this->host($url);

        return $this->content($url, $headline, $this->present($headline, $url, null, [], [
            ['label' => 'Buka tautan', 'href' => $url],
        ]), $url);
    }

    /**
     * @param  array<int, array{label: string, href: string}>  $actions
     * @param  array<int, array{label: string, value: string}>  $rows
     */
    private function content(string $text, string $title, array $presentation, ?string $redirect = null): BuiltQr
    {
        return new BuiltQr(
            text: str_replace("\0", '', $text),
            kind: $redirect ? 'redirect' : 'page',
            title: $title !== '' ? $title : 'QR',
            url: $redirect,
            presentation: $presentation,
        );
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $rows
     * @param  array<int, array{label: string, href: string}>  $actions
     * @return array<string, mixed>
     */
    private function present(string $headline, ?string $subline, ?string $body, array $rows, array $actions, ?string $code = null, bool $checkIn = false): array
    {
        $actions = array_values(array_filter($actions, function ($action) {
            return isset($action['href']) && SafeUrl::href($action['href']);
        }));

        return [
            'headline' => $headline,
            'subline' => $subline ?: null,
            'body' => $body ?: null,
            'rows' => $rows,
            'actions' => array_map(fn ($action) => [
                'label' => $action['label'],
                'href' => SafeUrl::href($action['href']),
            ], $actions),
            'code' => $code,
            'check_in' => $checkIn,
            'media' => null,
        ];
    }

    /**
     * @param  array<string, string>  $pairs
     * @return array<int, array{label: string, value: string}>
     */
    private function rows(array $pairs): array
    {
        $rows = [];

        foreach ($pairs as $label => $value) {
            $value = trim($value);

            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $rows
     */
    private function summary(string $title, array $rows, string $note): string
    {
        $lines = [$title];

        foreach ($rows as $row) {
            $lines[] = $row['label'].': '.$row['value'];
        }

        if ($note !== '') {
            $lines[] = $note;
        }

        return implode("\n", array_filter($lines, fn ($line) => trim($line) !== ''));
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function linkItems(mixed $items): array
    {
        if (! is_array($items)) {
            throw ValidationException::withMessages(['fields.links' => 'Tambahkan minimal satu tautan.']);
        }

        $links = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = SafeUrl::normalizeHttp((string) ($item['url'] ?? ''));

            if (! $url) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $links[] = ['label' => $label !== '' ? $label : $this->host($url), 'url' => $url];
        }

        if ($links === []) {
            throw ValidationException::withMessages(['fields.links' => 'Tambahkan minimal satu tautan yang valid.']);
        }

        return $links;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function optionalHttp(array $fields, string $key): ?string
    {
        $value = $this->string($fields, $key);

        if ($value === '') {
            return null;
        }

        return $this->requireHttp($value, $key, 'URL');
    }

    private function requireHttp(string $value, string $field, string $label): string
    {
        $url = SafeUrl::normalizeHttp($value);

        if (! $url) {
            throw ValidationException::withMessages([
                'fields.'.$field => $label.' harus berupa tautan http atau https.',
            ]);
        }

        return $url;
    }

    private function phoneNumber(string $value): string
    {
        $value = trim($value);
        $plus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            throw ValidationException::withMessages(['fields.phone' => 'Nomor telepon tidak valid.']);
        }

        return ($plus ? '+' : '').$digits;
    }

    private function wifiEscape(string $value): string
    {
        return str_replace(['\\', ';', ',', ':', '"'], ['\\\\', '\\;', '\\,', '\\:', '\\"'], $value);
    }

    private function vcardEscape(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        return str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $value);
    }

    private function icsEscape(string $value): string
    {
        return str_replace(["\r\n", "\n", ',', ';'], ['\\n', '\\n', '\\,', '\\;'], $value);
    }

    private function icsDate(string $value, string $field): string
    {
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            throw ValidationException::withMessages([
                'fields.'.$field => 'Waktu tidak valid.',
            ]);
        }

        return date('Ymd\THis', $timestamp);
    }

    private function displayDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return $value;
        }

        return date('d M Y H:i', $timestamp);
    }

    private function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $url;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function string(array $fields, string $key): string
    {
        $value = $fields[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
