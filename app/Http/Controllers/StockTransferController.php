<?php

namespace App\Http\Controllers;

use App\Models\ProductBatch;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class StockTransferController extends Controller
{
    public function stockTransfer(Request $request)
    {
        try {
            DB::beginTransaction();
            $req = json_decode($request->getContent());
            $user = auth()->guard('api')->user();
            // $userAccess = DB::table('staff_access')
            //     ->join('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
            //     ->select(
            //         'staff_access.idstore_warehouse',
            //         'staff_access.idstaff_access',
            //         'store_warehouse.is_store',
            //         'staff_access.idstaff'
            //     )
            //     ->where('staff_access.idstaff', $user->id)
            //     ->where('store_warehouse.is_store', 0)
            //     ->first();
            $vendorDetail = DB::table('vendor')
            ->where('idvendor', $req->idvendor)
            ->first();
            if($vendorDetail){
                foreach ($req->products as $pro) {
                    $vendorPurchaseDetail=null;
                    $productBatchDetail = DB::table('product_batch')
                        ->where('idproduct_master', $pro->idproduct_master)
                        ->where('idstore_warehouse', $pro->idstore_warehouse)
                        ->where('mrp', $pro->mrp)
                        ->where('name', $pro->batch)
                        ->first();
                    $vendorPurchaseDetail = DB::table('vendor_purchases_detail')
                        ->where('idproduct_master', $pro->idproduct_master)
                        ->where('idvendor_purchases_detail', $pro->idvendor_purchases_detail)
                        ->first();
                    
                    if (isset($productBatchDetail->idproduct_batch) && isset($vendorPurchaseDetail->idvendor_purchases_detail)) {
                        DB::table('product_batch')
                            ->where('idproduct_batch', $productBatchDetail->idproduct_batch)
                            ->update([
                                'quantity' => DB::raw('quantity + ' . $pro->quantity),
                                'selling_price' => $vendorPurchaseDetail->selling_price!=''?$vendorPurchaseDetail->selling_price:0,
                                'purchase_price' => $vendorPurchaseDetail->unit_purchase_price!=''?$vendorPurchaseDetail->unit_purchase_price:0,
                                'mrp' => $pro->mrp,
                                'product'=>$vendorPurchaseDetail->product,
                                'copartner'=>$vendorPurchaseDetail->copartner,
                                'land'=>$vendorPurchaseDetail->land,
                            ]);
                    } else { 
                        $batch = array(
                            'idstore_warehouse' => $pro->idstore_warehouse,
                            'idproduct_master' => $pro->idproduct_master,
                            'name' => $pro->batch,
                            'purchase_price' => floatval($vendorPurchaseDetail->unit_purchase_price),
                            'selling_price' => floatval($vendorPurchaseDetail->selling_price),
                            'mrp' => floatval($pro->mrp),
                            'product'=>$vendorPurchaseDetail->product,
                            'copartner'=>$vendorPurchaseDetail->copartner,
                            'land'=>$vendorPurchaseDetail->land,
                            'discount' => 0,
                            'quantity' =>  $vendorPurchaseDetail->quantity,
                            'expiry' => $vendorPurchaseDetail->expiry,
                            'created_by' => $user->id, // replace 1 with $user->id
                            'updated_by' => $user->id, // replace 1 with $user->id
                            'status' => 1
                        );
                        $pb = ProductBatch::create($batch);
                    
                    }

                    $productInvDetail = DB::table('inventory')
                        ->where('idproduct_master', $pro->idproduct_master)
                        ->where('idstore_warehouse', $pro->idstore_warehouse)
                        ->first();

                    if ($productInvDetail && $productInvDetail->idinventory) {
                        DB::table('inventory')
                            ->where('idproduct_master', $pro->idproduct_master)
                            ->where('idstore_warehouse', $pro->idstore_warehouse)
                            ->update([
                                'quantity' => DB::raw('quantity + ' . $pro->quantity),
                                'selling_price' => $vendorPurchaseDetail->selling_price!=''?$vendorPurchaseDetail->selling_price:0,
                                'purchase_price' => $vendorPurchaseDetail->unit_purchase_price!=''?$vendorPurchaseDetail->unit_purchase_price:0,
                                'mrp' => $pro->mrp,
                                'product'=>$vendorPurchaseDetail->product,
                                'copartner'=>$vendorPurchaseDetail->copartner,
                                'land'=>$vendorPurchaseDetail->land
                            ]);
                    } else {
                        $inv = array(
                            'idstore_warehouse' => $pro->idstore_warehouse,
                            'idproduct_master' => $pro->idproduct_master,
                            'purchase_price' => $vendorPurchaseDetail->unit_purchase_price!=''?$vendorPurchaseDetail->unit_purchase_price:0,
                            'selling_price' => $vendorPurchaseDetail->selling_price!=''?$vendorPurchaseDetail->selling_price:0,
                            'mrp' => floatval($pro->mrp),
                            'product'=>$vendorPurchaseDetail->product,
                            'copartner'=>$vendorPurchaseDetail->copartner,
                            'land'=>$vendorPurchaseDetail->land,
                            'discount' => 0,
                            'quantity' => $vendorPurchaseDetail->quantity,
                            'only_online' => 0,
                            'only_offline' => 0,
                            'created_by' => $user->id, // replace 1 with $user->id
                            'updated_by' => $user->id, // replace 1 with $user->id
                            'status' => 1
                        );
                        $inv = Inventory::create($inv);
                    }
                }
                DB::commit();
                return response()->json(["statusCode" => 0, "message" => "Success"], 200);
            }else{
                return response()->json(["statusCode" => 1, "message" => '', "err" => 'vendor does not exist'], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["statusCode" => 1, "message" => '', "err" => $e->getMessage()], 200);
        }
    }
}
