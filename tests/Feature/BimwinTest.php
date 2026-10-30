<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BimwinTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_bimwin_page(): void
    {
        $this->get(route('bimwin.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_see_bimwin_page_with_profile_config(): void
    {
        $user = User::factory()->create([
            'provinsi' => 'Jawa Timur',
            'kota_kabupaten' => 'Kab. Pasuruan',
            'kecamatan' => 'Pasrepan',
        ]);

        $this->actingAs($user)
            ->get(route('bimwin.index'))
            ->assertOk()
            ->assertSee('Otomasi Pengisian BIMWIN')
            ->assertSee('Jawa Timur')
            ->assertSee('Kab. Pasuruan')
            ->assertSee('Pasrepan')
            ->assertSee('simkah4-bimwin.user.js')
            ->assertSee('Cek saja');
    }

    public function test_bimwin_page_warns_when_profile_region_is_incomplete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('bimwin.index'))
            ->assertOk()
            ->assertSee('Lengkapi provinsi, kabupaten/kota, dan kecamatan');
    }

    public function test_bimwin_navigation_link_is_visible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('bimwin.index'), false)
            ->assertSee('BIMWIN');
    }

    public function test_bimwin_userscript_is_available(): void
    {
        $path = public_path('userscripts/simkah4-bimwin.user.js');

        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertStringContainsString('SIMKAH4 — Isi BIMWIN Otomatis', $content);
        $this->assertStringContainsString('https://simkah4.kemenag.go.id/*', $content);
        $this->assertStringContainsString('ENDPOINTS', $content);
        $this->assertStringContainsString('SS_ALL', $content);
    }
}
