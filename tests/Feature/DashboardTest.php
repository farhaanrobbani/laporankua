<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_dialihkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_melihat_statistik_dashboard(): void
    {
        $user = User::factory()->create();

        $import = Import::factory()->for($user)->create(['file_name' => 'data_karyawan.xlsx']);
        ImportData::factory()->for($import)->count(3)->create();
        Report::factory()->for($user)->for($import)->create(['title' => 'Laporan Karyawan']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data_karyawan.xlsx');
        $response->assertSee('Laporan Karyawan');
        $response->assertSee('Total File Import');
        $response->assertSee('Total Data Record');
        $response->assertSee('Laporan Dibuat');
    }

    public function test_data_user_lain_tidak_terlihat(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $importB = Import::factory()->for($userB)->create(['file_name' => 'rahasia_b.xlsx']);
        Report::factory()->for($userB)->for($importB)->create(['title' => 'Laporan Rahasia B']);

        $response = $this->actingAs($userA)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('rahasia_b.xlsx');
        $response->assertDontSee('Laporan Rahasia B');
    }

    public function test_empty_state_saat_belum_ada_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Belum ada data import');
        $response->assertSee('Belum ada laporan');
    }
}
