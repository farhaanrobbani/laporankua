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

    public function test_guest_tidak_bisa_akses_data(): void
    {
        $this->get('/data')->assertRedirect('/login');
        $this->get('/data/export')->assertRedirect('/login');
    }

    public function test_index_menampilkan_picker_dan_tabel(): void
    {
        $user = User::factory()->create();
        $import = $this->importWithRows($user);

        $response = $this->actingAs($user)->get('/data?import_id='.$import->id);

        $response->assertOk();
        $response->assertSee('Budi');
        $response->assertSee('Siti');
        $response->assertSee($import->file_name);
    }

    public function test_isolasi_data_antar_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $importB = $this->importWithRows($userB, [['Rahasia', 999]]);
        $recordB = $importB->importData()->first();

        // Picker tidak menampilkan import user lain
        $this->actingAs($userA)->get('/data')->assertOk()->assertDontSee($importB->file_name);

        // Akses langsung import user lain → 404
        $this->actingAs($userA)->get('/data?import_id='.$importB->id)->assertNotFound();

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
}
