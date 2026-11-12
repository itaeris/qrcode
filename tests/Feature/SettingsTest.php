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
        $this->get('/pengaturan')->assertRedirect('/masuk');
    }

    #[Test]
    public function a_user_can_open_settings_and_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/pengaturan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->where('users.0.email', $user->email));

        $this->actingAs($user)
            ->from('/pengaturan')
            ->put('/pengaturan/sandi', [
                'current_password' => 'salah',
                'password' => 'rahasia-baru',
                'password_confirmation' => 'rahasia-baru',
            ])
            ->assertRedirect('/pengaturan')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->actingAs($user)
            ->from('/pengaturan')
            ->put('/pengaturan/sandi', [
                'current_password' => 'password',
                'password' => 'rahasia-baru',
                'password_confirmation' => 'rahasia-baru',
            ])
            ->assertRedirect('/pengaturan')
            ->assertSessionHas('success', 'Password updated.');

        $this->assertTrue(Hash::check('rahasia-baru', $user->fresh()->password));

        $this->post('/keluar');

        $this->post('/masuk', [
            'email' => $user->email,
            'password' => 'rahasia-baru',
        ])->assertRedirect('/');
    }

    #[Test]
    public function a_user_can_add_another_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/pengaturan')
            ->post('/pengaturan/pengguna', [
                'name' => 'Sari',
                'email' => $user->email,
                'password' => 'sandi-sari',
                'password_confirmation' => 'sandi-sari',
            ])
            ->assertRedirect('/pengaturan')
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->from('/pengaturan')
            ->post('/pengaturan/pengguna', [
                'name' => 'Sari',
                'email' => 'sari@aerisbeaute.com',
                'password' => 'sandi-sari',
                'password_confirmation' => 'beda',
            ])
            ->assertRedirect('/pengaturan')
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->from('/pengaturan')
            ->post('/pengaturan/pengguna', [
                'name' => 'Sari',
                'email' => 'sari@aerisbeaute.com',
                'password' => 'sandi-sari',
                'password_confirmation' => 'sandi-sari',
            ])
            ->assertRedirect('/pengaturan')
            ->assertSessionHas('success', 'User added.');

        $this->assertDatabaseHas('users', [
            'name' => 'Sari',
            'email' => 'sari@aerisbeaute.com',
        ]);

        $this->post('/keluar');

        $this->post('/masuk', [
            'email' => 'sari@aerisbeaute.com',
            'password' => 'sandi-sari',
        ])->assertRedirect('/');
    }
}
