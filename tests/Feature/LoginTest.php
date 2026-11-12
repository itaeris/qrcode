<?php

namespace Tests\Feature;

use App\Models\User;
use App\Qr\QrInput;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/masuk');
        $this->get('/masuk')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    #[Test]
    public function the_admin_can_sign_in_and_sign_out(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/masuk', [
            'email' => 'dwiki@aerisbeaute.com',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->post('/masuk', [
            'email' => 'dwiki@aerisbeaute.com',
            'password' => 'aerisbeaute',
        ])->assertRedirect('/');

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Projects')
            ->where('auth.user.email', 'dwiki@aerisbeaute.com'));

        $this->post('/keluar')->assertRedirect('/masuk');
        $this->get('/')->assertRedirect('/masuk');
    }

    #[Test]
    public function a_signed_in_user_skips_the_login_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/masuk')->assertRedirect('/');
    }

    #[Test]
    public function a_public_qr_stays_open_without_signing_in(): void
    {
        $code = $this->actingAs(User::factory()->create())->postJson('/qr', [
            'type' => 'url',
            'fields' => ['url' => 'https://example.com/promo'],
            'style' => QrInput::defaultStyle(),
            'smart' => true,
        ])->assertOk()->json('code');

        auth()->logout();

        $this->get('/q/'.$code)->assertRedirect('https://example.com/promo');
    }
}
