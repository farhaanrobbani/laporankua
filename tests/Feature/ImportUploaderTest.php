<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportUploaderTest extends TestCase
{
    use RefreshDatabase;

    private function uploadedXlsx(array $sheets, string $filename = 'data_karyawan.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $first = true;

        foreach ($sheets as $name => $rows) {
            $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($name);

            foreach ($rows as $r => $row) {
                foreach (array_values($row) as $c => $value) {
                    $sheet->setCellValue([$c + 1, $r + 1], $value);
                }
            }

            $first = false;
        }

        $path = sys_get_temp_dir().'/upload_'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($filename, $contents);
    }

    public function test_upload_menampilkan_sheet_dan_pratinjau(): void
    {
        $user = User::factory()->create();

        $file = $this->uploadedXlsx([
            'Karyawan' => [['Nama', 'Gaji'], ['Budi', 5000000]],
            'Lainnya' => [['X'], ['Y']],
        ]);

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file])
            ->assertHasNoErrors()
            ->assertSee('Karyawan')
            ->assertSee('Lainnya')
            ->assertSee('Budi');
    }

    public function test_file_bukan_excel_ditolak(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('catatan.txt', 10, 'text/plain');

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file])
            ->assertHasErrors(['file']);
    }

    public function test_konfirmasi_membuat_import_dan_redirect(): void
    {
        $user = User::factory()->create();

        $file = $this->uploadedXlsx([
            'Karyawan' => [['Nama', 'Gaji'], ['Budi', 5000000], ['Siti', 6000000]],
        ]);

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file])
            ->set('sheet', 'Karyawan')
            ->call('confirmImport')
            ->assertHasNoErrors()
            ->assertRedirect(route('imports.show', Import::latest()->first()));

        $import = Import::latest()->first();

        $this->assertSame($user->id, $import->user_id);
        $this->assertSame('data_karyawan.xlsx', $import->file_name);
        $this->assertSame('Karyawan', $import->sheet_name);
        // Queue sync saat testing: job langsung jalan
        $this->assertSame('success', $import->fresh()->status);
        $this->assertSame(2, $import->fresh()->imported_rows);
    }

    public function test_append_pendaftaran_hanya_menghitung_baris_baru(): void
    {
        $user = User::factory()->create();

        $file1 = $this->uploadedXlsx([
            'Data' => [
                ['Nomor Daftar', 'Nama Suami', 'Nama Istri'],
                ['ND001', 'Budi', 'Siti'],
                ['ND002', 'Andi', 'Aya'],
            ],
        ], 'laporan-pendaftaran-nikah-09-2026.xlsx');

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file1])
            ->set('sheet', 'Data')
            ->call('confirmImport')
            ->assertHasNoErrors();

        $first = Import::latest('id')->first();

        $this->assertSame('laporan pendaftaran nikah', $first->table_name);
        $this->assertSame('No. Daftar', $first->dedup_column);
        $this->assertSame('success', $first->fresh()->status);
        $this->assertSame(2, $first->fresh()->imported_rows);

        $file2 = $this->uploadedXlsx([
            'Data' => [
                ['Nomor Daftar', 'Nama Suami', 'Nama Istri'],
                ['ND001', 'Budi', 'Siti'],
                ['ND002', 'Andi', 'Aya'],
                ['ND003', 'Cecep', 'Imas'],
            ],
        ], 'laporan-pendaftaran-nikah-09-2026.xlsx');

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file2])
            ->set('sheet', 'Data')
            ->call('confirmImport')
            ->assertHasNoErrors();

        $second = Import::latest('id')->first();

        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue((bool) $second->fresh()->is_append);
        $this->assertSame('appended', $second->fresh()->status);
        // Hanya ND003 yang baru; ND001/ND002 di-skip sebagai baris lama.
        $this->assertSame(1, $second->fresh()->imported_rows);
        $this->assertSame(1, $second->importData()->count());

        // Link Data tampil untuk record appended.
        $this->actingAs($user)
            ->get(route('imports.show', $second))
            ->assertOk()
            ->assertSee(route('imports.data', $second));
    }

    public function test_upload_ulang_normal_tidak_menggandakan_baris(): void
    {
        $user = User::factory()->create();

        $file1 = $this->uploadedXlsx([
            'Data' => [
                ['Nomor Daftar', 'Nama Suami', 'Nama Istri'],
                ['ND001', 'Budi', 'Siti'],
                ['ND002', 'Andi', 'Aya'],
            ],
        ], 'laporan-pendaftaran-nikah-09-2026.xlsx');

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file1])
            ->set('sheet', 'Data')
            ->call('confirmImport')
            ->assertHasNoErrors();

        $first = Import::latest('id')->first();

        $file2 = $this->uploadedXlsx([
            'Data' => [
                ['Nomor Daftar', 'Nama Suami', 'Nama Istri'],
                ['ND001', 'Budi Updated', 'Siti'],
                ['ND003', 'Cecep', 'Imas'],
            ],
        ], 'laporan-pendaftaran-nikah-10-2026.xlsx');

        Livewire::actingAs($user)
            ->test('import-uploader')
            ->upload('file', [$file2])
            ->set('sheet', 'Data')
            ->call('confirmImport')
            ->assertHasNoErrors();

        $second = Import::latest('id')->first();

        $this->assertNotSame($first->id, $second->id);
        $this->assertFalse((bool) $second->fresh()->is_append);
        $this->assertSame('success', $second->fresh()->status);
        // ND001 di-overwrite (pindah ke import baru), hanya ND003 yang benar-benar baru.
        $this->assertSame(1, $second->fresh()->imported_rows);

        $allRows = ImportData::whereIn('import_id', [$first->id, $second->id])->get();

        // Dengan fix: ND001 di-overwrite (pindah) → 3 baris unik (ND001, ND002, ND003).
        // Tanpa fix: ND001 terinsert ganda → 4 baris.
        $this->assertSame(3, $allRows->count());
        $this->assertSame(3, $allRows->pluck('dedup_key_value')->unique()->count());
        $this->assertSame(1, $allRows->where('dedup_key_value', 'ND001')->count());

        $nd1 = $allRows->firstWhere('dedup_key_value', 'ND001');
        $this->assertSame($second->id, $nd1->import_id);
        $this->assertSame('Budi Updated', $nd1->row_data['Nama Suami']);
    }
}
