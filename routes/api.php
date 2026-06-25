<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\GuestController;

// IAE-T2: Proteksi endpoint dengan X-IAE-KEY (NIM)
Route::prefix('v1/guests')->middleware('api.key')->group(function () {
    
    // Collection: GET /api/v1/guests → Mengambil daftar data
    Route::get('/', [GuestController::class, 'index']);
    
    // Resource: GET /api/v1/guests/{id} → Mengambil data spesifik
    Route::get('/{id}', [GuestController::class, 'show']);
    
    // Action: POST /api/v1/guests → Menambah data baru
    Route::post('/', [GuestController::class, 'store']);

});