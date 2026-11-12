<?php

namespace App\Qr;

class Catalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::type('url', 'Tautan / URL', 'Link', 'Buka situs atau tautan apa pun.', [
                self::field('url', 'URL', 'url', true, ['placeholder' => 'https://contoh.com']),
            ]),
            self::type('appstore', 'Play Market / App Store', 'Store', 'Satu QR untuk Google Play, App Store, atau keduanya.', [
                self::field('play_url', 'Google Play', 'url', false, ['placeholder' => 'https://play.google.com/store/apps/details?id=...']),
                self::field('apple_url', 'App Store', 'url', false, ['placeholder' => 'https://apps.apple.com/app/id...']),
            ], ['any' => ['play_url', 'apple_url'], 'smart_default' => true]),
            self::type('text', 'Teks', 'AlignLeft', 'Teks polos yang muncul saat QR dipindai.', [
                self::field('body', 'Teks', 'textarea', true, ['placeholder' => 'Tulis pesan', 'max' => 1200]),
            ]),
            self::type('maps', 'Google Maps', 'MapPin', 'Buka lokasi di Google Maps.', [
                self::field('query', 'Alamat atau nama tempat', 'text', false, ['placeholder' => 'Monas, Jakarta']),
                self::field('lat', 'Lintang', 'text', false, ['placeholder' => '-6.175392']),
                self::field('lng', 'Bujur', 'text', false, ['placeholder' => '106.827153']),
            ], ['any' => ['query', 'lat']]),
            self::type('wifi', 'Wi-Fi', 'Wifi', 'Ponsel bisa langsung terhubung ke jaringan.', [
                self::field('ssid', 'Nama jaringan', 'text', true, ['placeholder' => 'WiFi-Rumah']),
                self::field('encryption', 'Keamanan', 'select', false, [
                    'default' => 'WPA',
                    'options' => [
                        ['value' => 'WPA', 'label' => 'WPA/WPA2'],
                        ['value' => 'WEP', 'label' => 'WEP'],
                        ['value' => 'nopass', 'label' => 'Tanpa sandi'],
                    ],
                ]),
                self::field('password', 'Sandi', 'password', false, ['placeholder' => 'Sandi Wi-Fi']),
                self::field('hidden', 'SSID tersembunyi', 'toggle', false),
            ]),
            self::fileType('audio', 'Audio', 'Music', 'audio/*,.mp3,.wav,.m4a,.ogg,.aac', 'Putar atau unduh berkas audio.'),
            self::fileType('pdf', 'PDF', 'FileText', 'application/pdf,.pdf', 'Bagikan dokumen PDF.'),
            self::type('whatsapp', 'WhatsApp', 'MessageCircle', 'Buka chat WhatsApp dengan pesan siap kirim.', [
                self::field('phone', 'Nomor', 'tel', true, ['placeholder' => '628123456789', 'help' => 'Pakai kode negara, tanpa tanda + atau 0.']),
                self::field('message', 'Pesan', 'textarea', false, ['placeholder' => 'Halo, saya mau bertanya', 'max' => 500]),
            ]),
            self::type('youtube', 'YouTube', 'Play', 'Tautan video, channel, atau @nama.', [
                self::field('value', 'Tautan atau @nama', 'text', true, ['placeholder' => '@nama atau https://youtu.be/...']),
            ]),
            self::type('facebook', 'Facebook', 'ThumbsUp', 'Halaman atau profil Facebook.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama.halaman']),
            ]),
            self::fileType('image', 'Gambar', 'Image', 'image/png,image/jpeg,image/webp,image/gif', 'Tampilkan gambar saat QR dibuka.'),
            self::type('instagram', 'Instagram', 'Camera', 'Profil Instagram.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('vcard', 'vCard (Kartu Nama Digital)', 'Contact', 'Simpan kontak ke buku telepon.', [
                self::field('first_name', 'Nama depan', 'text', false),
                self::field('last_name', 'Nama belakang', 'text', false),
                self::field('organization', 'Perusahaan', 'text', false),
                self::field('job', 'Jabatan', 'text', false),
                self::field('phone', 'Telepon', 'tel', false, ['placeholder' => '+628123456789']),
                self::field('email', 'Surel', 'email', false),
                self::field('website', 'Situs', 'url', false, ['placeholder' => 'https://']),
                self::field('address', 'Alamat', 'text', false),
                self::field('note', 'Catatan', 'textarea', false, ['max' => 300]),
            ], ['any' => ['first_name', 'last_name']]),
            self::type('telegram', 'Telegram', 'Send', 'Buka chat Telegram.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('email', 'Surel', 'Mail', 'Buka aplikasi email dengan penerima terisi.', [
                self::field('to', 'Kepada', 'email', true, ['placeholder' => 'nama@contoh.com']),
                self::field('subject', 'Subjek', 'text', false),
                self::field('body', 'Isi', 'textarea', false, ['max' => 800]),
            ]),
            self::type('booking', 'Pemesanan', 'CalendarClock', 'Detail pesanan atau tautan pemesanan.', [
                self::field('name', 'Nama pesanan', 'text', true, ['placeholder' => 'Meja 4, 2 orang']),
                self::field('when', 'Waktu', 'datetime', false),
                self::field('party', 'Jumlah', 'text', false, ['placeholder' => '2']),
                self::field('location', 'Lokasi', 'text', false),
                self::field('url', 'Tautan', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Catatan', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('phone', 'Panggilan Telepon', 'Phone', 'Langsung menelepon nomor ini.', [
                self::field('phone', 'Nomor', 'tel', true, ['placeholder' => '+628123456789']),
            ]),
            self::fileType('pptx', 'PPTX', 'Presentation', '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation', 'Bagikan presentasi.'),
            self::type('smart', 'Kode QR Cerdas', 'Sparkles', 'QR berisi tautan pendek. Tujuan bisa diganti tanpa mencetak ulang.', [
                self::field('url', 'URL tujuan', 'url', true, ['placeholder' => 'https://contoh.com']),
                self::field('label', 'Judul tampilan', 'text', false, ['placeholder' => 'Promo November']),
            ], ['force_smart' => true, 'smart_default' => true]),
            self::type('links', 'Daftar tautan', 'List', 'Satu halaman berisi beberapa tautan.', [
                self::field('headline', 'Judul halaman', 'text', false, ['placeholder' => 'Tautan saya']),
                self::field('links', 'Tautan', 'links', true),
            ], ['smart_default' => true]),
            self::type('tiktok', 'TikTok', 'Music2', 'Profil TikTok.', [
                self::field('value', 'Tautan atau @nama', 'text', true, ['placeholder' => '@nama']),
            ]),
            self::fileType('video', 'Video', 'Clapperboard', 'video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov', 'Putar video saat QR dibuka.'),
            self::type('googleforms', 'Google Forms', 'ClipboardList', 'Buka formulir Google.', [
                self::field('url', 'Tautan formulir', 'url', true, ['placeholder' => 'https://docs.google.com/forms/...']),
            ]),
            self::type('pcr', 'PCR', 'Activity', 'Kartu hasil yang bisa diperbarui lewat QR cerdas.', [
                self::field('name', 'Nama', 'text', true),
                self::field('code', 'Kode', 'text', true, ['placeholder' => 'PCR-001']),
                self::field('result', 'Hasil', 'text', false, ['placeholder' => 'Negatif']),
                self::field('date', 'Tanggal', 'text', false, ['placeholder' => '11 Nov 2026']),
                self::field('note', 'Catatan', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('twitter', 'X (Twitter)', 'AtSign', 'Profil X.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('snapchat', 'Snapchat', 'Ghost', 'Tambah teman Snapchat.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('spotify', 'Spotify', 'AudioLines', 'Lagu, album, atau artis.', [
                self::field('url', 'Tautan Spotify', 'url', true, ['placeholder' => 'https://open.spotify.com/track/...']),
            ]),
            self::type('googledoc', 'Google Doc', 'FileText', 'Buka dokumen Google.', [
                self::field('url', 'Tautan dokumen', 'url', true, ['placeholder' => 'https://docs.google.com/document/...']),
            ]),
            self::type('googlereview', 'Google Review', 'Star', 'Minta pengunjung menulis ulasan.', [
                self::field('value', 'Tautan atau Place ID', 'text', true, ['placeholder' => 'ChIJ... atau https://']),
            ]),
            self::type('googlesheets', 'Google Sheets', 'Sheet', 'Buka spreadsheet Google.', [
                self::field('url', 'Tautan spreadsheet', 'url', true, ['placeholder' => 'https://docs.google.com/spreadsheets/...']),
            ]),
            self::type('payment', 'Pembayaran', 'Wallet', 'Nominal dan tautan atau instruksi bayar.', [
                self::field('name', 'Nama', 'text', true, ['placeholder' => 'Invoice 014']),
                self::field('amount', 'Nominal', 'text', false, ['placeholder' => '150000']),
                self::field('currency', 'Mata uang', 'text', false, ['default' => 'IDR', 'placeholder' => 'IDR']),
                self::field('url', 'Tautan bayar', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Instruksi', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('sms', 'SMS', 'MessageSquare', 'Buka SMS dengan nomor dan pesan terisi.', [
                self::field('phone', 'Nomor', 'tel', true, ['placeholder' => '+628123456789']),
                self::field('message', 'Pesan', 'textarea', false, ['max' => 300]),
            ]),
            self::type('logo', 'Logo', 'ImagePlus', 'QR dengan logo di tengah. Unggah logo di panel tampilan.', [
                self::field('url', 'URL tujuan', 'url', true, ['placeholder' => 'https://contoh.com']),
            ], ['logo_required' => true]),
            self::type('office', 'Office 365', 'Cloud', 'Tautan Word, Excel, PowerPoint, atau OneDrive.', [
                self::field('url', 'Tautan Office', 'url', true, ['placeholder' => 'https://']),
            ]),
            self::type('shaped', 'Shaped', 'Shapes', 'QR dengan modul titik. Tetap bisa dipindai.', [
                self::field('url', 'URL', 'url', true, ['placeholder' => 'https://contoh.com']),
            ]),
            self::type('paypal', 'Paypal', 'CreditCard', 'Tautan PayPal.me.', [
                self::field('username', 'Nama PayPal.me', 'text', true, ['placeholder' => 'nama']),
                self::field('amount', 'Nominal', 'text', false, ['placeholder' => '10']),
                self::field('currency', 'Mata uang', 'text', false, ['default' => 'USD']),
            ]),
            self::type('etsy', 'Etsy', 'ShoppingBag', 'Toko atau produk Etsy.', [
                self::field('url', 'Tautan Etsy', 'url', true, ['placeholder' => 'https://www.etsy.com/shop/...']),
            ]),
            self::fileType('png', 'PNG', 'FileImage', 'image/png,.png', 'Bagikan berkas PNG.'),
            self::type('linkedin', 'LinkedIn', 'Briefcase', 'Profil LinkedIn.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('crypto', 'Pembayaran crypto', 'Bitcoin', 'Alamat dompet BTC, ETH, SOL, atau LTC.', [
                self::field('coin', 'Koin', 'select', false, [
                    'default' => 'BTC',
                    'options' => [
                        ['value' => 'BTC', 'label' => 'Bitcoin'],
                        ['value' => 'ETH', 'label' => 'Ethereum'],
                        ['value' => 'SOL', 'label' => 'Solana'],
                        ['value' => 'LTC', 'label' => 'Litecoin'],
                    ],
                ]),
                self::field('address', 'Alamat dompet', 'text', true),
                self::field('amount', 'Jumlah', 'text', false, ['placeholder' => '0.01']),
                self::field('label', 'Label', 'text', false),
            ]),
            self::type('calendar', 'Calendar', 'Calendar', 'Simpan acara ke kalender.', [
                self::field('title', 'Nama acara', 'text', true),
                self::field('start', 'Mulai', 'datetime', true),
                self::field('end', 'Selesai', 'datetime', false),
                self::field('location', 'Lokasi', 'text', false),
                self::field('details', 'Detail', 'textarea', false, ['max' => 500]),
            ]),
            self::type('social', 'Media Sosial', 'Share2', 'Kumpulkan profil sosial dalam satu halaman.', [
                self::field('headline', 'Judul', 'text', false, ['placeholder' => 'Ikuti kami']),
                self::field('instagram', 'Instagram', 'text', false, ['placeholder' => '@nama']),
                self::field('facebook', 'Facebook', 'text', false),
                self::field('tiktok', 'TikTok', 'text', false),
                self::field('youtube', 'YouTube', 'text', false),
                self::field('x', 'X', 'text', false),
                self::field('website', 'Situs', 'url', false, ['placeholder' => 'https://']),
            ], ['any' => ['instagram', 'facebook', 'tiktok', 'youtube', 'x', 'website'], 'smart_default' => true]),
            self::type('ticket', 'Tiket', 'Ticket', 'Tiket acara dengan kode yang bisa dicek.', [
                self::field('event', 'Acara', 'text', true),
                self::field('holder', 'Nama', 'text', false),
                self::field('seat', 'Kursi', 'text', false, ['placeholder' => 'A12']),
                self::field('code', 'Kode tiket', 'text', true),
                self::field('when', 'Waktu', 'text', false),
            ], ['smart_default' => true]),
            self::type('reddit', 'Reddit', 'Hash', 'Profil atau tautan Reddit.', [
                self::field('value', 'Tautan atau nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::fileType('file', 'Berkas', 'File', '', 'Unggah berkas atau tempel tautannya.'),
            self::fileType('excel', 'Excel', 'Table', '.xls,.xlsx,.csv', 'Bagikan spreadsheet.'),
            self::type('venmo', 'Venmo', 'Banknote', 'Permintaan bayar Venmo.', [
                self::field('username', 'Nama pengguna', 'text', true, ['placeholder' => 'nama']),
                self::field('amount', 'Nominal', 'text', false, ['placeholder' => '10']),
                self::field('note', 'Catatan', 'text', false),
            ]),
            self::type('amazon', 'Amazon', 'ShoppingCart', 'Halaman produk Amazon.', [
                self::field('url', 'Tautan produk', 'url', true, ['placeholder' => 'https://www.amazon.com/dp/...']),
            ]),
            self::type('product', 'Produk', 'Package', 'Nama, harga, dan tautan produk.', [
                self::field('name', 'Nama produk', 'text', true),
                self::field('sku', 'SKU', 'text', false),
                self::field('price', 'Harga', 'text', false, ['placeholder' => '99000']),
                self::field('currency', 'Mata uang', 'text', false, ['default' => 'IDR']),
                self::field('url', 'Tautan', 'url', false, ['placeholder' => 'https://']),
            ], ['smart_default' => true]),
            self::type('upi', 'UPI', 'QrCode', 'Permintaan bayar UPI India.', [
                self::field('pa', 'UPI ID', 'text', true, ['placeholder' => 'nama@upi']),
                self::field('pn', 'Nama penerima', 'text', true),
                self::field('amount', 'Nominal', 'text', false, ['placeholder' => '100']),
                self::field('note', 'Catatan', 'text', false),
            ]),
            self::type('attendance', 'Kehadiran', 'UserCheck', 'Peserta mengisi nama saat memindai QR.', [
                self::field('event', 'Nama acara', 'text', true),
                self::field('location', 'Lokasi', 'text', false),
                self::field('when', 'Waktu', 'text', false),
            ], ['force_smart' => true, 'smart_default' => true]),
            self::type('wechat', 'WeChat', 'MessageCircle', 'Tampilkan WeChat ID supaya mudah disalin.', [
                self::field('value', 'WeChat ID', 'text', true),
            ]),
            self::type('line', 'Line', 'MessageSquare', 'Tambah teman LINE.', [
                self::field('value', 'Tautan atau LINE ID', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('kakaotalk', 'KakaoTalk', 'MessageCircleMore', 'Tautan terbuka KakaoTalk atau ID.', [
                self::field('value', 'Tautan atau ID', 'text', true),
            ]),
            self::type('discord', 'Discord', 'MessagesSquare', 'Tautan undangan Discord.', [
                self::field('url', 'Tautan undangan', 'url', true, ['placeholder' => 'https://discord.gg/...']),
            ]),
            self::type('revolut', 'Revolut', 'Landmark', 'Tautan Revolut.me.', [
                self::field('username', 'Nama pengguna', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('soundcloud', 'SoundCloud', 'Cloud', 'Profil atau lagu SoundCloud.', [
                self::field('value', 'Tautan atau nama', 'text', true, ['placeholder' => 'nama']),
            ]),
            self::type('pix', 'PIX', 'QrCode', 'QR PIX statis untuk real Brasil.', [
                self::field('key', 'Kunci PIX', 'text', true, ['placeholder' => 'email, telepon, atau chave']),
                self::field('name', 'Nama penerima', 'text', true),
                self::field('city', 'Kota', 'text', false, ['default' => 'BRASILIA']),
                self::field('amount', 'Nominal BRL', 'text', false, ['placeholder' => '25.00']),
                self::field('note', 'Catatan', 'text', false),
            ]),
            self::type('multipay', 'Multi-Pembayaran', 'WalletCards', 'Beberapa cara bayar dalam satu halaman.', [
                self::field('headline', 'Judul', 'text', false, ['placeholder' => 'Bayar pesanan']),
                self::field('paypal', 'PayPal.me', 'text', false),
                self::field('upi', 'UPI ID', 'text', false),
                self::field('pix', 'Kunci PIX', 'text', false),
                self::field('crypto', 'Alamat crypto', 'text', false),
                self::field('url', 'Tautan lain', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Catatan', 'textarea', false, ['max' => 300]),
            ], ['any' => ['paypal', 'upi', 'pix', 'crypto', 'url'], 'smart_default' => true]),
            self::type('coupon', 'Kupon', 'BadgePercent', 'Kode kupon yang bisa diganti nanti.', [
                self::field('name', 'Nama promo', 'text', true, ['placeholder' => 'Diskon 10%']),
                self::field('code', 'Kode', 'text', true, ['placeholder' => 'HEMAT10']),
                self::field('expires', 'Berlaku sampai', 'text', false),
                self::field('url', 'Tautan', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Syarat', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('onlinebooking', 'Tempahan dalam Talian', 'CalendarCheck', 'Jadwal layanan atau tautan booking.', [
                self::field('service', 'Layanan', 'text', true, ['placeholder' => 'Konsultasi 30 menit']),
                self::field('when', 'Waktu', 'datetime', false),
                self::field('url', 'Tautan booking', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Catatan', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $flags
     * @return array<string, mixed>
     */
    private static function type(string $id, string $label, string $icon, string $hint, array $fields, array $flags = []): array
    {
        return array_merge([
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'hint' => $hint,
            'fields' => $fields,
            'any' => [],
            'file' => false,
            'accept' => '',
            'logo_required' => false,
            'smart_default' => false,
            'force_smart' => false,
        ], $flags);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fileType(string $id, string $label, string $icon, string $accept, string $hint): array
    {
        return self::type($id, $label, $icon, $hint, [
            self::field('name', 'Nama', 'text', false, ['placeholder' => $label]),
            self::field('url', 'Tautan berkas', 'url', false, ['placeholder' => 'https:// atau unggah berkas']),
        ], [
            'file' => true,
            'accept' => $accept,
            'any' => ['url', 'file'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function field(string $key, string $label, string $type, bool $required, array $extra = []): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'required' => $required,
            'placeholder' => '',
            'help' => '',
            'max' => $type === 'textarea' ? 1200 : 500,
            'default' => $type === 'toggle' ? false : '',
            'options' => [],
        ], $extra);
    }
}
