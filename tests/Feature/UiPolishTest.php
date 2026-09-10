<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_status_terlokalisasi_di_dashboard(): void
    {
        $user = User::factory()->create();
        Import::factory()->for($user)->create(['status' => 'success']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Berhasil')
            ->assertDontSee('>Success<');
    }

    public function test_toast_menampilkan_session_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'Data import berhasil dihapus.'])
            ->get('/imports')
            ->assertOk()
            ->assertSee('id="app-toast"', false)
            ->assertSee('Data import berhasil dihapus.');
    }

    public function test_modal_hapus_ada_di_halaman_detail(): void
    {
        $user = User::factory()->create();
        $import = Import::factory()->for($user)->create();

        $this->actingAs($user)->get(route('imports.show', $import))
            ->assertOk()
            ->assertSee('confirm-delete-import', false)
            ->assertSee('Hapus data import?');
    }

    public function test_empty_state_komponen_dirender(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/templates')
            ->assertOk()
            ->assertSee('Belum ada template')
            ->assertSee('Buat Template');
    }

    public function test_badge_format_laporan_dirender(): void
    {
        $user = User::factory()->create();
        $import = Import::factory()->for($user)->create(['status' => 'success']);
        ImportData::factory()->for($import)->create();

        $this->actingAs($user)->get('/data?import_id='.$import->id)->assertOk();
    }
}
