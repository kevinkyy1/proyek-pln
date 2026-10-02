<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataTableController;
use App\Http\Controllers\ImportBatchController;
use Illuminate\Support\Facades\Route;

// --- Auth routes (guest only) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

// --- Logout (auth only) ---
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Protected routes ---
Route::middleware('auth')->group(function () {
    Route::get('/', [DataTableController::class, 'index'])->name('home');
    Route::post('/import', [ImportBatchController::class, 'store'])->name('import.store');
    Route::post('/import/chunk', [ImportBatchController::class, 'storeChunk'])->name('import.chunk');
    Route::get('/import-batches', [ImportBatchController::class, 'status'])->name('import.status');
    Route::get('/pelanggan/{idpel}/pemakaian', [DataTableController::class, 'grafik'])->name('pelanggan.grafik');
});
