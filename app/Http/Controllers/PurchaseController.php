<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class PurchaseController extends Controller
{
    public function getPurchases(Request $request)
    {
        $req=json_decode($request->getContent()); 
        $user = auth()->guard('api')->user(); 
                
        $userAccess = DB::table('staff_access')
            ->join('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
            ->select(
                'staff_access.idstore_warehouse',
                'staff_access.idstaff_access',
                'store_warehouse.is_store',
                'staff_access.idstaff'
            )
            ->where('staff_access.idstaff', $user->id) // replace 2 with $user->id
            ->first();

        try {
            if($userAccess){
                $orderMaster = DB::table('vendor_purchases')
                    ->leftJoin('vendor', 'vendor_purchases.idvendor', '=', 'vendor.idvendor')
                    ->select(
                        'vendor.name AS vendor_name',
                        'vendor.gst AS vendor_gst',
                        'vendor_purchases.*'
                    )->where('vendor_purchases.idstore_warehouse', $userAccess->idstore_warehouse); //replace 1 with $userAccess->idstore_warehouse

                if (isset($req->idvendor) > 0) {
                    $orderMaster->where('vendor_purchases.idvendor', $req->idvendor);
                }

                if (isset($req->bill_number)) {
                    $orderMaster->where('vendor_purchases.bill_number', $req->bill_number);
                } else {
                    $orderMaster->whereBetween('vendor_purchases.created_at', [$req->valid_from, $req->valid_till]);
                }
                $orderMaster->orderBy('vendor_purchases.idvendor_purchases', 'DESC');
                $purchaseData=$orderMaster->get();
                $purchaseArray=[];
                $i=0;
                foreach($purchaseData as $p){
                    $purchaseArray[$i]=$p;
                    $orderDetail = DB::table('vendor_purchases_detail')
                    ->leftJoin('vendor_purchases', 'vendor_purchases_detail.idvendor_purchases', '=', 'vendor_purchases.idvendor_purchases')
                    ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                    ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                    ->select(
                        'product_master.name AS prod_name',
                        'product_master.barcode',
                        'vendor_purchases_detail.*',
                        'product_batch.name as batch_name',
                        'product_batch.mrp as batch_mrp',
                        'product_batch.idproduct_batch as idproduct_batch'
                    )->where('vendor_purchases_detail.idvendor_purchases', $p->idvendor_purchases)
                    ->where('vendor_purchases.idstore_warehouse', $userAccess->idstore_warehouse) //replace 1 with $userAccess->idstore_warehouse
                    ->get();
                    $purchaseArray[$i]->purchase_details=$orderDetail;
                    $i++;
                }
                return response()->json(["statusCode" => 0, "message" => "Success", "data" => $purchaseArray], 200);
            }else{
                return response()->json(["statusCode" => 1, "message" => "", "err" => 'user Access required'], 200);
            }
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => '', "err" => $e->getMessage()], 200);
        }
    }
    public function getPurchaseDetails($id)
    {
        try {
            $user = auth()->guard('api')->user();
            $userAccess = DB::table('staff_access')
                ->join('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
                ->select(
                    'staff_access.idstore_warehouse',
                    'staff_access.idstaff_access',
                    'store_warehouse.is_store',
                    'staff_access.idstaff'
                )
                ->where('staff_access.idstaff', $user->id)
                ->first();

            $orderDetail = DB::table('vendor_purchases_detail')
                ->leftJoin('vendor_purchases', 'vendor_purchases_detail.idvendor_purchases', '=', 'vendor_purchases.idvendor_purchases')
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                ->select(
                    'product_master.name AS prod_name',
                    'product_master.barcode',
                    'vendor_purchases_detail.*'
                )->where('vendor_purchases_detail.idvendor_purchases', $id)
                ->where('vendor_purchases.idstore_warehouse', $userAccess->idstore_warehouse)
                ->get();

            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $orderDetail], 200);
        } catch (Exception $e) {
            return response()->json($e->getTrace(), 403);
        }
    }
}
