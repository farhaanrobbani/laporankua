<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(): View
    {
        $downloads = Download::with('uploader')->latest()->get();

        return view('downloads.index', compact('downloads'));
    }

    public function download(Download $download): StreamedResponse|RedirectResponse
    {
        if ($download->isExternal()) {
            return redirect($download->external_url);
        }

        if (! $download->file_path || ! Storage::disk('public')->exists($download->file_path)) {
            return back()->withErrors(['error' => 'File tidak ditemukan di server.']);
        }

        return Storage::disk('public')->download($download->file_path, $download->title.'.'.pathinfo($download->file_path, PATHINFO_EXTENSION));
    }
}
