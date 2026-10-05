<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DataTest extends TestCase
{
    use RefreshDatabase;

    private function importWithRows(User $user, array $rows = [['Budi', 100], ['Siti', 200], ['Andi', 300]]): Import
    {
        $import = Import::factory()->for($user)->create(['status' => 'success']);

        foreach ($rows as $i => $row) {
            ImportData::factory()->for($import)->create([
                'row_data' => ['Nama' => $row[0], 'Nilai' => $row[1]],
                'row_number' => $i + 2,
            ]);
        }

        return $import;
    }

    private function l3Import(User $user, array $rows, string $status = 'success'): Import
    {
        $import = Import::factory()->for($user)->create([
            'status' => $status,
            'table_name' => 'laporan model l3',
        ]);

        foreach ($rows as $i => $row) {
            ImportData::factory()->for($import)->create([
                'row_data' => $row,
                'row_number' => $i + 2,
            ]);
        }

        return $import;
    }

    public function test_guest_tidak_bisa_akses_data(): void
    {
        $this->get('/data/data-import')->assertRedirect('/login');
        $this->get('/data/export')->assertRedirect('/login');
        $this->get('/data/rusak')->assertRedirect('/login');
    }

    public function test_index_menampilkan_picker_dan_tabel(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $response = $this->actingAs($user)->get('/data/data-import?import_id='.$import->id);

        $response->assertOk();
        $response->assertSee('Budi');
        $response->assertSee('Siti');
        $response->assertSee($import->table_name);
    }

    public function test_isolasi_data_antar_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $importB = $this->importWithRows($userB, [['Rahasia', 999]]);
        $importB->update(['table_name' => 'data_rahasia_user_b']);
        $recordB = $importB->importData()->first();

        // Picker tidak menampilkan import user lain
        $this->actingAs($userA)->get('/data/data-import')->assertOk()->assertDontSee('data_rahasia_user_b');

        // Akses langsung import user lain → 404
        $this->actingAs($userA)->get('/data/data-import?import_id='.$importB->id)->assertNotFound();

        // Detail record user lain → 403
        $this->actingAs($userA)->get('/data/'.$recordB->id)->assertForbidden();

        // Export data user lain → 404
        $this->actingAs($userA)->get('/data/export?import_id='.$importB->id)->assertNotFound();
    }

    public function test_detail_record_menampilkan_semua_kolom(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);
        $record = $import->importData()->first();

        $this->actingAs($user)->get('/data/'.$record->id)
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Nama')
            ->assertSee('Nilai');
    }

    public function test_pencarian_memfilter_baris(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->set('search', 'Siti')
            ->assertSee('Siti')
            ->assertDontSee('Budi')
            ->assertDontSee('Andi');
    }

    public function test_pencarian_tidak_case_sensitive(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->set('search', 'budi')
            ->assertSee('Budi')
            ->assertDontSee('Siti');
    }

    public function test_filter_kolom_bekerja(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->set('filterColumn', 'Nama')
            ->set('filterValue', 'Andi')
            ->assertSee('Andi')
            ->assertDontSee('Budi');
    }

    public function test_sorting_kolom_bekerja(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->call('sortBy', 'Nama')
            ->call('sortBy', 'Nama')
            ->assertSeeInOrder(['Siti', 'Budi', 'Andi']);
    }

    public function test_hapus_satu_record(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);
        $record = $import->importData()->first();

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->call('deleteRecord', $record->id);

        $this->assertDatabaseMissing('import_data', ['id' => $record->id]);
        $this->assertSame(2, $import->importData()->count());
    }

    public function test_hapus_banyak_record_sekaligus(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);
        $ids = $import->importData()->pluck('id')->map(fn ($id) => (int) $id)->all();

        Livewire::actingAs($user)
            ->test('data-table', ['importId' => $import->id])
            ->set('selected', [$ids[0], $ids[1]])
            ->call('deleteSelected');

        $this->assertSame(1, $import->importData()->count());
        $this->assertDatabaseMissing('import_data', ['id' => $ids[0]]);
    }

    public function test_mount_ditolak_untuk_import_milik_orang(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $importB = $this->importWithRows($userB);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($userA)->test('data-table', ['importId' => $importB->id]);
    }

    public function test_export_excel_berisi_data_benar(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $response = $this->actingAs($user)->get('/data/export?import_id='.$import->id);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition');

        $tmp = sys_get_temp_dir().'/export_test_'.uniqid().'.xlsx';
        file_put_contents($tmp, $response->streamedContent() ?? $response->getContent());

        $sheet = IOFactory::load($tmp)->getActiveSheet();
        $this->assertSame('Nama', $sheet->getCell('A1')->getValue());
        $this->assertSame('Nilai', $sheet->getCell('B1')->getValue());
        $this->assertSame('Budi', $sheet->getCell('A2')->getValue());

        unlink($tmp);
    }

    public function test_export_menghormati_pencarian(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $response = $this->actingAs($user)->get('/data/export?import_id='.$import->id.'&search=Siti');

        $response->assertOk();

        $tmp = sys_get_temp_dir().'/export_test_'.uniqid().'.xlsx';
        file_put_contents($tmp, $response->streamedContent() ?? $response->getContent());

        $rows = IOFactory::load($tmp)->getActiveSheet()->toArray();
        $this->assertCount(2, $rows); // header + 1 baris
        $this->assertSame('Siti', $rows[1][0]);

        unlink($tmp);
    }

    public function test_kolom_desa_muncul_setelah_keterangan(): void
    {
        $user = User::factory()->create(['daftar_desa' => ['SONOWANGI']]);
        $import = $this->l3Import($user, [
            ['Nomor Perforasi' => 'PF-DT-001', 'Keterangan' => 'Duplikat'],
        ]);

        Livewire::actingAs($user)
            ->test('data-table', [
                'importIds' => [$import->id],
                'filterColumn' => 'Keterangan',
                'filterValue' => 'Duplikat',
                'filterMode' => 'exact',
                'editableColumns' => ['Desa'],
            ])
            ->assertSeeInOrder(['Keterangan', 'Desa'])
            ->assertSee('list="options-desa"', false)
            ->assertSee('SONOWANGI');
    }

    public function test_update_cell_menyimpan_desa(): void
    {
        $user = User::factory()->create(['daftar_desa' => ['SONOWANGI']]);
        $import = $this->l3Import($user, [
            ['Nomor Perforasi' => 'PF-DT-002', 'Keterangan' => 'Duplikat'],
        ]);
        $record = $import->importData()->first();

        Livewire::actingAs($user)
            ->test('data-table', [
                'importIds' => [$import->id],
                'editableColumns' => ['Desa'],
            ])
            ->call('updateCell', $record->id, 'Desa', '  SONOWANGI  ');

        $this->assertSame('SONOWANGI', $record->fresh()->row_data['Desa']);

        // Kolom di luar editableColumns diabaikan.
        Livewire::actingAs($user)
            ->test('data-table', [
                'importIds' => [$import->id],
                'editableColumns' => ['Desa'],
            ])
            ->call('updateCell', $record->id, 'Keterangan', 'Bukan Duplikat');

        $this->assertSame('Duplikat', $record->fresh()->row_data['Keterangan']);
    }

    public function test_update_cell_sinkron_baris_duplikat_fisik(): void
    {
        $user = User::factory()->create(['daftar_desa' => ['TIRTOMARTO']]);
        $a = $this->l3Import($user, [['Nomor Perforasi' => 'PF-DT-003', 'Keterangan' => 'Duplikat']]);
        $b = $this->l3Import($user, [['Nomor Perforasi' => 'PF-DT-003', 'Keterangan' => 'Duplikat']], 'appended');
        $rowA = $a->importData()->first();
        $rowB = $b->importData()->first();

        Livewire::actingAs($user)
            ->test('data-table', [
                'importIds' => [$a->id, $b->id],
                'editableColumns' => ['Desa'],
            ])
            ->call('updateCell', $rowA->id, 'Desa', 'TIRTOMARTO');

        $this->assertSame('TIRTOMARTO', $rowA->fresh()->row_data['Desa']);
        $this->assertSame('TIRTOMARTO', $rowB->fresh()->row_data['Desa']);
    }

    public function test_update_cell_ditolak_bukan_pemilik(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $importA = $this->l3Import($userA, [['Nomor Perforasi' => 'PF-DT-A', 'Keterangan' => 'Duplikat']]);
        $importB = $this->l3Import($userB, [['Nomor Perforasi' => 'PF-DT-B', 'Keterangan' => 'Duplikat']]);
        $recordB = $importB->importData()->first();

        Livewire::actingAs($userA)
            ->test('data-table', [
                'importIds' => [$importA->id],
                'editableColumns' => ['Desa'],
            ])
            ->call('updateCell', $recordB->id, 'Desa', 'SONOWANGI')
            ->assertStatus(403);

        $this->assertArrayNotHasKey('Desa', $recordB->fresh()->row_data);
    }

    public function test_mount_merge_ditolak_untuk_import_milik_orang(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $importB = $this->l3Import($userB, [['Nomor Perforasi' => 'PF-DT-X', 'Keterangan' => 'Duplikat']]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($userA)->test('data-table', ['importIds' => [$importB->id]]);
    }

    public function test_tab_rusak_mendeteksi_nomor_hilang_per_nomor(): void
    {
        $user = User::factory()->create();
        $this->l3Import($user, [
            ['Nomor Perforasi' => '116741001'],
            ['Nomor Perforasi' => 'JT 116741002'],
            ['Nomor Perforasi' => '116741004'],
            ['Nomor Perforasi' => '116741005'],
            ['Nomor Perforasi' => 'PF-DT-NONNUM'],
        ]);

        $response = $this->actingAs($user)->get('/data/rusak');

        $response->assertOk();
        // Celah interior: 003 hilang di tengah rentang 001..005.
        $response->assertSee('JT 116741003');
        // Di luar rentang = stok, bukan rusak.
        $response->assertDontSee('JT 116741006');
        // Nomor yang ada bukan rusak.
        $response->assertDontSee('JT 116741001');
        $response->assertSee('nomor terdeteksi rusak');
        $response->assertSee('116741');
    }

    public function test_tab_rusak_tanpa_import_l3_tampil_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/data/rusak');

        $response->assertOk();
        $response->assertSee('Belum ada data');
        $response->assertSee('Upload laporan model L3');
    }

    public function test_tab_rusak_tanpa_celah_tampil_tidak_ada_rusak(): void
    {
        $user = User::factory()->create();
        $this->l3Import($user, [
            ['Nomor Perforasi' => '116741001'],
            ['Nomor Perforasi' => '116741002'],
            ['Nomor Perforasi' => '116741003'],
        ]);

        $response = $this->actingAs($user)->get('/data/rusak');

        $response->assertOk();
        $response->assertSee('Tidak ada nomor rusak terdeteksi');
    }

    public function test_tab_rusak_isolasi_antar_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->l3Import($userA, [
            ['Nomor Perforasi' => '116741001'],
            ['Nomor Perforasi' => '116741004'],
        ]);

        $response = $this->actingAs($userB)->get('/data/rusak');

        $response->assertOk();
        $response->assertSee('Belum ada data');
        $response->assertDontSee('JT 116741002');
        $response->assertDontSee('JT 116741003');
    }

    public function test_tab_rusak_muncul_di_semua_halaman_menu_data(): void
    {
        $user = User::factory()->create();
        $this->l3Import($user, [['Nomor Perforasi' => '116741001']]);

        foreach (['/data/data-import', '/data/pelaksanaan-kantor', '/data/pelaksanaan-luar-kantor', '/data/duplikat', '/data/bukan-duplikat', '/data/rusak'] as $uri) {
            $this->actingAs($user)->get($uri)
                ->assertOk()
                ->assertSee('/data/rusak', false);
        }
    }

    public function test_tab_pelaksanaan_tidak_error_dengan_join_column(): void
    {
        $user = User::factory()->create();

        $pn = Import::factory()->for($user)->create(['status' => 'success', 'table_name' => 'laporan peristiwa nikah']);
        ImportData::factory()->for($pn)->create([
            'row_data' => ['Nomor Daftar' => 'ND-001', 'Nikah Di' => 'KANTOR', 'Nama Suami' => 'Budi'],
            'row_number' => 2,
        ]);
        ImportData::factory()->for($pn)->create([
            'row_data' => ['Nomor Daftar' => 'ND-002', 'Nikah Di' => 'BEDOL', 'Nama Suami' => 'Agus'],
            'row_number' => 3,
        ]);

        $pdk = Import::factory()->for($user)->create(['status' => 'success', 'table_name' => 'laporan pendaftaran nikah']);
        ImportData::factory()->for($pdk)->create([
            'row_data' => ['Nomor Daftar' => 'ND-001', 'Nikah Di' => 'KANTOR', 'Nama Istri' => 'Siti'],
            'row_number' => 2,
        ]);
        ImportData::factory()->for($pdk)->create([
            'row_data' => ['Nomor Daftar' => 'ND-002', 'Nikah Di' => 'BEDOL', 'Nama Istri' => 'Aminah'],
            'row_number' => 3,
        ]);

        // Sebelum fix mergeByColumn tidak mengisi _row_id → ErrorException wire:key (500).
        $this->actingAs($user)->get('/data/pelaksanaan-kantor')
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Siti')
            ->assertDontSee('Agus');

        $this->actingAs($user)->get('/data/pelaksanaan-luar-kantor')
            ->assertOk()
            ->assertSee('Agus')
            ->assertSee('Aminah')
            ->assertDontSee('Budi');
    }
}
