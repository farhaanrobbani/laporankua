<?php

use App\Http\Controllers\OtomasiLicenseController;
use Illuminate\Support\Facades\Route;

Route::post('/otomasi/license', [OtomasiLicenseController::class, 'check'])
    ->middleware('throttle:120,1')
    ->name('api.otomasi.license');

Route::post('/bimwin/license', [OtomasiLicenseController::class, 'check'])
    ->middleware('throttle:120,1')
    ->name('api.bimwin.license');
