<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes for Laundry Management System
Route::middleware(['auth:sanctum'])->group(function () {
    // Order API Routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderApiController::class, 'index']);
        Route::post('/', [OrderApiController::class, 'store']);
        Route::get('/{order}', [OrderApiController::class, 'show']);
        Route::patch('/{order}/status', [OrderApiController::class, 'updateStatus']);
    });
}); 