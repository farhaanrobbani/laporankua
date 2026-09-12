<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DatabaseSeeder dilewati di environment production.');

            return;
        }

        $email = env('SEED_ADMIN_EMAIL');

        if ($email) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => env('SEED_ADMIN_NAME', 'Admin'),
                    'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                    'role' => 'admin',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $this->command?->info("Admin {$email} seeded.");

            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
