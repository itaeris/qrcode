<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function a_short_link_redirects_and_can_change_destination(): void
    {
        $this->get('/links')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('ShortLinks/Index')->has('links', 0));

        $this->post('/links', [
            'url' => 'example.com/promo',
            'title' => 'Promo',
            'code' => 'promo1',
        ])->assertRedirect('/links');

        $this->get('/s/promo1')->assertRedirect('https://example.com/promo');
        $this->assertSame(1, ShortLink::query()->first()->clicks);

        $this->put('/links/promo1', [
            'url' => 'https://example.com/baru',
            'title' => 'Promo baru',
        ])->assertRedirect();

        $this->get('/s/promo1')->assertRedirect('https://example.com/baru');

        $this->post('/links', [
            'url' => 'https://example.com/lain',
            'code' => 'promo1',
        ])->assertSessionHasErrors('code');

        $this->post('/links', [
            'url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('url');

        $this->post('/links/promo1/delete')->assertRedirect('/links');
        $this->get('/s/promo1')->assertNotFound();
    }

    #[Test]
    public function guests_cannot_manage_short_links(): void
    {
        auth()->logout();

        $this->get('/links')->assertRedirect('/login');
    }
}
