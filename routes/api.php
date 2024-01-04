<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\BrandsUpdateController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\ShippingChargeMasterController;
use App\Http\Controllers\SmsTemplateMasterController;
use App\Http\Controllers\EmailTemplateMasterController;

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

Route::post('get-banner',[BannerController::class, 'getBanner']);
Route::post('create-banner',[BannerController::class, 'createBanner']);
Route::post('update-banner',[BannerController::class, 'updateBanner']);
Route::post('delete-banner',[BannerController::class, 'destroyBanner']);

Route::post('reorder',[ReorderController::class, 'reorder']);
Route::post('newly-added-products',[ProductsController::class, 'newlyAddedProducts']);
Route::post('frequently-bought-products',[ProductsController::class, 'frequentlyBoughtProducts']);
Route::post('most-popular-products',[ProductsController::class, 'mostPopularProducts']);
Route::post('deals-of-the-day-products',[ProductsController::class, 'dealsOfTheDayProducts']);
Route::post('create-issue',[SupportController::class, 'createIssue']);
Route::post('get-issues',[SupportController::class, 'getIssuesByID']);
Route::post('get-customer-issues',[SupportController::class, 'getIssuesByCustomer']);
Route::get('get-support-categories',[SupportController::class, 'getSupportCategories']);
Route::post('create-contact',[ContactController::class, 'createContact']);
Route::get('get-contact-categories',[ContactController::class, 'getContactCategories']);

Route::post('upload-product-excel',[BrandsUpdateController::class, 'uploadProductExcel']);

Route::post('create-slot',[SlotsController::class, 'createSlots']);
Route::post('update-slot',[SlotsController::class, 'updateSlots']);
Route::post('get-slot',[SlotsController::class, 'getSlots']);
Route::post('delete-slot',[SlotsController::class, 'destroySlot']);
Route::post('create-bulk-slot',[SlotsController::class, 'createBulkSlots']);
Route::post('update-slot-status',[SlotsController::class, 'updateSlotStatus']);

Route::get('online-order-data',  [OrderController::class, 'getOnlineOrder']);
Route::post('update-order-status',  [OrderController::class, 'updateOrderStatus']);
Route::get('get-shipping-charge',  [ShippingChargeMasterController::class, 'getShippingCharge']);
Route::post('create-shipping-charge',  [ShippingChargeMasterController::class, 'createShippingCharge']);
Route::post('update-shipping-charge',  [ShippingChargeMasterController::class, 'updateShippingCharge']);
Route::post('delete-shipping-charge',  [ShippingChargeMasterController::class, 'deleteShippingCharge']);

Route::get('get-sms-template',  [SmsTemplateMasterController::class, 'getsmsTemplate']);
Route::post('create-sms-template',  [SmsTemplateMasterController::class, 'createsmsTemplate']);
Route::post('update-sms-template',  [SmsTemplateMasterController::class, 'updatesmsTemplate']);
Route::post('delete-sms-template',  [SmsTemplateMasterController::class, 'deletesmsTemplate']);

Route::get('get-email-template',  [EmailTemplateMasterController::class, 'getemailTemplate']);
Route::post('create-email-template',  [EmailTemplateMasterController::class, 'createemailTemplate']);
Route::post('update-email-template',  [EmailTemplateMasterController::class, 'updateemailTemplate']);
Route::post('delete-email-template',  [EmailTemplateMasterController::class, 'deleteemailTemplate']);