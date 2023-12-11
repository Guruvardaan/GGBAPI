<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductReportController;
use App\Http\Controllers\API\InventoryReportController;
use App\Http\Controllers\API\WarehouseReportController;


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
Route::post('product-report', [ProductReportController::class, 'get_product_report']);
Route::post('inventory-report', [InventoryReportController::class, 'get_inventory_report']);
Route::post('warehouse-report', [WarehouseReportController::class, 'get_warehouse_report']);