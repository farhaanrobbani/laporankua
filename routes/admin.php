<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');

    Route::resource('users', UserController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
    Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');
});
