<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'imports' => Import::count(),
            'reports' => Report::count(),
            'records' => ImportData::count(),
        ];

        $recentImports = Import::latest()->limit(10)->get();
        $recentReports = Report::latest()->limit(10)->get();

        return view('admin.index', compact('stats', 'recentImports', 'recentReports'));
    }
}
