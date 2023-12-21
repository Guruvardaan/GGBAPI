<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductReportController;
use App\Http\Controllers\API\InventoryReportController;
use App\Http\Controllers\API\WarehouseReportController;
use App\Http\Controllers\API\SystemReportController;


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
Route::post('inventory/expried-and-expiring-report', [InventoryReportController::class, 'expried_and_expiring_inventory']);
Route::post('performance-report', [SystemReportController::class, 'get_performance_report']);
Route::post('inventory-profitability-report', [SystemReportController::class, 'get_inventory_profitability_report']);
Route::post('inventory-value-report', [SystemReportController::class, 'get_value_report']);
Route::post('stock-levels-report', [SystemReportController::class, 'get_stock_levels_report']);
Route::post('inventory-forecasting-report', [SystemReportController::class, 'inventory_forecasting_report']);
Route::get('sales-report', [SystemReportController::class, 'get_sales_report']);
Route::get('cogs-report', [SystemReportController::class, 'get_cogs_report']);
Route::get('purchase-order-report', [SystemReportController::class, 'get_purchase_order_report']);