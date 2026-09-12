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
            $recordIds = array_values($recordIds);
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
        $isPrintAll = count($recordIds) === $totalAllRecords && $totalAllRecords > 1;

        if ($isPrintAll) {
            $currentId = $validated['record'] ?? $allIds[0];
            $currentIndex = array_search($currentId, $allIds, true);

            if ($currentIndex === false) {
                $currentIndex = 0;
                $currentId = $allIds[0];
            }

            $importData = ImportData::where('id', $currentId)
                ->where('import_id', $import->id)
                ->first();

            if (! $importData) {
                abort(404, 'Record tidak ditemukan.');
            }

            $records = [$importData->row_data ?? []];

            return view('reports.cetak-nb', [
                'report' => null,
                'importId' => $import->id,
                'records' => $records,
                'recordData' => $records[0],
                'prevId' => $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
                'nextId' => $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
                'currentPosition' => $currentIndex + 1,
                'totalRecords' => $totalAllRecords,
                'entryMode' => 'cetak-nb',
                'multiMode' => false,
                'showNav' => true,
                'allRecordIds' => implode(',', $allIds),
            ]);
        }

        $importDataRecords = ImportData::where('import_id', $import->id)
            ->whereIn('id', $recordIds)
            ->orderBy('row_number')
            ->get();

        if ($importDataRecords->isEmpty()) {
            abort(404, 'Record tidak ditemukan.');
        }

        $records = $importDataRecords->map(fn ($item) => $item->row_data ?? [])->values()->all();
        $count = count($records);
        $showNav = $count > 1;

        $currentId = $recordIds[0];
        $selectedIndex = array_search($currentId, $recordIds, true);

        return view('reports.cetak-nb', [
            'report' => null,
            'importId' => $import->id,
            'records' => $records,
            'recordData' => $records[0],
            'prevId' => $showNav && $selectedIndex > 0 ? $recordIds[$selectedIndex - 1] : null,
            'nextId' => $showNav && $selectedIndex < count($recordIds) - 1 ? $recordIds[$selectedIndex + 1] : null,
            'currentPosition' => $showNav ? ($selectedIndex + 1) : null,
            'totalRecords' => $showNav ? count($recordIds) : $count,
            'entryMode' => 'cetak-nb',
            'multiMode' => $count > 1,
            'showNav' => $showNav,
            'allRecordIds' => $showNav ? implode(',', $recordIds) : null,
        ]);
    }
}
