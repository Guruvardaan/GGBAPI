<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\InventoryController;
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
Route::prefix('auth')->group(function () {
    // Your API routes here
    Route::get('/sample-api', [APIController::class, 'getSampleData']);
});
// Route::get('/sample-api', [APIController::class, 'getSampleData']);
Route::get('/', function () {
    return response()->json([
        'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
    ]);
});
Route::get('/check-db-connection', 'App\Http\Controllers\DatabaseController@checkConnection');
Route::get('/orderReport',  [OrderController::class, 'getUserData']);
Route::get('/orderDetail',  [OrderDetailController::class, 'getOrderData']);
Route::post('get-token', [AuthController::class, 'getToken']);

Route::post('/storeInventory', [InventoryController::class, 'StoreInventory']);