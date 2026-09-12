<?php

use App\Http\Controllers\CetakNbController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'throttle:120,1'])->name('dashboard');

Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/upload', [ImportController::class, 'create'])->name('imports.create');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    Route::get('/data', [DataController::class, 'index'])->name('data.index');
    Route::get('/data/export', [DataController::class, 'export'])->name('data.export');
    Route::get('/data/cetak-nb', [DataController::class, 'cetakNb'])->name('data.cetak-nb');
    Route::get('/data/{record}', [DataController::class, 'show'])->name('data.show');

    Route::get('/cetak-nb', [CetakNbController::class, 'index'])->name('cetak-nb.index');
    Route::get('/cetak-nb/print', [CetakNbController::class, 'print'])->name('cetak-nb.print');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportsController::class, 'create'])->name('reports.create');
    Route::get('/reports/{report}', [ReportsController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/download', [ReportsController::class, 'download'])->name('reports.download');
    Route::get('/reports/{report}/print', [ReportsController::class, 'print'])->name('reports.print');
    Route::get('/reports/{report}/cetak-nb/{record}', [ReportsController::class, 'cetakNb'])->name('reports.cetak-nb');
    Route::delete('/reports/{report}', [ReportsController::class, 'destroy'])->name('reports.destroy');

    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/use', [TemplateController::class, 'use'])->name('templates.use');
    Route::post('/templates/{template}/apply', [TemplateController::class, 'apply'])->name('templates.apply');
    Route::post('/templates/{template}/default', [TemplateController::class, 'setDefault'])->name('templates.default');
    Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
