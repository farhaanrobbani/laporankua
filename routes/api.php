<?php

use App\Http\Controllers\BimwinLicenseController;
use Illuminate\Support\Facades\Route;

Route::post('/bimwin/license', [BimwinLicenseController::class, 'check'])
    ->middleware('throttle:120,1')
    ->name('api.bimwin.license');
