<?php

use App\Http\Controllers\DataTableController;
use App\Http\Controllers\ImportBatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DataTableController::class, 'index'])->name('home');
Route::post('/import', [ImportBatchController::class, 'store'])->name('import.store');
Route::post('/import/chunk', [ImportBatchController::class, 'storeChunk'])->name('import.chunk');
Route::get('/import-batches', [ImportBatchController::class, 'status'])->name('import.status');
Route::get('/pelanggan/{idpel}/pemakaian', [DataTableController::class, 'grafik'])->name('pelanggan.grafik');
