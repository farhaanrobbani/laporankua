<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

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
        $this->get('/templates')->assertRedirect('/login');
        $this->get('/templates/create')->assertRedirect('/login');
    }

    public function test_index_hanya_menampilkan_template_miliknya(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        ReportTemplate::factory()->for($user)->create(['name' => 'Miliku']);
        ReportTemplate::factory()->for($other)->create(['name' => 'Milik Orang']);

        $this->actingAs($user)->get('/templates')
            ->assertOk()
            ->assertSee('Miliku')
            ->assertDontSee('Milik Orang');
    }

    public function test_buat_template_baru(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/templates', [
            'name' => 'Laporan Bulanan',
            'description' => 'Template rutin',
            'output_format' => 'pdf',
            'fields' => ['Nama', 'Nilai'],
            'search' => '2026',
            'sort_column' => 'Nama',
            'sort_direction' => 'asc',
            'orientation' => 'landscape',
        ])->assertRedirect('/templates');

        $template = ReportTemplate::latest()->first();

        $this->assertSame('Laporan Bulanan', $template->name);
        $this->assertSame(['Nama', 'Nilai'], $template->fields_json);
        $this->assertSame('2026', $template->filters_json['search']);
        $this->assertSame('landscape', $template->layout_json['orientation']);
    }

    public function test_set_default_menonaktifkan_lainnya(): void
    {
        $user = User::factory()->create();

        $first = ReportTemplate::factory()->for($user)->create(['is_default' => true]);
        $second = ReportTemplate::factory()->for($user)->create(['is_default' => false]);

        $this->actingAs($user)->post(route('templates.default', $second))->assertRedirect('/templates');

        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_ubah_dan_hapus_template(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $template = ReportTemplate::factory()->for($user)->create(['name' => 'Lama']);

        $this->actingAs($other)->get(route('templates.edit', $template))->assertForbidden();
        $this->actingAs($other)->delete(route('templates.destroy', $template))->assertForbidden();

        $this->actingAs($user)->put(route('templates.update', $template), [
            'name' => 'Baru',
            'output_format' => 'excel',
        ])->assertRedirect('/templates');
        $this->assertSame('Baru', $template->fresh()->name);

        $this->actingAs($user)->delete(route('templates.destroy', $template))->assertRedirect('/templates');
        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }

    public function test_pakai_template_membuat_laporan(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $template = ReportTemplate::factory()->for($user)->create([
            'name' => 'T Bulanan',
            'output_format' => 'pdf',
            'fields_json' => ['Nama', 'Nilai', 'KolomHantu'],
        ]);

        $this->actingAs($user)->get(route('templates.use', $template))->assertOk()->assertSee('T Bulanan');

        $this->actingAs($user)->post(route('templates.apply', $template), [
            'import_id' => $import->id,
            'title' => 'Laporan dari Template',
        ])->assertRedirect('/reports');

        $report = Report::latest()->first();

        $this->assertSame('Laporan dari Template', $report->title);
        $this->assertSame($template->id, $report->report_template_id);
        $this->assertSame(['Nama', 'Nilai'], $report->config_json['fields']);
        $this->assertSame('generated', $report->fresh()->status);
        Storage::disk('local')->assertExists($report->fresh()->file_path);
    }

    public function test_pakai_template_ditolak_untuk_data_orang_lain(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $importB = $this->importWithRows($userB);
        $templateA = ReportTemplate::factory()->for($userA)->create();

        // Template orang lain tidak bisa dipakai
        $templateB = ReportTemplate::factory()->for($userB)->create();
        $this->actingAs($userA)->get(route('templates.use', $templateB))->assertForbidden();

        // Import orang lain tidak bisa dipilih
        $this->actingAs($userA)->post(route('templates.apply', $templateA), [
            'import_id' => $importB->id,
            'title' => 'Nekat',
        ])->assertNotFound();
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
        ]);
    }

    public function test_builder_bisa_muat_template_default(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        ReportTemplate::factory()->for($user)->create([
            'output_format' => 'excel',
            'filters_json' => ['search' => 'Budi', 'filter_column' => null, 'filter_value' => null],
            'is_default' => true,
        ]);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->call('loadDefaultTemplate')
            ->assertSet('format', 'excel')
            ->assertSet('search', 'Budi');
    }
}
