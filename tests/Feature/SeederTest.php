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

    public function test_seeder_membuat_system_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $system = User::where('email', 'system@laporanku.id')->first();

        $this->assertNotNull($system);
        $this->assertSame('admin', $system->role);
        $this->assertNotNull($system->email_verified_at);
    }
}
