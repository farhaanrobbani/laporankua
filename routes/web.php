<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/upload', [ImportController::class, 'create'])->name('imports.create');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    Route::get('/data', [DataController::class, 'index'])->name('data.index');
    Route::get('/data/export', [DataController::class, 'export'])->name('data.export');
    Route::get('/data/{record}', [DataController::class, 'show'])->name('data.show');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportsController::class, 'create'])->name('reports.create');
    Route::get('/reports/{report}', [ReportsController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/download', [ReportsController::class, 'download'])->name('reports.download');
    Route::get('/reports/{report}/print', [ReportsController::class, 'print'])->name('reports.print');
    Route::delete('/reports/{report}', [ReportsController::class, 'destroy'])->name('reports.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
