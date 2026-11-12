<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the application admin.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'dwiki@aerisbeaute.com'],
            [
                'name' => 'Dwiki',
                'password' => 'aerisbeaute',
                'email_verified_at' => now(),
            ],
        );
    }
}
