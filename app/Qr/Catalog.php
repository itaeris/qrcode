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
            self::type('url', 'Link / URL', 'Link', 'Open a site or any other link.', [
                self::field('url', 'URL', 'url', true, ['placeholder' => 'https://example.com']),
            ]),
            self::type('appstore', 'Play Market / App Store', 'Store', 'One QR for Google Play, the App Store, or both.', [
                self::field('play_url', 'Google Play', 'url', false, ['placeholder' => 'https://play.google.com/store/apps/details?id=...']),
                self::field('apple_url', 'App Store', 'url', false, ['placeholder' => 'https://apps.apple.com/app/id...']),
            ], ['any' => ['play_url', 'apple_url'], 'smart_default' => true]),
            self::type('text', 'Text', 'AlignLeft', 'Plain text shown when the QR is scanned.', [
                self::field('body', 'Text', 'textarea', true, ['placeholder' => 'Write a message', 'max' => 1200]),
            ]),
            self::type('maps', 'Google Maps', 'MapPin', 'Open a location in Google Maps.', [
                self::field('query', 'Address or place name', 'text', false, ['placeholder' => 'Central Park, New York']),
                self::field('lat', 'Latitude', 'text', false, ['placeholder' => '-6.175392']),
                self::field('lng', 'Longitude', 'text', false, ['placeholder' => '106.827153']),
            ], ['any' => ['query', 'lat']]),
            self::type('wifi', 'Wi-Fi', 'Wifi', 'Phones can join the network directly.', [
                self::field('ssid', 'Network name', 'text', true, ['placeholder' => 'Home-WiFi']),
                self::field('encryption', 'Security', 'select', false, [
                    'default' => 'WPA',
                    'options' => [
                        ['value' => 'WPA', 'label' => 'WPA/WPA2'],
                        ['value' => 'WEP', 'label' => 'WEP'],
                        ['value' => 'nopass', 'label' => 'No password'],
                    ],
                ]),
                self::field('password', 'Password', 'password', false, ['placeholder' => 'Wi-Fi password']),
                self::field('hidden', 'Hidden SSID', 'toggle', false),
            ]),
            self::fileType('audio', 'Audio', 'Music', 'audio/*,.mp3,.wav,.m4a,.ogg,.aac', 'Play or download an audio file.'),
            self::fileType('pdf', 'PDF', 'FileText', 'application/pdf,.pdf', 'Share a PDF document.'),
            self::type('whatsapp', 'WhatsApp', 'MessageCircle', 'Open a WhatsApp chat with a ready-to-send message.', [
                self::field('phone', 'Number', 'tel', true, ['placeholder' => '628123456789', 'help' => 'Use a country code, without + or a leading 0.']),
                self::field('message', 'Message', 'textarea', false, ['placeholder' => 'Hi, I have a question', 'max' => 500]),
            ]),
            self::type('youtube', 'YouTube', 'Play', 'A video, channel, or @name link.', [
                self::field('value', 'Link or @name', 'text', true, ['placeholder' => '@name or https://youtu.be/...']),
            ]),
            self::type('facebook', 'Facebook', 'ThumbsUp', 'A Facebook page or profile.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'page.name']),
            ]),
            self::fileType('image', 'Image', 'Image', 'image/png,image/jpeg,image/webp,image/gif', 'Show an image when the QR is opened.'),
            self::type('instagram', 'Instagram', 'Camera', 'An Instagram profile.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('vcard', 'vCard (Digital business card)', 'Contact', 'Save the contact to the phone book.', [
                self::field('first_name', 'First name', 'text', false),
                self::field('last_name', 'Last name', 'text', false),
                self::field('organization', 'Company', 'text', false),
                self::field('job', 'Job title', 'text', false),
                self::field('phone', 'Phone', 'tel', false, ['placeholder' => '+628123456789']),
                self::field('email', 'Email', 'email', false),
                self::field('website', 'Website', 'url', false, ['placeholder' => 'https://']),
                self::field('address', 'Address', 'text', false),
                self::field('note', 'Note', 'textarea', false, ['max' => 300]),
            ], ['any' => ['first_name', 'last_name']]),
            self::type('telegram', 'Telegram', 'Send', 'Open a Telegram chat.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('email', 'Email', 'Mail', 'Open the email app with the recipient filled in.', [
                self::field('to', 'To', 'email', true, ['placeholder' => 'name@example.com']),
                self::field('subject', 'Subject', 'text', false),
                self::field('body', 'Body', 'textarea', false, ['max' => 800]),
            ]),
            self::type('booking', 'Booking', 'CalendarClock', 'Order details or a booking link.', [
                self::field('name', 'Order name', 'text', true, ['placeholder' => 'Table 4, 2 people']),
                self::field('when', 'Time', 'datetime', false),
                self::field('party', 'Party size', 'text', false, ['placeholder' => '2']),
                self::field('location', 'Location', 'text', false),
                self::field('url', 'Link', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Note', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('phone', 'Phone call', 'Phone', 'Call this number directly.', [
                self::field('phone', 'Number', 'tel', true, ['placeholder' => '+628123456789']),
            ]),
            self::fileType('pptx', 'PPTX', 'Presentation', '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation', 'Share a presentation.'),
            self::type('smart', 'Smart QR code', 'Sparkles', 'A short link. The destination can change without reprinting.', [
                self::field('url', 'Destination URL', 'url', true, ['placeholder' => 'https://example.com']),
                self::field('label', 'Display title', 'text', false, ['placeholder' => 'November promo']),
            ], ['force_smart' => true, 'smart_default' => true]),
            self::type('links', 'Link list', 'List', 'One page with several links.', [
                self::field('headline', 'Page title', 'text', false, ['placeholder' => 'My links']),
                self::field('links', 'Links', 'links', true),
            ], ['smart_default' => true]),
            self::type('tiktok', 'TikTok', 'Music2', 'A TikTok profile.', [
                self::field('value', 'Link or @name', 'text', true, ['placeholder' => '@name']),
            ]),
            self::fileType('video', 'Video', 'Clapperboard', 'video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov', 'Play a video when the QR is opened.'),
            self::type('googleforms', 'Google Forms', 'ClipboardList', 'Open a Google form.', [
                self::field('url', 'Form link', 'url', true, ['placeholder' => 'https://docs.google.com/forms/...']),
            ]),
            self::type('pcr', 'PCR', 'Activity', 'A result card that can be updated through the smart QR.', [
                self::field('name', 'Name', 'text', true),
                self::field('code', 'Code', 'text', true, ['placeholder' => 'PCR-001']),
                self::field('result', 'Result', 'text', false, ['placeholder' => 'Negative']),
                self::field('date', 'Date', 'text', false, ['placeholder' => '11 Nov 2026']),
                self::field('note', 'Note', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('twitter', 'X (Twitter)', 'AtSign', 'An X profile.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('snapchat', 'Snapchat', 'Ghost', 'Add a Snapchat friend.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('spotify', 'Spotify', 'AudioLines', 'A song, album, or artist.', [
                self::field('url', 'Spotify link', 'url', true, ['placeholder' => 'https://open.spotify.com/track/...']),
            ]),
            self::type('googledoc', 'Google Doc', 'FileText', 'Open a Google document.', [
                self::field('url', 'Document link', 'url', true, ['placeholder' => 'https://docs.google.com/document/...']),
            ]),
            self::type('googlereview', 'Google Review', 'Star', 'Ask visitors to write a review.', [
                self::field('value', 'Link or Place ID', 'text', true, ['placeholder' => 'ChIJ... or https://']),
            ]),
            self::type('googlesheets', 'Google Sheets', 'Sheet', 'Open a Google spreadsheet.', [
                self::field('url', 'Spreadsheet link', 'url', true, ['placeholder' => 'https://docs.google.com/spreadsheets/...']),
            ]),
            self::type('payment', 'Payment', 'Wallet', 'An amount plus a pay link or instructions.', [
                self::field('name', 'Name', 'text', true, ['placeholder' => 'Invoice 014']),
                self::field('amount', 'Amount', 'text', false, ['placeholder' => '150000']),
                self::field('currency', 'Currency', 'text', false, ['default' => 'IDR', 'placeholder' => 'IDR']),
                self::field('url', 'Pay link', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Instructions', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('sms', 'SMS', 'MessageSquare', 'Open an SMS with the number and message filled in.', [
                self::field('phone', 'Number', 'tel', true, ['placeholder' => '+628123456789']),
                self::field('message', 'Message', 'textarea', false, ['max' => 300]),
            ]),
            self::type('logo', 'Logo', 'ImagePlus', 'A QR with a logo in the center. Upload the logo in the appearance panel.', [
                self::field('url', 'Destination URL', 'url', true, ['placeholder' => 'https://example.com']),
            ], ['logo_required' => true]),
            self::type('office', 'Office 365', 'Cloud', 'A Word, Excel, PowerPoint, or OneDrive link.', [
                self::field('url', 'Office link', 'url', true, ['placeholder' => 'https://']),
            ]),
            self::type('shaped', 'Shaped', 'Shapes', 'A QR with dot modules. It can still be scanned.', [
                self::field('url', 'URL', 'url', true, ['placeholder' => 'https://example.com']),
            ]),
            self::type('paypal', 'Paypal', 'CreditCard', 'A PayPal.me link.', [
                self::field('username', 'PayPal.me name', 'text', true, ['placeholder' => 'name']),
                self::field('amount', 'Amount', 'text', false, ['placeholder' => '10']),
                self::field('currency', 'Currency', 'text', false, ['default' => 'USD']),
            ]),
            self::type('etsy', 'Etsy', 'ShoppingBag', 'An Etsy shop or product.', [
                self::field('url', 'Etsy link', 'url', true, ['placeholder' => 'https://www.etsy.com/shop/...']),
            ]),
            self::fileType('png', 'PNG', 'FileImage', 'image/png,.png', 'Share a PNG file.'),
            self::type('linkedin', 'LinkedIn', 'Briefcase', 'A LinkedIn profile.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('crypto', 'Crypto payment', 'Bitcoin', 'A BTC, ETH, SOL, or LTC wallet address.', [
                self::field('coin', 'Coin', 'select', false, [
                    'default' => 'BTC',
                    'options' => [
                        ['value' => 'BTC', 'label' => 'Bitcoin'],
                        ['value' => 'ETH', 'label' => 'Ethereum'],
                        ['value' => 'SOL', 'label' => 'Solana'],
                        ['value' => 'LTC', 'label' => 'Litecoin'],
                    ],
                ]),
                self::field('address', 'Wallet address', 'text', true),
                self::field('amount', 'Amount', 'text', false, ['placeholder' => '0.01']),
                self::field('label', 'Label', 'text', false),
            ]),
            self::type('calendar', 'Calendar', 'Calendar', 'Save an event to the calendar.', [
                self::field('title', 'Event name', 'text', true),
                self::field('start', 'Start', 'datetime', true),
                self::field('end', 'End', 'datetime', false),
                self::field('location', 'Location', 'text', false),
                self::field('details', 'Details', 'textarea', false, ['max' => 500]),
            ]),
            self::type('social', 'Social media', 'Share2', 'Collect social profiles on one page.', [
                self::field('headline', 'Title', 'text', false, ['placeholder' => 'Follow us']),
                self::field('instagram', 'Instagram', 'text', false, ['placeholder' => '@name']),
                self::field('facebook', 'Facebook', 'text', false),
                self::field('tiktok', 'TikTok', 'text', false),
                self::field('youtube', 'YouTube', 'text', false),
                self::field('x', 'X', 'text', false),
                self::field('website', 'Website', 'url', false, ['placeholder' => 'https://']),
            ], ['any' => ['instagram', 'facebook', 'tiktok', 'youtube', 'x', 'website'], 'smart_default' => true]),
            self::type('ticket', 'Ticket', 'Ticket', 'An event ticket with a code that can be checked.', [
                self::field('event', 'Event', 'text', true),
                self::field('holder', 'Name', 'text', false),
                self::field('seat', 'Seat', 'text', false, ['placeholder' => 'A12']),
                self::field('code', 'Ticket code', 'text', true),
                self::field('when', 'Time', 'text', false),
            ], ['smart_default' => true]),
            self::type('reddit', 'Reddit', 'Hash', 'A Reddit profile or link.', [
                self::field('value', 'Link or username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::fileType('file', 'File', 'File', '', 'Upload a file or paste its link.'),
            self::fileType('excel', 'Excel', 'Table', '.xls,.xlsx,.csv', 'Share a spreadsheet.'),
            self::type('venmo', 'Venmo', 'Banknote', 'A Venmo payment request.', [
                self::field('username', 'Username', 'text', true, ['placeholder' => 'name']),
                self::field('amount', 'Amount', 'text', false, ['placeholder' => '10']),
                self::field('note', 'Note', 'text', false),
            ]),
            self::type('amazon', 'Amazon', 'ShoppingCart', 'An Amazon product page.', [
                self::field('url', 'Product link', 'url', true, ['placeholder' => 'https://www.amazon.com/dp/...']),
            ]),
            self::type('product', 'Product', 'Package', 'A product name, price, and link.', [
                self::field('name', 'Product name', 'text', true),
                self::field('sku', 'SKU', 'text', false),
                self::field('price', 'Price', 'text', false, ['placeholder' => '99000']),
                self::field('currency', 'Currency', 'text', false, ['default' => 'IDR']),
                self::field('url', 'Link', 'url', false, ['placeholder' => 'https://']),
            ], ['smart_default' => true]),
            self::type('upi', 'UPI', 'QrCode', 'An Indian UPI payment request.', [
                self::field('pa', 'UPI ID', 'text', true, ['placeholder' => 'name@upi']),
                self::field('pn', 'Payee name', 'text', true),
                self::field('amount', 'Amount', 'text', false, ['placeholder' => '100']),
                self::field('note', 'Note', 'text', false),
            ]),
            self::type('attendance', 'Attendance', 'UserCheck', 'Guests enter their name when they scan the QR.', [
                self::field('event', 'Event name', 'text', true),
                self::field('location', 'Location', 'text', false),
                self::field('when', 'Time', 'text', false),
            ], ['force_smart' => true, 'smart_default' => true]),
            self::type('wechat', 'WeChat', 'MessageCircle', 'Show a WeChat ID so it is easy to copy.', [
                self::field('value', 'WeChat ID', 'text', true),
            ]),
            self::type('line', 'Line', 'MessageSquare', 'Add a LINE friend.', [
                self::field('value', 'Link or LINE ID', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('kakaotalk', 'KakaoTalk', 'MessageCircleMore', 'A KakaoTalk open link or ID.', [
                self::field('value', 'Link or ID', 'text', true),
            ]),
            self::type('discord', 'Discord', 'MessagesSquare', 'A Discord invite link.', [
                self::field('url', 'Invite link', 'url', true, ['placeholder' => 'https://discord.gg/...']),
            ]),
            self::type('revolut', 'Revolut', 'Landmark', 'A Revolut.me link.', [
                self::field('username', 'Username', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('soundcloud', 'SoundCloud', 'Cloud', 'A SoundCloud profile or track.', [
                self::field('value', 'Link or name', 'text', true, ['placeholder' => 'name']),
            ]),
            self::type('pix', 'PIX', 'QrCode', 'A static PIX QR for Brazilian real.', [
                self::field('key', 'PIX key', 'text', true, ['placeholder' => 'email, phone, or key']),
                self::field('name', 'Payee name', 'text', true),
                self::field('city', 'City', 'text', false, ['default' => 'BRASILIA']),
                self::field('amount', 'Amount BRL', 'text', false, ['placeholder' => '25.00']),
                self::field('note', 'Note', 'text', false),
            ]),
            self::type('multipay', 'Multi-payment', 'WalletCards', 'Several ways to pay on one page.', [
                self::field('headline', 'Title', 'text', false, ['placeholder' => 'Pay the order']),
                self::field('paypal', 'PayPal.me', 'text', false),
                self::field('upi', 'UPI ID', 'text', false),
                self::field('pix', 'PIX key', 'text', false),
                self::field('crypto', 'Crypto address', 'text', false),
                self::field('url', 'Other link', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Note', 'textarea', false, ['max' => 300]),
            ], ['any' => ['paypal', 'upi', 'pix', 'crypto', 'url'], 'smart_default' => true]),
            self::type('coupon', 'Coupon', 'BadgePercent', 'A coupon code that can be changed later.', [
                self::field('name', 'Promo name', 'text', true, ['placeholder' => '10% off']),
                self::field('code', 'Code', 'text', true, ['placeholder' => 'SAVE10']),
                self::field('expires', 'Valid until', 'text', false),
                self::field('url', 'Link', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Terms', 'textarea', false, ['max' => 400]),
            ], ['smart_default' => true]),
            self::type('onlinebooking', 'Online booking', 'CalendarCheck', 'A service schedule or booking link.', [
                self::field('service', 'Service', 'text', true, ['placeholder' => '30-minute consultation']),
                self::field('when', 'Time', 'datetime', false),
                self::field('url', 'Booking link', 'url', false, ['placeholder' => 'https://']),
                self::field('note', 'Note', 'textarea', false, ['max' => 400]),
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
            self::field('name', 'Name', 'text', false, ['placeholder' => $label]),
            self::field('url', 'File link', 'url', false, ['placeholder' => 'https:// or upload a file']),
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
