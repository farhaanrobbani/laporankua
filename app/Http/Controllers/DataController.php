<?php

namespace App\Http\Controllers;

use App\Exports\ImportDataExport;
use App\Models\Import;
use App\Models\ImportData;
use App\Services\ReportGenerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->withCount('importData')
            ->latest()
            ->get();

        $selectedImport = null;
        if ($request->filled('import_id')) {
            $selectedImport = Import::where('user_id', auth()->id())
                ->findOrFail($request->integer('import_id'));
        }

        return view('data.index', compact('imports', 'selectedImport'));
    }

    public function show(ImportData $record): View
    {
        $this->authorize('view', $record->import);

        $record->load('import');

        return view('data.show', compact('record'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'import_id' => 'required|integer',
            'search' => 'nullable|string|max:255',
            'filter_column' => 'nullable|string|max:255',
            'filter_value' => 'nullable|string|max:255',
            'sort_column' => 'nullable|string|max:255',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);

        $headings = $import->availableColumns();

        $dataset = app(ReportGenerationService::class)->buildDataset(
            $import,
            $headings,
            $validated['search'] ?? null,
            $validated['filter_column'] ?? null,
            $validated['filter_value'] ?? null,
            $validated['sort_column'] ?? null,
            $validated['sort_direction'] ?? 'asc',
        );

        $filename = 'data_import_'.$import->id.'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ImportDataExport($headings, $dataset['rows']), $filename);
    }
}
