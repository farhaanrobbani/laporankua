<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index(): View
    {
        $downloads = Download::with('uploader')->latest()->get();

        return view('admin.downloads.index', compact('downloads'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240',
            'external_url' => 'nullable|url|max:500',
        ]);

        if (empty($validated['file']) && empty($validated['external_url'])) {
            return back()->withErrors(['file' => 'Upload file atau masukkan link wajib diisi.']);
        }

        $download = new Download;
        $download->title = $validated['title'];
        $download->description = $validated['description'] ?? null;
        $download->uploaded_by = auth()->id();

        if (! empty($validated['file'])) {
            $file = $validated['file'];
            $path = $file->store('downloads', 'public');
            $download->file_path = $path;
            $download->file_type = $file->getMimeType();
            $download->file_size = $file->getSize();
        } else {
            $download->external_url = $validated['external_url'];
        }

        $download->save();

        return redirect()->route('admin.downloads.index')->with('success', 'File berhasil ditambahkan.');
    }

    public function destroy(Download $download): RedirectResponse
    {
        if ($download->file_path) {
            Storage::disk('public')->delete($download->file_path);
        }

        $download->delete();

        return redirect()->route('admin.downloads.index')->with('success', 'File berhasil dihapus.');
    }
}
