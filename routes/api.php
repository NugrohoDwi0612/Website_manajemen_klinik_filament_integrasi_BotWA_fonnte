<?php

use App\Models\Pasien;
use App\Models\Antrian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FonnteWebhookController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');;


Route::any('/fonnte/webhook', [FonnteWebhookController::class, 'handle']);
