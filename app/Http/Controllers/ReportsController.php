<?php

namespace App\Http\Controllers;

use App\Models\ImportData;
use App\Models\Report;
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

        $dataset = $service->buildDataset(
            $report->import,
            $fields === [] ? $report->import->availableColumns() : $fields,
            $config['search'] ?? null,
            $config['filter_column'] ?? null,
            $config['filter_value'] ?? null,
            $config['sort_column'] ?? null,
            $config['sort_direction'] ?? 'asc',
        );
        $dataset['title'] = $report->title;
        $dataset['table_layout'] = $config['table_layout'] ?? null;

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
