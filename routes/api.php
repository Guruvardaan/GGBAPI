<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductReportController;
use App\Http\Controllers\API\InventoryReportController;
use App\Http\Controllers\API\WarehouseReportController;
use App\Http\Controllers\API\SystemReportController;
use App\Http\Controllers\API\UpdateRecordController;
use App\Http\Controllers\API\InventoryThresholdController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PackegeController;
use App\Http\Controllers\API\GstReportController;
use App\Http\Controllers\API\ExcelController;
use App\Http\Controllers\API\PurchaseOrderController;


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

Route::controller(InventoryThresholdController::class)->group(function () {
    Route::prefix('inventory-threshold')->group(function () {
        Route::get('list', 'index');
        Route::post('create', 'store');
        Route::post('edit/{id}', 'update');
        Route::get('view/{id}', 'show');
        Route::delete('delete/{id}', 'destroy');
    });    
});

Route::get('inventory-threshold-products', [InventoryThresholdController::class, 'get_inventory_threshold_products']);
Route::post('place-order-threshold-product', [InventoryThresholdController::class, 'place_order_threshold_product']);
Route::get('threshold-order', [InventoryThresholdController::class, 'get_threshold_order']);
Route::get('sync-inventory', [InventoryThresholdController::class, 'sync_inventory_with_purchase_order']);

/* Records Update */
Route::get('update-product-records', [UpdateRecordController::class, 'update_product_records']);
Route::get('update-category-records', [UpdateRecordController::class, 'update_category_records']);
Route::get('update-sub-category-records', [UpdateRecordController::class, 'update_sub_category_records']);
Route::get('update-sub-sub-category-records', [UpdateRecordController::class, 'update_sub_sub_category_records']);
Route::get('update-brands-records', [UpdateRecordController::class, 'update_brands_records']);
Route::post('storeInventory', [InventoryController::class, 'StoreInventory']);

Route::get('findByBarcode/{barcode}/{idStore}', [ProductController::class, 'findByBarcode']);

Route::post('add-inventory', [ProductController::class, 'add_inventory_and_batch']);
Route::get('default-inventory', [ProductController::class, 'get_default_inventory_product']);

Route::post('add-product', [ProductController::class, 'store']);
Route::post('add-store-package', [PackegeController::class, 'store']);

//GST Report
Route::get('gstr1', [GstReportController::class, 'get_gstr1']);
Route::get('gstr2', [GstReportController::class, 'get_gstr2']);
Route::get('gstr1-detail', [GstReportController::class, 'customer_order_artical_wise']);
Route::get('gstr2-detail', [GstReportController::class, 'purchase_order_artical_wise']);
Route::get('download-excel-gstr1/{year}/{month}/{last_six_month?}', [ExcelController::class, 'download_excel_gstr1']);
Route::get('download-excel-gstr2/{year}/{month}/{last_six_month?}', [ExcelController::class, 'download_excel_gstr2']);


//Purchase Order
Route::post('place-order', [PurchaseOrderController::class, 'place_order']);
Route::get('purchase-order-list', [PurchaseOrderController::class, 'get_puchase_order']);
Route::get('generate-pdf/{start_date?}/{end_date?}', [PurchaseOrderController::class, 'generate_pdf']);
Route::get('view', [PurchaseOrderController::class, 'loadDataview']);