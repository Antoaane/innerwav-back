<?php

use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AuthController;

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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/test', function (Request $request) {
    return response()->json(['message' => 'Ceci est une réponse de test']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/safe-test', function (Request $request) {
        return response()->json(['message' => 'Ceci est une réponse de test pour les utilisateurs authentifiés']);
    });

    Route::post('/order/start', [OrderController::class, 'start']);
    Route::patch('/order/update/{orderId}', [OrderController::class, 'update']);
    Route::patch('/order/complete/{orderId}', [OrderController::class, 'complete']);
});
