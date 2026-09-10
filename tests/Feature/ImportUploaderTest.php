<?php

namespace Tests\Feature;

use App\Models\Import;
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

    private function uploadedXlsx(array $sheets): UploadedFile
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

        return UploadedFile::fake()->createWithContent('data_karyawan.xlsx', $contents);
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
}
