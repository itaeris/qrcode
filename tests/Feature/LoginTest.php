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
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    #[Test]
    public function the_admin_can_sign_in_and_sign_out(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/login', [
            'email' => 'dwiki@aerisbeaute.com',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => 'dwiki@aerisbeaute.com',
            'password' => 'aerisbeaute',
        ])->assertRedirect('/');

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Projects')
            ->where('auth.user.email', 'dwiki@aerisbeaute.com'));

        $this->post('/logout')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    #[Test]
    public function repeated_wrong_passwords_lock_that_email(): void
    {
        $this->seed(AdminUserSeeder::class);
        $other = User::factory()->create(['email' => 'other@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withHeader('X-Forwarded-For', '203.0.113.'.$attempt)
                ->post('/login', [
                    'email' => 'dwiki@aerisbeaute.com',
                    'password' => 'wrong-password',
                ])->assertSessionHasErrors('email');
        }

        $this->withHeader('X-Forwarded-For', '203.0.113.20')
            ->post('/login', [
                'email' => 'dwiki@aerisbeaute.com',
                'password' => 'aerisbeaute',
            ])->assertStatus(429);

        $this->assertGuest();

        $this->withHeader('X-Forwarded-For', '203.0.113.30')
            ->post('/login', [
                'email' => $other->email,
                'password' => 'password',
            ])->assertRedirect('/');
    }

    #[Test]
    public function a_signed_in_user_skips_the_login_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/login')->assertRedirect('/');
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
