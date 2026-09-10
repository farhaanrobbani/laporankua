<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class ReportTest extends TestCase
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

    public function test_guest_tidak_bisa_akses_laporan(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/reports/create')->assertRedirect('/login');
    }

    public function test_halaman_builder_menampilkan_import(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $this->actingAs($user)->get('/reports/create')
            ->assertOk()
            ->assertSee($import->file_name);
    }

    public function test_generate_pdf_menyimpan_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Laporan Uji PDF')
            ->set('format', 'pdf')
            ->call('generate')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.index'));

        $report = Report::latest()->first();

        $this->assertSame('Laporan Uji PDF', $report->title);
        $this->assertSame('generated', $this->reportStatus($report));
        $this->assertNotNull($report->fresh()->file_path);
        Storage::disk('local')->assertExists($report->fresh()->file_path);
        $this->assertStringEndsWith('.pdf', $report->fresh()->file_path);
    }

    public function test_generate_word_menghasilkan_docx_valid(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Laporan Uji Word')
            ->set('format', 'word')
            ->call('generate')
            ->assertHasNoErrors();

        $report = Report::latest()->first()->fresh();
        $this->assertStringEndsWith('.docx', $report->file_path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($report->file_path)));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('Budi', $xml);
        $this->assertStringContainsString('Laporan Uji Word', $xml);
    }

    public function test_generate_excel_dapat_dibaca_kembali(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Laporan Uji Excel')
            ->set('format', 'excel')
            ->call('generate')
            ->assertHasNoErrors();

        $report = Report::latest()->first()->fresh();
        $this->assertStringEndsWith('.xlsx', $report->file_path);

        $sheet = IOFactory::load(Storage::disk('local')->path($report->file_path))->getActiveSheet();
        $this->assertSame('Nama', $sheet->getCell('A1')->getValue());
        $this->assertSame('Budi', $sheet->getCell('A2')->getValue());
    }

    public function test_format_print_menuju_halaman_cetak(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $component = Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Laporan Cetak')
            ->set('format', 'print')
            ->call('generate')
            ->assertHasNoErrors();

        $report = Report::latest()->first();
        $component->assertRedirect(route('reports.print', $report));

        $this->actingAs($user)->get(route('reports.print', $report))
            ->assertOk()
            ->assertSee('Laporan Cetak')
            ->assertSee('Budi');
    }

    public function test_generate_tanpa_kolom_ditolak(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Tanpa Kolom')
            ->set('format', 'pdf')
            ->set('fields', [])
            ->call('generate')
            ->assertHasErrors(['fields']);

        $this->assertSame(0, Report::count());
    }

    public function test_download_hanya_pemilik(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Untuk Download')
            ->set('format', 'pdf')
            ->call('generate');

        $report = Report::latest()->first();

        $this->actingAs($user)->get(route('reports.download', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($other)->get(route('reports.download', $report))->assertForbidden();
        $this->actingAs($other)->get(route('reports.show', $report))->assertForbidden();
    }

    public function test_hapus_laporan_menghapus_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('report-builder')
            ->set('importId', $import->id)
            ->set('title', 'Hapus Saya')
            ->set('format', 'pdf')
            ->call('generate');

        $report = Report::latest()->first()->fresh();
        Storage::disk('local')->assertExists($report->file_path);

        $this->actingAs($other)->delete(route('reports.destroy', $report))->assertForbidden();

        $this->actingAs($user)->delete(route('reports.destroy', $report))->assertRedirect('/reports');
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        Storage::disk('local')->assertMissing($report->file_path);
    }

    private function reportStatus(Report $report): string
    {
        return $report->fresh()->status;
    }
}
