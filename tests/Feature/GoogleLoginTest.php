<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function google_sign_in_starts_at_the_provider(): void
    {
        Socialite::fake('google');

        $this->get('/login/google')->assertRedirect('https://socialite.fake/google/authorize');
    }

    #[Test]
    public function google_sign_in_asks_which_account_to_use(): void
    {
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost:8000/login/google/callback',
        ]);

        $response = $this->get('/login/google');
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertSame('select_account', $query['prompt'] ?? null);
    }

    #[Test]
    public function a_registered_email_can_sign_in_with_google(): void
    {
        $user = User::factory()->create([
            'name' => 'Member',
            'email' => 'Member@Example.com',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'email' => 'member@example.com',
            'name' => 'Google Name',
            'email_verified' => true,
        ]));

        $this->get('/login/google/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Member', $user->fresh()->name);
        $this->assertSame(1, User::query()->count());
    }

    #[Test]
    public function an_unregistered_google_account_cannot_sign_in(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'email' => 'stranger@example.com',
            'email_verified' => true,
        ]));

        $this->get('/login/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'This Google account cannot sign in.');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function an_unverified_google_email_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'member@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'email' => 'member@example.com',
            'email_verified' => false,
        ]));

        $this->get('/login/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'This Google account cannot sign in.');

        $this->assertGuest();
    }
}
