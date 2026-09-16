<?php

use App\Http\Controllers\CetakNbController;
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
    ->middleware(['auth', 'verified', 'throttle:120,1'])->name('dashboard');

Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/upload', [ImportController::class, 'create'])->name('imports.create');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::get('/imports/{import}/data', [ImportController::class, 'data'])->name('imports.data');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    Route::get('/data/data-import', [DataController::class, 'index'])->name('data.index');
    Route::get('/data/pelaksanaan-kantor', [DataController::class, 'pelaksanaanKantor'])->name('data.pelaksanaan-kantor');
    Route::get('/data/pelaksanaan-luar-kantor', [DataController::class, 'pelaksanaanLuarKantor'])->name('data.pelaksanaan-luar-kantor');
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

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.edit');
    Route::get('/profile/info', [ProfileController::class, 'info'])->name('profile.info');
    Route::get('/profile/kop-surat', [ProfileController::class, 'kopSurat'])->name('profile.kop-surat');
    Route::get('/profile/daftar-desa', [ProfileController::class, 'daftarDesa'])->name('profile.daftar-desa');
    Route::get('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/profile/delete', [ProfileController::class, 'delete'])->name('profile.delete');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/kop-surat', [ProfileController::class, 'updateKop'])->name('profile.update-kop');
    Route::patch('/profile/daftar-desa', [ProfileController::class, 'updateDesa'])->name('profile.update-desa');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
