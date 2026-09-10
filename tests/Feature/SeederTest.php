<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_user_uji_di_non_produksi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_seeder_membuat_admin_dari_env(): void
    {
        putenv('SEED_ADMIN_EMAIL=admin@contoh.test');
        putenv('SEED_ADMIN_NAME=Admin Uji');
        putenv('SEED_ADMIN_PASSWORD=rahasia123');

        try {
            $this->seed(DatabaseSeeder::class);
        } finally {
            putenv('SEED_ADMIN_EMAIL');
            putenv('SEED_ADMIN_NAME');
            putenv('SEED_ADMIN_PASSWORD');
        }

        $admin = User::where('email', 'admin@contoh.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Admin Uji', $admin->name);
        $this->assertNotNull($admin->email_verified_at);
    }
}
