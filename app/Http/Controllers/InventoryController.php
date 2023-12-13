<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\StoreRequest;
use App\Models\StoreRequestDetails;
use App\Models\Inventory;
use App\Models\Vendor;
use App\Models\VendorPurchasesDetail;
use App\Models\VendorPurchases;
use App\Models\product_batch;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function StoreInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_warehouse_id' => 'required|integer',
            'to_warehouse_id' => 'required|integer',
            'request_type' => 'required|integer',
            'products' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

          try {
            DB::beginTransaction();
            
            $currentUserId = isset($request->user_id) ? $request->user_id : null;
            $warehouseToId = isset($request->to_warehouse_id) ? $request->to_warehouse_id : null;
            $warehouseFromId = isset($request->current_warehouse_id) ? $request->current_warehouse_id : null;
            $requestType = isset($request->current_warehouse_id) ? $request->current_warehouse_id : null;
            $products = isset($request->products) ? $request->products : null;
            foreach ($products as $productDetails) {

                $checkStoreRequest = StoreRequest::where('idstore_warehouse_to',$warehouseToId)
                                    ->where('idstore_warehouse_from',$warehouseFromId)->first();
                if($checkStoreRequest){
                    $storeRequestDetailId =StoreRequestDetails::where('idstore_request',$checkStoreRequest->idstore_request)
                                    ->where('idproduct_master',$productDetails['idproduct_master'])
                                    ->where('idproduct_batch',$productDetails['idproduct_batch'])
                                    ->first();

                    if($storeRequestDetailId === null){
                        $storeRequestDetailId = DB::table('store_request_detail')->insertGetId([
                            'idstore_request' => $checkStoreRequest->idstore_request,
                            'idproduct_master' => $productDetails['idproduct_master'],  
                            'idproduct_batch' => $productDetails['idproduct_master'],  
                            'quantity' => $productDetails['quantity'],
                            'quantity_sent' => $productDetails['quantity'],
                            'quantity_received' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $currentUserId,  
                            'updated_by' => $currentUserId,  
                            'status' => 1,
                        ]);
                    }
                    $storeRequestId = $checkStoreRequest->idstore_request;
                }else{
                    $storeRequestId = DB::table('store_request')->insertGetId([
                        'idstore_warehouse_to' => $warehouseToId,
                        'idstore_warehouse_from' => $warehouseFromId,
                        'request_type' => $requestType,
                        'old_idstore_request' => null, 
                        'dispatch_date' => null,  
                        'dispatched_by' => null,  
                        'dispatch_detail' => null,  
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => $currentUserId,  
                        'updated_by' => $currentUserId,  
                        'status' => 3,
                    ]);

                    $storeRequestDetailId = DB::table('store_request_detail')->insertGetId([
                        'idstore_request' => $storeRequestId,
                        'idproduct_master' => $productDetails['idproduct_master'],  
                        'idproduct_batch' => $productDetails['idproduct_master'],  
                        'quantity' => $productDetails['quantity'],
                        'quantity_sent' => $productDetails['quantity'],
                        'quantity_received' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => $currentUserId,  
                        'updated_by' => $currentUserId,  
                        'status' => 1,
                    ]);
                }

                $CheckInventoryFromId = Inventory::where('idstore_warehouse',$warehouseFromId)
                                    ->where('idproduct_master',$productDetails['idproduct_master'])->first();

                $CheckInventoryToId = Inventory::where('idstore_warehouse',$warehouseToId)
                                    ->where('idproduct_master',$productDetails['idproduct_master'])->first();

                $productBatch = product_batch::where('idproduct_batch',$productDetails['idproduct_batch'])->first();

                if($CheckInventoryFromId === null){
                    $sentQuantity =  0;
                    $inventoryId[] = DB::table('inventory')->insertGetId([
                        'idstore_warehouse' => $warehouseFromId,    
                        'idproduct_master' => $productBatch->idproduct_master, 
                        'selling_price' => $productBatch->selling_price,    
                        'purchase_price' => $productBatch->purchase_price, 
                        'mrp' => $productBatch->mrp, 
                        'discount' => $productBatch->discount,    
                        'instant_discount_percent' => 0,    
                        'quantity' => $sentQuantity, 
                        'product' => $productBatch->product, 
                        'copartner' => $productBatch->copartner, 
                        'land' => $productBatch->land, 
                        'only_online' => $productDetails['only_online'],    
                        'only_offline' => $productDetails['only_offline'],    
                        'listing_type' => $productDetails['listing_type'],    
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => $currentUserId, 
                        'updated_by' => $currentUserId, 
                        'status' => 1,    
                    ]);
                    
                }else{
                    if($CheckInventoryFromId->quantity >= $productDetails['quantity']){
                        $sentQuantity =  $CheckInventoryFromId->quantity - $productDetails['quantity'];
                    }else{
                        $sentQuantity =  0;
                    }

                    $inventoryId = Inventory::where('idinventory', $CheckInventoryFromId->idinventory) // Assuming you have the $inventoryId from the previous insert
                        ->update([
                            'quantity' => $sentQuantity,
                        ]);
                }     
                if($CheckInventoryFromId != null){
                    if($sentQuantity == 0){
                        $recivedQuantity = $CheckInventoryFromId->quantity + $CheckInventoryToId->quantity;
                    }else{
                        $recivedQuantity = $productDetails['quantity'] + $CheckInventoryToId->quantity;
                    }
                }else{
                    $recivedQuantity = 0;
                }

                if($CheckInventoryToId === null ){
                    $inventoryId[] = DB::table('inventory')->insertGetId([
                        'idstore_warehouse' => $warehouseToId,    
                        'idproduct_master' => $productBatch->idproduct_master, 
                        'selling_price' => $productBatch->selling_price,    
                        'purchase_price' => $productBatch->purchase_price, 
                        'mrp' => $productBatch->mrp, 
                        'discount' => $productBatch->discount,    
                        'instant_discount_percent' => 0,    
                        'quantity' => $recivedQuantity, 
                        'product' => $productBatch->product, 
                        'copartner' => $productBatch->copartner, 
                        'land' => $productBatch->land, 
                        'only_online' => $productDetails['only_online'],    
                        'only_offline' => $productDetails['only_offline'],    
                        'listing_type' => $productDetails['listing_type'],    
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => $currentUserId, 
                        'updated_by' => $currentUserId, 
                        'status' => 1,    
                    ]);
                }else{
                    if($CheckInventoryFromId != null){
                        $inventoryId = Inventory::where('idinventory', $CheckInventoryToId->idinventory) // Assuming you have the $inventoryId from the previous insert
                            ->update([
                                'quantity' => $recivedQuantity,
                            ]);
                    }
                    
                }          
            }
               
            DB::commit();
            return response()->json(['message' => 'Data successfully inserted'], 200);

          } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => 'Could not connect to the database', 'details' => $e->getMessage()], 500);
          }
    }

    public function TransferInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_purchases' => 'required|array',
            'vendor_purchases_details' => 'required|array',
            'user_id' => 'required|integer',
            'idvendor' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $vendorPurchases = $request->vendor_purchases[0] ?? null;
            $vendorPurchasesDetails = $request->vendor_purchases_details ?? null;
            $vendorID = $request->idvendor ?? null;
            $user_id = $request->user_id ?? null;
            $j = 0;
            if ($vendorPurchasesDetails) {
                foreach ($vendorID as $idVendor) {
                    $vendorPurchaseId = DB::table('vendor_purchases')->insertGetId([
                        'idvendor' => $idVendor,
                        'idstore_warehouse' => $vendorPurchases['idstore_warehouse'] ?? null,
                        'bill_number' => $vendorPurchases['bill_number'] ?? null,
                        'total' => $vendorPurchases['total'] ?? null,
                        'sgst' => $vendorPurchases['sgst'] ?? null,
                        'cgst' => $vendorPurchases['cgst'] ?? null,
                        'items' => $vendorPurchases['items'] ?? null,
                        'quantity' => $vendorPurchases['quantity'] ?? null,
                        'paid' => $vendorPurchases['paid'] ?? null,
                        'balance' => $vendorPurchases['balance'] ?? null,
                        'bill_added' => $vendorPurchases['idvendor_purchases'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => $user_id,
                        'updated_by' => $user_id,
                        'status' => 1,
                    ]);
                    foreach ($vendorPurchasesDetails as $VendorPurDet) {
                        
                        $inventoryQuantity = Inventory::where('idstore_warehouse', $vendorPurchases['idstore_warehouse'])
                                            ->where('idproduct_master', $VendorPurDet['idproduct_master'])
                                            ->first();

                        $quantityPerVendor = floor($inventoryQuantity->quantity / count($vendorID));
                        $remainingQuantity = $inventoryQuantity->quantity % count($vendorID);
                        $quantitys = $quantityPerVendor;

                        if ($quantityPerVendor <= $VendorPurDet['quantity']) {
                            $quantitys = $quantitys;
                            $quantityArray[$vendorPurchases['idstore_warehouse']][$VendorPurDet['idproduct_master']][] = $quantitys;
                            $remainingQuantity -= 1;
                        }else{
                            $quantitys = $VendorPurDet['quantity'];
                            $quantityArray[$vendorPurchases['idstore_warehouse']][$VendorPurDet['idproduct_master']][] = $VendorPurDet['quantity'];
                        }
                        $quntityRemaining = $inventoryQuantity->quantity - $quantitys;
                        
                        DB::table('vendor_purchases_detail')->insert([
                            'idvendor_purchases' => $vendorPurchaseId,
                            'idproduct_master' => $VendorPurDet['idproduct_master'],
                            'mrp' => $VendorPurDet['mrp'],
                            'product' => $VendorPurDet['product'],
                            'copartner' => $VendorPurDet['copartner'],
                            'land' => $VendorPurDet['land'],
                            'selling_price' => $VendorPurDet['selling_price'],
                            'hsn' => $VendorPurDet['hsn'],
                            'quantity' => $quantitys,
                            'unit_purchase_price' => $VendorPurDet['unit_purchase_price'],
                            'free_quantity' => $VendorPurDet['free_quantity'],
                            'expiry' => $VendorPurDet['expiry'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $user_id,
                            'updated_by' => $user_id,
                            'status' => 1,
                        ]);
                       
                    }
                }
                $warehouseProductQuantities = [];

                foreach ($quantityArray as $warehouseId => $products) {
                    foreach ($products as $productId => $quantities) {
                        $inventoryQuantity = Inventory::where('idstore_warehouse', $warehouseId)
                            ->where('idproduct_master', $productId)
                            ->first();

                        $totalInventoryQuantity = $inventoryQuantity->quantity;
                        $totalQuantity = array_sum($quantities);
                        $difference = $totalInventoryQuantity - $totalQuantity;
                        if ($difference > 0) {
                            Inventory::where('idstore_warehouse', $warehouseId)
                                ->where('idproduct_master', $productId)
                                ->update([
                                    'quantity' => $difference,
                                ]);
                        }
                    }
                }                
                
                DB::commit();
                return response()->json(['message' => 'Data successfully inserted'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Could not connect to the database', 'details' => $e->getMessage()], 500);
        }
    }
    public function conformTransferInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_purchases' => 'required|array',
            'vendor_purchases_details' => 'required|array',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();

        try {
            $vendorPurchases = $request->vendor_purchases[0] ?? null;
            $vendorPurchasesDetails = $request->vendor_purchases_details ?? null;
            $user_id = $request->user_id ?? null;
            if ($vendorPurchasesDetails) {
                    $vendorPurchaseId = DB::table('vendor_purchases')
                                            ->where("idvendor_purchases", $vendorPurchases['idvendor_purchases'])
                                            ->update([
                                                'idvendor' => $vendorPurchases['idvendor'],
                                                'idstore_warehouse' => $vendorPurchases['idstore_warehouse'] ?? null,
                                                'bill_number' => $vendorPurchases['bill_number'] ?? null,
                                                'total' => $vendorPurchases['total'] ?? null,
                                                'sgst' => $vendorPurchases['sgst'] ?? null,
                                                'cgst' => $vendorPurchases['cgst'] ?? null,
                                                'items' => $vendorPurchases['items'] ?? null,
                                                'quantity' => $vendorPurchases['quantity'] ?? null,
                                                'paid' => $vendorPurchases['paid'] ?? null,
                                                'balance' => $vendorPurchases['balance'] ?? null,
                                                'bill_added' => 0,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                                'created_by' => $user_id,
                                                'updated_by' => $user_id,
                                                'status' => 1,
                                            ]);

                    foreach ($vendorPurchasesDetails as $VendorPurDet) {
                        
                        DB::table('vendor_purchases_detail')->where("idvendor_purchases_detail", $VendorPurDet['idvendor_purchases_detail'])->update([
                            'idvendor_purchases' => $vendorPurchaseId,
                            'idproduct_master' => $VendorPurDet['idproduct_master'],
                            'mrp' => $VendorPurDet['mrp'],
                            'product' => $VendorPurDet['product'],
                            'copartner' => $VendorPurDet['copartner'],
                            'land' => $VendorPurDet['land'],
                            'selling_price' => $VendorPurDet['selling_price'],
                            'hsn' => $VendorPurDet['hsn'],
                            'quantity' => $VendorPurDet['quantity'],
                            'unit_purchase_price' => $VendorPurDet['unit_purchase_price'],
                            'free_quantity' => $VendorPurDet['free_quantity'],
                            'expiry' => $VendorPurDet['expiry'],
                            'updated_at' => now(),
                            'updated_by' => $user_id,
                            'status' => 1,
                        ]);

                        $CheckInventoryToId = Inventory::where('idstore_warehouse',$vendorPurchases['idstore_warehouse'])
                                    ->where('idproduct_master',$VendorPurDet['idproduct_master'])->first();

                        if($CheckInventoryToId === null ){
                            $inventoryId[] = DB::table('inventory')->insertGetId([
                                'idstore_warehouse' => $vendorPurchases['idstore_warehouse'],    
                                'idproduct_master' => $VendorPurDet['idproduct_master'], 
                                'selling_price' => $VendorPurDet['selling_price'],    
                                'purchase_price' => $VendorPurDet['unit_purchase_price'], 
                                'mrp' => $VendorPurDet['mrp'], 
                                'discount' => 0,    
                                'instant_discount_percent' => 0,    
                                'quantity' => $VendorPurDet['quantity'], 
                                'product' => $VendorPurDet['product'], 
                                'copartner' => $VendorPurDet['copartner'], 
                                'land' => $VendorPurDet['land'], 
                                'only_online' => 0,    
                                'only_offline' => 0,    
                                'listing_type' => "gen",    
                                'created_at' => now(),
                                'updated_at' => now(),
                                'created_by' => $user_id, 
                                'updated_by' => $user_id, 
                                'status' => 1,    
                            ]);
                        }else{
                            $recivedQuantity = $VendorPurDet['quantity'] + $CheckInventoryToId->quantity;
                            $inventoryId = Inventory::where('idinventory', $CheckInventoryToId->idinventory) // Assuming you have the $inventoryId from the previous insert
                                ->update([
                                    'quantity' => $recivedQuantity,
                                ]);
                            
                        }   
                    }
                DB::commit();
                return response()->json(['message' => 'Data successfully inserted'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Could not connect to the database', 'details' => $e->getMessage()], 500);
        }
    }

}