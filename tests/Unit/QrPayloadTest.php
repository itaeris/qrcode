<?php

namespace Tests\Unit;

use App\Qr\PayloadBuilder;
use App\Qr\PixPayload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QrPayloadTest extends TestCase
{
    #[Test]
    public function it_builds_a_wifi_payload(): void
    {
        $built = app(PayloadBuilder::class)->build('wifi', [
            'ssid' => 'Ru;mah',
            'password' => 'rahasia',
            'encryption' => 'WPA',
            'hidden' => false,
        ]);

        $this->assertSame('WIFI:T:WPA;S:Ru\;mah;P:rahasia;H:false;;', $built->text);
    }

    #[Test]
    public function it_normalizes_a_url_without_a_scheme(): void
    {
        $built = app(PayloadBuilder::class)->build('url', [
            'url' => 'contoh.com/harga',
            'label' => '',
        ]);

        $this->assertSame('https://contoh.com/harga', $built->text);
        $this->assertSame('redirect', $built->kind);
    }

    #[Test]
    public function it_builds_a_vcard_and_a_whatsapp_link(): void
    {
        $card = app(PayloadBuilder::class)->build('vcard', [
            'first_name' => 'Ayu',
            'last_name' => 'Putri',
            'organization' => 'Studio',
            'job' => '',
            'phone' => '+628123',
            'email' => 'ayu@contoh.com',
            'website' => '',
            'address' => '',
            'note' => '',
        ]);

        $this->assertStringContainsString('BEGIN:VCARD', $card->text);
        $this->assertStringContainsString('FN:Ayu Putri', $card->text);

        $chat = app(PayloadBuilder::class)->build('whatsapp', [
            'phone' => '+62 812-3456-789',
            'message' => 'Halo',
        ]);

        $this->assertSame('https://wa.me/628123456789?text=Halo', $chat->text);
    }

    #[Test]
    public function it_builds_a_pix_payload_with_a_valid_crc(): void
    {
        $this->assertSame('29B1', PixPayload::crc16('123456789'));

        $payload = PixPayload::make('toko@email.com', 'Toko Maju', 'BRASILIA', '10.50', '');

        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('br.gov.bcb.pix', $payload);
        $this->assertSame(substr($payload, -4), PixPayload::crc16(substr($payload, 0, -4)));
    }
}
