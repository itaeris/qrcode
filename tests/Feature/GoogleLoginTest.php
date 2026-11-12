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

        $this->get('/masuk/google')->assertRedirect('https://socialite.fake/google/authorize');
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

        $this->get('/masuk/google/callback')->assertRedirect('/');

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

        $this->get('/masuk/google/callback')
            ->assertRedirect('/masuk')
            ->assertSessionHas('error', 'This Google account is not registered.');

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

        $this->get('/masuk/google/callback')
            ->assertRedirect('/masuk')
            ->assertSessionHas('error', 'Google did not confirm this email address.');

        $this->assertGuest();
    }
}
