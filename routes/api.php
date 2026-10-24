<?php

use App\Http\Controllers\Api\SimulationController;
use App\Http\Controllers\Mock\MockBankController;
use Illuminate\Support\Facades\Route;

// Endpoints que simulam os bancos externos (somente MVP).
Route::prefix('mock/banks')->withoutMiddleware(['throttle:api'])->group(function () {
    Route::post('alfa', [MockBankController::class, 'alfa']);
    Route::post('beta', [MockBankController::class, 'beta']);
    Route::post('gamma', [MockBankController::class, 'gamma']);
});


Route::prefix('simulations')->group(function () {
    Route::post('/', [SimulationController::class, 'store'])->middleware('throttle:simulations');
    Route::get('{simulation}', [SimulationController::class, 'show'])->whereUuid('simulation');
});