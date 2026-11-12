<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_cannot_open_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    #[Test]
    public function a_user_can_open_settings_and_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->where('users.0.email', $user->email));

        $this->actingAs($user)
            ->from('/settings')
            ->put('/settings/password', [
                'current_password' => 'salah',
                'password' => 'rahasia-baru',
                'password_confirmation' => 'rahasia-baru',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->actingAs($user)
            ->from('/settings')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'rahasia-baru',
                'password_confirmation' => 'rahasia-baru',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHas('success', 'Password updated.');

        $this->assertTrue(Hash::check('rahasia-baru', $user->fresh()->password));

        $this->post('/logout');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia-baru',
        ])->assertRedirect('/');
    }

    #[Test]
    public function a_user_can_add_another_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings')
            ->post('/settings/users', [
                'name' => 'Sari',
                'email' => $user->email,
                'password' => 'sandi-sari',
                'password_confirmation' => 'sandi-sari',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->from('/settings')
            ->post('/settings/users', [
                'name' => 'Sari',
                'email' => 'sari@aerisbeaute.com',
                'password' => 'sandi-sari',
                'password_confirmation' => 'beda',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->from('/settings')
            ->post('/settings/users', [
                'name' => 'Sari',
                'email' => 'sari@aerisbeaute.com',
                'password' => 'sandi-sari',
                'password_confirmation' => 'sandi-sari',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHas('success', 'User added.');

        $this->assertDatabaseHas('users', [
            'name' => 'Sari',
            'email' => 'sari@aerisbeaute.com',
        ]);

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'sari@aerisbeaute.com',
            'password' => 'sandi-sari',
        ])->assertRedirect('/');
    }
}
