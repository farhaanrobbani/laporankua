<?php

namespace App\Http\Controllers;

use App\Models\ImportData;
use App\Models\Report;
use App\Services\MergeService;
use App\Services\ReportGenerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $reports = Report::where('user_id', auth()->id())
            ->with('import')
            ->latest()
            ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load(['import', 'reportTemplate']);

        $firstRecord = ImportData::where('import_id', $report->import_id)
            ->orderBy('row_number')
            ->first();

        return view('reports.show', compact('report', 'firstRecord'));
    }

    public function download(Report $report): StreamedResponse|SymfonyRedirectResponse
    {
        $this->authorize('view', $report);

        if (! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
            return redirect()->route('reports.show', $report)
                ->with('status', 'File laporan belum tersedia.');
        }

        $extensions = ['pdf' => 'pdf', 'word' => 'docx', 'excel' => 'xlsx'];
        $extension = $extensions[$report->output_format] ?? 'bin';

        if ($report->status !== 'downloaded') {
            $report->update(['status' => 'downloaded']);
        }

        return Storage::disk('local')->download(
            $report->file_path,
            $report->title.'.'.$extension
        );
    }

    public function print(Report $report, ReportGenerationService $service): View
    {
        $this->authorize('view', $report);

        $config = $report->config_json ?? [];
        $fields = array_values(array_filter($config['fields'] ?? []));

        if (! empty($config['is_merged']) && ! empty($config['merged_import_ids']) && ! empty($config['join_column'])) {
            $mergeService = app(MergeService::class);
            $allColumns = $mergeService->getAllColumns($config['merged_import_ids']);
            $dataset = $mergeService->buildMergedDataset(
                $config['merged_import_ids'],
                $config['join_column'],
                $fields === [] ? $allColumns : $fields,
                $config['search'] ?? null,
                $config['filter_column'] ?? null,
                $config['filter_value'] ?? null,
                $config['sort_column'] ?? null,
                $config['sort_direction'] ?? 'asc',
            );
        } else {
            $dataset = $service->buildDataset(
                $report->import,
                $fields === [] ? $report->import->availableColumns() : $fields,
                $config['search'] ?? null,
                $config['filter_column'] ?? null,
                $config['filter_value'] ?? null,
                $config['sort_column'] ?? null,
                $config['sort_direction'] ?? 'asc',
            );
        }
        $dataset['title'] = $report->title;
        $dataset['table_layout'] = $config['table_layout'] ?? null;
        $dataset['kecamatan'] = $config['kecamatan'] ?? null;
        $dataset['nama_kepala_kua'] = $config['nama_kepala_kua'] ?? null;
        $dataset['nip_kepala'] = $config['nip_kepala'] ?? null;
        $dataset['nama_kementerian'] = $config['nama_kementerian'] ?? null;
        $dataset['nama_kantor_kota'] = $config['nama_kantor_kota'] ?? null;
        $dataset['nama_kantor'] = $config['nama_kantor'] ?? null;
        $dataset['alamat_kantor'] = $config['alamat_kantor'] ?? null;
        $dataset['telepon_kantor'] = $config['telepon_kantor'] ?? null;
        $dataset['email_kantor'] = $config['email_kantor'] ?? null;
        $dataset['logo_kantor'] = $config['logo_kantor'] ?? null;
        $dataset['font_size_kop_kementerian'] = $config['font_size_kop_kementerian'] ?? null;
        $dataset['font_size_kop_kantor_kota'] = $config['font_size_kop_kantor_kota'] ?? null;
        $dataset['font_size_kop_kantor'] = $config['font_size_kop_kantor'] ?? null;
        $dataset['font_size_kop_alamat'] = $config['font_size_kop_alamat'] ?? null;
        $dataset['font_size_kop_kontak'] = $config['font_size_kop_kontak'] ?? null;

        return view('reports.print', compact('dataset'));
    }

    public function cetakNb(Report $report, int $record): View
    {
        $this->authorize('view', $report);

        $importData = ImportData::where('id', $record)
            ->where('import_id', $report->import_id)
            ->first();

        if (! $importData) {
            abort(404, 'Record tidak ditemukan.');
        }

        $recordData = $importData->row_data ?? [];

        $allIds = ImportData::where('import_id', $report->import_id)
            ->orderBy('row_number')
            ->pluck('id')
            ->all();

        $currentIndex = array_search($record, $allIds, true);

        return view('reports.cetak-nb', [
            'report' => $report,
            'importId' => $report->import_id,
            'records' => [$recordData],
            'recordData' => $recordData,
            'prevId' => $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
            'nextId' => $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
            'currentPosition' => $currentIndex + 1,
            'totalRecords' => count($allIds),
            'entryMode' => 'report',
            'multiMode' => false,
            'showNav' => true,
        ]);
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }

        $report->delete();

        return redirect()->route('reports.index')
            ->with('status', 'Laporan berhasil dihapus.');
    }
}
