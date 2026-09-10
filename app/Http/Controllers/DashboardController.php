<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $totalImports = Import::where('user_id', $userId)->count();

        $totalRecords = ImportData::whereHas('import', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $totalReports = Report::where('user_id', $userId)->count();

        $recentImports = Import::where('user_id', $userId)
            ->withCount('importData')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::where('user_id', $userId)
            ->with('import')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalImports',
            'totalRecords',
            'totalReports',
            'recentImports',
            'recentReports',
        ));
    }
}
