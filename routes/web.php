<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
