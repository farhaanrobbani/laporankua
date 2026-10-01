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
            ->assertSee($import->table_name);
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

    public function test_nb_menyertakan_baris_appended_dan_dibatasi_per_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $pdk = fn (User $owner, string $status, array $rows) => tap(
            Import::factory()->for($owner)->create([
                'status' => $status,
                'table_name' => 'laporan pendaftaran nikah',
            ]),
            function (Import $import) use ($rows) {
                foreach ($rows as $i => $row) {
                    ImportData::factory()->for($import)->create([
                        'row_data' => $row,
                        'row_number' => $i + 2,
                    ]);
                }
            }
        );

        $firstImport = $pdk($user, 'success', [
            ['Nomor Daftar' => 'NB-SEP-001', 'Tanggal Daftar' => '2026-09-05', 'Nama Suami' => 'Suami Nol Satu', 'Nama Istri' => 'Istri Nol Satu'],
            ['Nomor Daftar' => 'NB-SEP-002', 'Tanggal Daftar' => '2026-09-08', 'Nama Suami' => 'Suami Nol Dua', 'Nama Istri' => 'Istri Nol Dua'],
        ]);
        $pdk($user, 'appended', [
            ['Nomor Daftar' => 'NB-SEP-003', 'Tanggal Daftar' => '2026-09-12', 'Nama Suami' => 'Suami Nol Tiga', 'Nama Istri' => 'Istri Nol Tiga'],
        ]);
        $pdk($other, 'success', [
            ['Nomor Daftar' => 'NB-LAIN-001', 'Tanggal Daftar' => '2026-09-06', 'Nama Suami' => 'Suami Lain', 'Nama Istri' => 'Istri Lain'],
        ]);

        $report = Report::factory()->for($user)->create([
            'import_id' => $firstImport->id,
            'config_json' => [
                'fields' => ['Nomor Daftar', 'Tanggal Daftar', 'Nama Suami', 'Nama Istri'],
                'filter_month' => '9',
                'filter_year' => '2026',
                'table_layout' => ['type' => 'laporan_nb'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.print', $report));
        $response->assertOk()
            ->assertSee('Suami Nol Tiga')
            ->assertDontSee('Suami Lain');

        $entries = [];
        foreach ($response->viewData('dataset')['rows'] as $row) {
            foreach ($row['entries'] ?? [] as $entry) {
                $entries[] = $entry['Nomor Daftar'] ?? '';
            }
        }

        $this->assertSame(['NB-SEP-001', 'NB-SEP-002', 'NB-SEP-003'], $entries);
    }

    public function test_laporan_n_menyertakan_baris_appended(): void
    {
        $user = User::factory()->create();

        $pn = fn (string $status, array $rows) => tap(
            Import::factory()->for($user)->create([
                'status' => $status,
                'table_name' => 'laporan peristiwa nikah',
            ]),
            function (Import $import) use ($rows) {
                foreach ($rows as $i => $row) {
                    ImportData::factory()->for($import)->create([
                        'row_data' => $row,
                        'row_number' => $i + 2,
                    ]);
                }
            }
        );

        $firstImport = $pn('success', [
            ['Nomor Daftar' => 'PN-SEP-001', 'Nomor Akta Nikah' => 'AKTA-SEP-001', 'Tanggal Nikah' => '2026-09-05', 'Nama Suami' => 'Pengantin Satu', 'Nama Istri' => 'Pasangan Satu'],
        ]);
        $pn('appended', [
            ['Nomor Daftar' => 'PN-SEP-002', 'Nomor Akta Nikah' => 'AKTA-SEP-002', 'Tanggal Nikah' => '2026-09-11', 'Nama Suami' => 'Pengantin Dua', 'Nama Istri' => 'Pasangan Dua'],
        ]);

        $report = Report::factory()->for($user)->create([
            'import_id' => $firstImport->id,
            'config_json' => [
                'fields' => ['Nomor Daftar', 'Nomor Akta Nikah', 'Tanggal Nikah', 'Nama Suami', 'Nama Istri'],
                'filter_month' => '9',
                'filter_year' => '2026',
                'table_layout' => ['type' => 'laporan_n'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.print', $report));
        $response->assertOk()
            ->assertSee('Pengantin Satu')
            ->assertSee('Pengantin Dua')
            ->assertSee('AKTA-SEP-002');

        $entries = [];
        foreach ($response->viewData('dataset')['rows'] as $row) {
            foreach ($row['entries'] ?? [] as $entry) {
                $entries[] = $entry['Nomor Akta Nikah'] ?? '';
            }
        }

        $this->assertSame(['AKTA-SEP-001', 'AKTA-SEP-002'], $entries);
    }

    public function test_laporan_l1_duplikat_l3_tidak_double_count(): void
    {
        $user = User::factory()->create(['daftar_desa' => ['DESA A']]);

        $mk = fn (string $table, string $status, array $rows) => tap(
            Import::factory()->for($user)->create([
                'status' => $status,
                'table_name' => $table,
            ]),
            function (Import $import) use ($rows) {
                foreach ($rows as $i => $row) {
                    ImportData::factory()->for($import)->create([
                        'row_data' => $row,
                        'row_number' => $i + 2,
                    ]);
                }
            }
        );

        $pn = $mk('laporan peristiwa nikah', 'success', [
            ['Nomor Daftar' => 'ND-L1-001', 'Nomor Akta Nikah' => 'AK-L1-001', 'Tanggal Nikah' => '2026-09-05', 'Kelurahan' => 'DESA A', 'Nama Suami' => 'Suami A', 'Nama Istri' => 'Istri A'],
        ]);
        $mk('laporan model l3', 'success', [
            ['Nomor Perforasi' => 'PF-L1-001', 'Keterangan' => 'Duplikat', 'Tanggal Cetak' => '2026-09-01', 'Desa' => 'DESA A'],
        ]);
        $mk('laporan model l3', 'appended', [
            ['Nomor Perforasi' => 'PF-L1-001', 'Keterangan' => 'Duplikat', 'Tanggal Cetak' => '2026-09-01', 'Desa' => 'DESA A'],
            ['Nomor Perforasi' => 'PF-L1-002', 'Keterangan' => 'Duplikat', 'Tanggal Cetak' => '2026-09-01', 'Desa' => 'DESA A'],
        ]);

        $report = Report::factory()->for($user)->create([
            'import_id' => $pn->id,
            'config_json' => [
                'fields' => ['Kelurahan', 'Jumlah Nikah', 'Duplikat'],
                'filter_month' => '9',
                'filter_year' => '2026',
                'merged_import_ids' => [$pn->id],
                'table_layout' => ['type' => 'laporan_l1'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.print', $report));
        $response->assertOk();

        $rows = collect($response->viewData('dataset')['rows']);
        $desaA = $rows->firstWhere('Kelurahan', 'DESA A');

        $this->assertNotNull($desaA);
        // PF-L1-001 ada di success DAN appended → dihitung sekali; PF-L1-002 baru → total 2, bukan 3.
        $this->assertSame(2, $desaA['Duplikat']);
    }

    public function test_l4_filter_menggunakan_tanggal_akad_bukan_tanggal_setor(): void
    {
        $user = User::factory()->create();

        $sim = tap(
            Import::factory()->for($user)->create([
                'status' => 'success',
                'table_name' => 'laporan simponi',
            ]),
            function (Import $import) {
                $rows = [
                    // Akad 5 Sep, setor 25 Sep → tampil.
                    ['No Pendaftaran' => 'SP-001', 'Nama Kelurahan' => 'DESA A', 'Tanggal dan Jam Setor' => '2026-09-25 10:00:00', 'Tanggal Akad' => '2026-09-05', 'Nama Suami' => 'Penyetor Alpha', 'Nominal Setor' => '600000'],
                    // Akad 20 Sep, setor 1 Sep → tampil, urut sebelum Alpha jika pakai tanggal setor.
                    ['No Pendaftaran' => 'SP-002', 'Nama Kelurahan' => 'DESA A', 'Tanggal dan Jam Setor' => '2026-09-01 09:00:00', 'Tanggal Akad' => '2026-09-20', 'Nama Suami' => 'Penyetor Beta', 'Nominal Setor' => '600000'],
                    // Akad 2 Okt, setor 10 Sep → gugur karena akad di luar bulan filter.
                    ['No Pendaftaran' => 'SP-003', 'Nama Kelurahan' => 'DESA A', 'Tanggal dan Jam Setor' => '2026-09-10 08:00:00', 'Tanggal Akad' => '2026-10-02', 'Nama Suami' => 'Penyetor Gamma', 'Nominal Setor' => '600000'],
                ];
                foreach ($rows as $i => $row) {
                    ImportData::factory()->for($import)->create([
                        'row_data' => $row,
                        'row_number' => $i + 2,
                    ]);
                }
            }
        );

        $report = Report::factory()->for($user)->create([
            'import_id' => $sim->id,
            'config_json' => [
                'fields' => ['Nama Kelurahan', 'Tanggal dan Jam Setor', 'Nominal Setor', 'Tanggal Akad', 'Nama Suami'],
                'filter_month' => '9',
                'filter_year' => '2026',
                'table_layout' => [
                    'type' => 'grouped_detail',
                    'aggregation' => [
                        'group_by' => 'Nama Kelurahan',
                        'date_filter_field' => 'Tanggal Akad',
                    ],
                    'columns' => [
                        ['type' => 'row_number', 'label' => 'No'],
                        ['type' => 'field', 'field' => 'Nama Kelurahan', 'label' => 'Desa'],
                        ['type' => 'aggregate_total', 'label' => 'Jumlah Perkawinan'],
                        ['type' => 'field', 'field' => 'Tanggal dan Jam Setor', 'label' => 'Tanggal Setor', 'format' => 'date_id'],
                        ['type' => 'static', 'value' => '600000', 'label' => 'Jumlah Setor'],
                        ['type' => 'field', 'field' => 'Tanggal Akad', 'label' => 'Tanggal Perkawinan', 'format' => 'date_id'],
                        ['type' => 'field', 'field' => 'Nama Suami', 'label' => 'Penyetor'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.print', $report));
        $response->assertOk()
            ->assertSee('Penyetor Alpha')
            ->assertSee('Penyetor Beta')
            ->assertDontSee('Penyetor Gamma');

        $rows = $response->viewData('dataset')['rows'];
        $this->assertCount(2, $rows);

        // Urutan dalam grup mengikuti Tanggal Akad (Alpha 5 Sep < Beta 20 Sep),
        // bukan tanggal setor (Beta 1 Sep < Alpha 25 Sep).
        $html = $response->getContent();
        $posAlpha = strpos($html, 'Penyetor Alpha');
        $posBeta = strpos($html, 'Penyetor Beta');
        $this->assertNotFalse($posAlpha);
        $this->assertNotFalse($posBeta);
        $this->assertLessThan($posBeta, $posAlpha);
    }

    private function reportStatus(Report $report): string
    {
        return $report->fresh()->status;
    }
}
