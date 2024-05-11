<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FeedbackController;

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

    Route::get('/user/orders', [UserController::class, 'ordersInfos']);
    Route::get('/user/infos', [UserController::class, 'userInfos']);

    Route::post('/order/start', [OrderController::class, 'newOrder']);
    Route::patch('/order/update/{orderId}', [OrderController::class, 'update']);
    Route::post('/order/upload/{orderId}', [OrderController::class, 'uploadTrack']);
    Route::patch('/order/complete/{orderId}', [OrderController::class, 'complete']);
    Route::get('/order/{orderId}', [OrderController::class, 'orderInfos']);

    Route::post('/{orderId}/version/new', [FeedbackController::class, 'newVersion']);
    Route::patch('/{orderId}/feedback/new', [FeedbackController::class, 'newFeedback']);
});

Route::middleware(['auth:sanctum', 'role:super admin'])->group(function () {
    Route::get('/superadmin/all', [AdminController::class, 'allData']);
    Route::get('/superadmin/orders', [AdminController::class, 'allOrders']);
    Route::get('/superadmin/users', [UserController::class, 'allUsers']);
});
