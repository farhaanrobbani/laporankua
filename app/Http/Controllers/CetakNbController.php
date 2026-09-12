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
            ->where('status', 'success')
            ->withCount('importData')
            ->latest()
            ->get();

        $selectedImport = null;
        if ($request->filled('import_id')) {
            $selectedImport = Import::where('user_id', auth()->id())
                ->findOrFail($request->integer('import_id'));
        }

        return view('cetak-nb.index', compact('imports', 'selectedImport'));
    }

    public function print(Request $request): View
    {
        $validated = $request->validate([
            'import_id' => 'required|integer',
            'record' => 'nullable|integer',
            'records' => 'nullable|string',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);
        $this->authorize('view', $import);

        $recordIds = [];

        if (! empty($validated['records'])) {
            $recordIds = array_map('intval', explode(',', $validated['records']));
            $recordIds = array_filter($recordIds, fn ($id) => $id > 0);
        } elseif (! empty($validated['record'])) {
            $recordIds = [(int) $validated['record']];
        }

        if ($recordIds === []) {
            abort(404, 'Tidak ada record yang dipilih.');
        }

        $allIds = ImportData::where('import_id', $import->id)
            ->orderBy('row_number')
            ->pluck('id')
            ->all();

        $totalAllRecords = count($allIds);
        $isPrintAll = count($recordIds) === $totalAllRecords;

        $importDataRecords = ImportData::where('import_id', $import->id)
            ->whereIn('id', $recordIds)
            ->orderBy('row_number')
            ->get();

        if ($importDataRecords->isEmpty()) {
            abort(404, 'Record tidak ditemukan.');
        }

        $records = $importDataRecords->map(fn ($item) => $item->row_data ?? [])->values()->all();
        $count = count($records);

        $showNav = $isPrintAll || $count > 1;

        if ($showNav && $isPrintAll) {
            $currentId = $recordIds[0];
            $currentIndex = array_search($currentId, $allIds, true);
        } elseif ($showNav && $count > 1) {
            $currentId = $recordIds[0];
            $currentIndex = array_search($currentId, $allIds, true);
        } else {
            $currentId = $recordIds[0];
            $currentIndex = array_search($currentId, $allIds, true);
        }

        return view('reports.cetak-nb', [
            'report' => null,
            'importId' => $import->id,
            'records' => $records,
            'recordData' => $records[0],
            'prevId' => $showNav && $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
            'nextId' => $showNav && $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
            'currentPosition' => $showNav ? ($currentIndex + 1) : null,
            'totalRecords' => $showNav ? count($allIds) : $count,
            'entryMode' => 'cetak-nb',
            'multiMode' => $count > 1,
            'showNav' => $showNav,
        ]);
    }
}
