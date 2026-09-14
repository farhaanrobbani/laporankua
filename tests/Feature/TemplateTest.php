<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function importWithRows(User $user): Import
    {
        $import = Import::factory()->for($user)->create(['status' => 'success']);

        foreach ([['Budi', 100], ['Siti', 200]] as $i => $row) {
            ImportData::factory()->for($import)->create([
                'row_data' => ['Nama' => $row[0], 'Nilai' => $row[1]],
                'row_number' => $i + 2,
            ]);
        }

        return $import;
    }

    public function test_guest_tidak_bisa_akses_template(): void
    {
        $this->get('/admin/templates')->assertRedirect('/login');
        $this->get('/admin/templates/create')->assertRedirect('/login');
    }

    public function test_non_admin_tidak_bisa_akses_template(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/templates')->assertForbidden();
        $this->actingAs($user)->get('/admin/templates/create')->assertForbidden();
    }

    public function test_index_hanya_menampilkan_template_miliknya(): void
    {
        ReportTemplate::factory()->for($this->admin)->create(['name' => 'Miliku', 'is_global' => true]);
        ReportTemplate::factory()->for($this->admin)->create(['name' => 'Milik Orang', 'is_global' => false]);

        $this->actingAs($this->admin)->get(route('admin.templates.index'))
            ->assertOk()
            ->assertSee('Miliku');
    }

    public function test_buat_template_baru(): void
    {
        $this->actingAs($this->admin)->post(route('admin.templates.store'), [
            'name' => 'Laporan Bulanan',
            'description' => 'Template rutin',
            'output_format' => 'pdf',
            'fields' => ['Nama', 'Nilai'],
            'search' => '2026',
            'sort_column' => 'Nama',
            'sort_direction' => 'asc',
            'orientation' => 'landscape',
        ])->assertRedirect(route('admin.templates.index'));

        $template = ReportTemplate::latest()->first();

        $this->assertSame('Laporan Bulanan', $template->name);
        $this->assertSame(['Nama', 'Nilai'], $template->fields_json);
        $this->assertSame('2026', $template->filters_json['search']);
        $this->assertSame('landscape', $template->layout_json['orientation']);
        $this->assertTrue($template->is_global);
    }

    public function test_set_default_menonaktifkan_lainnya(): void
    {
        $first = ReportTemplate::factory()->for($this->admin)->create(['is_default' => true, 'is_global' => true]);
        $second = ReportTemplate::factory()->for($this->admin)->create(['is_default' => false, 'is_global' => true]);

        $this->actingAs($this->admin)->post(route('admin.templates.default', $second))->assertRedirect(route('admin.templates.index'));

        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_ubah_dan_hapus_template(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $template = ReportTemplate::factory()->for($this->admin)->create(['name' => 'Lama']);

        $this->actingAs($otherAdmin)->get(route('admin.templates.edit', $template))->assertOk();
        $this->actingAs($otherAdmin)->delete(route('admin.templates.destroy', $template))->assertRedirect(route('admin.templates.index'));

        $template = ReportTemplate::factory()->for($this->admin)->create(['name' => 'Lama']);

        $this->actingAs($this->admin)->put(route('admin.templates.update', $template), [
            'name' => 'Baru',
            'output_format' => 'excel',
        ])->assertRedirect(route('admin.templates.index'));
        $this->assertSame('Baru', $template->fresh()->name);

        $this->actingAs($this->admin)->delete(route('admin.templates.destroy', $template))->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }

    public function test_global_template_bisa_dipakai_user_biasa(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user']);
        $import = $this->importWithRows($user);

        $template = ReportTemplate::factory()->for($this->admin)->create([
            'name' => 'T Bulanan',
            'output_format' => 'print',
            'fields_json' => ['Nama', 'Nilai', 'KolomHantu'],
            'is_global' => true,
        ]);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('selectedTemplateId', $template->id)
            ->call('applySelectedTemplate')
            ->assertSet('format', 'print')
            ->assertSet('search', $template->filters_json['search'] ?? '');
    }

    public function test_builder_bisa_simpan_sebagai_template(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('templateName', 'Dari Builder')
            ->call('saveAsTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('report_templates', [
            'user_id' => $user->id,
            'name' => 'Dari Builder',
            'is_global' => true,
        ]);
    }

    public function test_builder_bisa_muat_template_default(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        ReportTemplate::factory()->for($this->admin)->create([
            'output_format' => 'excel',
            'filters_json' => ['search' => 'Budi', 'filter_column' => null, 'filter_value' => null],
            'is_default' => true,
            'is_global' => true,
        ]);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->call('loadDefaultTemplate')
            ->assertSet('format', 'excel')
            ->assertSet('search', 'Budi');
    }
}
