<?php

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->withCount('importData')
            ->latest()
            ->paginate(10);

        return view('imports.index', compact('imports'));
    }

    public function create(): View
    {
        return view('imports.upload');
    }

    public function show(Import $import): View
    {
        $this->authorize('view', $import);

        $import->loadCount('importData');

        return view('imports.show', compact('import'));
    }

    public function destroy(Import $import): RedirectResponse
    {
        $this->authorize('delete', $import);

        foreach ($import->reports as $report) {
            if ($report->file_path) {
                Storage::disk('local')->delete($report->file_path);
            }
        }

        Storage::disk('local')->delete($import->file_path);
        $import->delete();

        return redirect()->route('imports.index')
            ->with('status', 'Data import berhasil dihapus.');
    }
}
