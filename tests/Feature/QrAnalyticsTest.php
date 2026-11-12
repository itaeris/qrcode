<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Models\QrScan;
use App\Models\User;
use App\Qr\QrInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QrAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function kelola_shows_scans_for_the_selected_dates(): void
    {
        $response = $this->postJson('/qr', [
            'type' => 'url',
            'title' => 'Situs',
            'fields' => ['url' => 'https://example.com/promo'],
            'style' => QrInput::defaultStyle(),
            'smart' => true,
        ])->assertOk();

        $code = $response->json('code');
        $link = QrLink::query()->where('code', $code)->firstOrFail();

        QrScan::query()->create([
            'qr_link_id' => $link->id,
            'device' => 'Chrome · Windows',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
        ]);

        $older = QrScan::query()->create([
            'qr_link_id' => $link->id,
            'device' => 'Safari · iPhone',
            'city' => 'Bandung',
            'country' => 'Indonesia',
        ]);
        $older->created_at = now()->subDays(10);
        $older->save();

        $today = now()->toDateString();

        $this->get('/kelola/'.$code.'?dari='.$today.'&sampai='.$today)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Manage')
                ->where('qr.title', 'Situs')
                ->where('total', 1)
                ->where('scans.0.device', 'Chrome · Windows')
                ->where('scans.0.location', 'Jakarta, Indonesia')
                ->where('places.0.name', 'Jakarta, Indonesia')
                ->where('places.0.count', 1));

        $this->get('/kelola/'.$code.'/ubah')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Generate')
                ->where('editingCode', $code));
    }

    #[Test]
    public function guests_cannot_open_the_dashboard(): void
    {
        auth()->logout();

        $this->get('/kelola/abcdefgh')->assertRedirect('/masuk');
    }
}
