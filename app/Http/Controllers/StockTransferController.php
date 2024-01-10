<?php

namespace App\Http\Controllers;

use App\Models\ProductBatch;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;
use App\Models\billwiseRequest;
use App\Models\billwiseRequestDetail;
use App\Models\DirectTransferRequest;
use App\Models\DirectTransferRequestDetail;
use App\Models\AutoTransferRequest;
use App\Models\AutoTransferRequestDetail;
use Illuminate\Support\Facades\Http;

class StockTransferController extends Controller
{
    public function stockTransfer(Request $request)
    {
        try { 
            DB::beginTransaction();
            $reqestedData = json_decode($request->getContent(),true);
            
            //$user = auth()->guard('api')->user();
            foreach($reqestedData as $req){ 
                $storeWarehouseDetail = DB::table('store_warehouse')
                ->where('idstore_warehouse', $req['idstore_warehouse_from'])
                ->first();
                if($storeWarehouseDetail){
                    $DirectTransferRequest = array(
                        'idstore_warehouse_from' => $req['idstore_warehouse_from'],
                        'idstore_warehouse_to' => $req['to_warehouse_id'],
                        'dispatch_date'=>date("Y-m-d"),
                        'dispatched_by'=> 1, // replace 1 with $user->id
                        'created_by' => 1, // replace 1 with $user->id
                        'updated_by' => 1, // replace 1 with $user->id
                        'status' => 1
                    );
                    $createDirectTransfer = DirectTransferRequest::create($DirectTransferRequest);
                    
                    if($createDirectTransfer){
                        foreach ($req['products'] as $pro) {
                        
                            // $productInvDetail = DB::table('inventory')
                            //     ->where('idproduct_master', $pro['idproduct_master'])
                            //     ->where('idstore_warehouse', $req['to_warehouse_id'])
                            //     ->first();

                            $ware_productInvDetail = DB::table('inventory')
                                ->where('idproduct_master', $pro['idproduct_master'])
                                ->where('idstore_warehouse', $req['idstore_warehouse_from'])
                                ->first();
                            if($ware_productInvDetail)
                            {
                                //if ($productInvDetail) {
                                    $updatedQty=$pro['quantity'];
                                    if($ware_productInvDetail->quantity < $updatedQty){ // check if warehouse Qty lessthan threshold then only available warehose qty will transfer
                                        $updatedQty=$ware_productInvDetail->quantity;
                                    }
                                    DB::table('inventory')
                                    ->where('idproduct_master', $pro['idproduct_master'])
                                    ->where('idstore_warehouse', $req['to_warehouse_id'])
                                    ->update([
                                        'quantity' => DB::raw('quantity + ' . $updatedQty),
                                    ]);
                                    
                                    // add request details
                                    $billwiseRequestDetail = array(
                                        'iddirect_transfer_requests' => $createDirectTransfer->id,
                                        'idstore_warehouse_to' => $req['to_warehouse_id'],
                                        'idproduct_master' => $pro['idproduct_master'],
                                        'quantity'=>$ware_productInvDetail->quantity,
                                        'idproduct_batch'=>$pro['idproduct_batch'],
                                        'quantity_sent'=>$updatedQty,
                                        'quantity_received'=>$updatedQty,
                                        'created_by' => 1, // replace 1 with $user->id
                                        'updated_by' => 1, // replace 1 with $user->id
                                        'status' => 1
                                    );
                                    $createDirectTransferDetails = DirectTransferRequestDetail::create($billwiseRequestDetail);
                                    // update from qty
                                    DB::table('inventory')
                                        ->where('idproduct_master', $pro['idproduct_master'])
                                        ->where('idstore_warehouse', $req['idstore_warehouse_from'])
                                        ->update([
                                            'quantity' => DB::raw('quantity - ' . $updatedQty)
                                        ]);
                                    
                                // }else {
                                //     return response()->json(["statusCode" => 1, "message" => '', "err" => 'store product inventory does not exist'], 200);
                                // }
                            }else {
                                return response()->json(["statusCode" => 1, "message" => '', "err" => 'warehouse product inventory does not exist'], 200);
                            }
                        }
                        DB::commit();
                        return response()->json(["statusCode" => 0, "message" => "Success"], 200);
                    }else{
                        return response()->json(["statusCode" => 1, "message" => '', "err" => 'issue while creating direct transfer request'], 200);
                    }
                }else{
                    return response()->json(["statusCode" => 1, "message" => '', "err" => 'Warehouse does not exist'], 200);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["statusCode" => 1, "message" => '', "err" => $e->getMessage()], 200);
        }
    }
    public function BillWiseTransfer(Request $request)
    {
        try {
            DB::beginTransaction();
            $req = json_decode($request->getContent());
            $user = auth()->guard('api')->user();
            
            $vendorDetail = DB::table('vendor')
            ->where('idvendor', $req->idvendor)
            ->first();
            if($vendorDetail){
                $billwiseRequest = array(
                    'idvendor' => $req->idvendor,
                    'dispatch_date'=>date("Y-m-d"),
                    'dispatched_by'=> $user->id, // replace 1 with $user->id
                    'created_by' => $user->id, // replace 1 with $user->id
                    'updated_by' => $user->id, // replace 1 with $user->id
                    'status' => 1
                );
                $createBillwise = billwiseRequest::create($billwiseRequest);
                if($createBillwise){
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

                        if($vendorPurchaseDetail){
                            $updatedQty=$pro->quantity;
                            if($vendorPurchaseDetail->quantity < $updatedQty){ // check if warehouse Qty lessthan request then only available warehose qty will transfer
                                $updatedQty=$vendorPurchaseDetail->quantity;
                            }
                            if (isset($productBatchDetail->idproduct_batch) && isset($vendorPurchaseDetail->idvendor_purchases_detail)) {
                                DB::table('product_batch')
                                    ->where('idproduct_batch', $productBatchDetail->idproduct_batch)
                                    ->update([
                                        'quantity' => DB::raw('quantity + ' . $updatedQty),
                                        'selling_price' => $vendorPurchaseDetail->selling_price!=''?$vendorPurchaseDetail->selling_price:0,
                                        'purchase_price' => $vendorPurchaseDetail->unit_purchase_price!=''?$vendorPurchaseDetail->unit_purchase_price:0,
                                        'mrp' => $pro->mrp,
                                        'product'=>$vendorPurchaseDetail->product,
                                        'copartner'=>$vendorPurchaseDetail->copartner,
                                        'land'=>$vendorPurchaseDetail->land,
                                    ]);
                                    $batch_id=$productBatchDetail->idproduct_batch;
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
                                $batch_id=$pb->idproduct_batch;
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
                                        'quantity' => DB::raw('quantity + ' . $updatedQty),
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
                                    'quantity' => $updatedQty,
                                    'only_online' => 0,
                                    'only_offline' => 0,
                                    'created_by' => $user->id, // replace 1 with $user->id
                                    'updated_by' => $user->id, // replace 1 with $user->id
                                    'status' => 1
                                );
                                $inv = Inventory::create($inv);
                            }

                            // update store/warehouse
                            $updateBillwise = billwiseRequest::where('id',$createBillwise->id)
                                    ->update([
                                        'idstore_warehouse_to' => $pro->idstore_warehouse,
                                        'idstore_warehouse_from' => $vendorDetail->idstore_warehouse,
                                    ]);
                            // add request details
                            $billwiseRequestDetail = array(
                                'idbillwise_requests' => $createBillwise->id,
                                'idproduct_master' => $pro->idproduct_master,
                                'idproduct_batch' => $batch_id,
                                'quantity'=>$vendorPurchaseDetail->quantity,
                                'quantity_sent'=>$updatedQty,
                                'quantity_received'=>$updatedQty,
                                'created_by' => $user->id, // replace 1 with $user->id
                                'updated_by' => $user->id, // replace 1 with $user->id
                                'status' => 1
                            );
                            $createBillwiseDetails = billwiseRequestDetail::create($billwiseRequestDetail);
                            // update from qty
                            DB::table('inventory')
                                ->where('idproduct_master', $pro->idproduct_master)
                                ->where('idstore_warehouse', $vendorDetail->idstore_warehouse)
                                ->update([
                                    'quantity' => DB::raw('quantity - ' . $updatedQty)
                                ]);


                        }else{
                            return response()->json(["statusCode" => 1, "message" => '', "err" => 'vendor Purchase Detail does not exist'], 200);
                        }
                    }
                    DB::commit();
                }else{
                    return response()->json(["statusCode" => 1, "message" => '', "err" => 'issue while creating bill wise request'], 200);
                }
                return response()->json(["statusCode" => 0, "message" => "Success"], 200);
            }else{
                return response()->json(["statusCode" => 1, "message" => '', "err" => 'vendor does not exist'], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["statusCode" => 1, "message" => '', "err" => $e->getMessage()], 200);
        }
    }

    public function getDirectTransferRequest(Request $request){
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
            ->where('staff_access.idstaff', $user->id)
            ->first();
            if(isset($req->idstore_warehouse) && $req->idstore_warehouse!=''){
                $idstore_warehouse=$req->idstore_warehouse;
            }else{
                $idstore_warehouse=$userAccess->idstore_warehouse;
            }
        $requestData = DirectTransferRequest::where('idstore_warehouse_from', $idstore_warehouse); //replace 1 with $userAccess->idstore_warehouse

        if (isset($req->valid_from) && isset($req->valid_till)) {
            $requestData->whereBetween('created_at', [$req->valid_from, $req->valid_till]);
        }
        $requestData->orderBy('created_at', 'DESC');
        $TransferData=$requestData->get();
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $TransferData], 200);
    }
    public function getBillwiseTransferRequest(Request $request){
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
            ->where('staff_access.idstaff', $user->id)
            ->first();
        if(isset($req->idstore_warehouse) && $req->idstore_warehouse!=''){
            $idstore_warehouse=$req->idstore_warehouse;
        }else{
            $idstore_warehouse=$userAccess->idstore_warehouse;
        }
        $requestData = billwiseRequest::where('idstore_warehouse_from', $idstore_warehouse); //replace 1 with $userAccess->idstore_warehouse

        if (isset($req->valid_from) && isset($req->valid_till)) {
            $requestData->whereBetween('created_at', [$req->valid_from, $req->valid_till]);
        }
        $requestData->orderBy('created_at', 'DESC');
        $TransferData=$requestData->get();
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $TransferData], 200);
    }
    public function getAutoTransferRequest(Request $request){
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
            ->where('staff_access.idstaff', $user->id)
            ->first();
            if(isset($req->idstore_warehouse) && $req->idstore_warehouse!=''){
                $idstore_warehouse=$req->idstore_warehouse;
            }else{
                $idstore_warehouse=$userAccess->idstore_warehouse;
            }
        $requestData = AutoTransferRequest::where('idstore_warehouse_from', $idstore_warehouse); //replace 1 with $userAccess->idstore_warehouse

        if (isset($req->valid_from) && isset($req->valid_till)) {
            $requestData->whereBetween('created_at', [$req->valid_from, $req->valid_till]);
        }
        $requestData->orderBy('created_at', 'DESC');
        $TransferData=$requestData->get();
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $TransferData], 200);
    }
    public function getDirectTransferRequestDetail($id){
        
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
       if($id){
            $orderDetail = DB::table('direct_transfer_request_details')
            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'direct_transfer_request_details.idproduct_master')
            ->leftJoin('product_batch', 'product_batch.idproduct_batch', '=', 'direct_transfer_request_details.idproduct_batch')
            ->select(
                'product_master.name AS prod_name',
                'product_master.barcode',
                'direct_transfer_request_details.*',
                'product_batch.name as batch_name',
                'product_batch.mrp as batch_mrp'
            )->where('direct_transfer_request_details.iddirect_transfer_requests', $id)
            ->get();
            
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $orderDetail], 200);
        }else {
            return response()->json(["statusCode" => 1, "message" => '', "err" => 'direct transfer id is required'], 200);
        }
    }

    public function getBillwiseTransferRequestDetail($id){
        
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
       if($id){
            $orderDetail = DB::table('billwise_request_details')->leftJoin('product_master', 'product_master.idproduct_master', '=', 'billwise_request_details.idproduct_master')
            ->select(
                'product_master.name AS prod_name',
                'product_master.barcode',
                'billwise_request_details.*'
            )->where('billwise_request_details.idbillwise_requests', $id)
            ->get();
            
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $orderDetail], 200);
        }else {
            return response()->json(["statusCode" => 1, "message" => '', "err" => 'billwise transfer id is required'], 200);
        }
    }
    public function getAutoTransferRequestDetail($id){
        
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
        if($id){
            $orderDetail = DB::table('auto_transfer_request_details')->leftJoin('product_master', 'product_master.idproduct_master', '=', 'auto_transfer_request_details.idproduct_master')
            ->select(
                'product_master.name AS prod_name',
                'product_master.barcode',
                'auto_transfer_request_details.*'
            )->where('auto_transfer_request_details.idauto_transfer_requests', $id)
            ->get();
            
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $orderDetail], 200);
        }else {
            return response()->json(["statusCode" => 1, "message" => '', "err" => 'direct transfer id is required'], 200);
        }
    }
}
