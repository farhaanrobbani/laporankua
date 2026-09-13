<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportData;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CetakNbController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->withCount('importData')
            ->latest()
            ->get();

        $grouped = $imports->groupBy('table_name')->map(function ($rows, $tableName) {
            return [
                'table_name' => $tableName,
                'total_rows' => $rows->sum('import_data_count'),
                'import_ids' => $rows->pluck('id')->toArray(),
            ];
        })->values();

        $selectedGroup = null;
        if ($request->filled('import_id')) {
            $import = Import::where('user_id', auth()->id())
                ->findOrFail($request->integer('import_id'));
            $selectedGroup = $grouped->firstWhere('table_name', $import->table_name);
        }

        return view('cetak-nb.index', compact('grouped', 'selectedGroup'));
    }

    public function print(Request $request): View
    {
        $validated = $request->validate([
            'import_id' => 'required|integer',
            'record' => 'required|integer',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);
        $this->authorize('view', $import);

        $importData = ImportData::where('id', $validated['record'])
            ->where('import_id', $import->id)
            ->first();

        if (! $importData) {
            abort(404, 'Record tidak ditemukan.');
        }

        $allIds = ImportData::where('import_id', $import->id)
            ->orderBy('row_number')
            ->pluck('id')
            ->all();

        $currentIndex = array_search($importData->id, $allIds, true);

        return view('reports.cetak-nb', [
            'report' => null,
            'importId' => $import->id,
            'records' => [$importData->row_data ?? []],
            'recordData' => $importData->row_data ?? [],
            'prevId' => $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
            'nextId' => $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
            'currentPosition' => $currentIndex + 1,
            'totalRecords' => count($allIds),
            'entryMode' => 'cetak-nb',
            'showNav' => count($allIds) > 1,
        ]);
    }
}
