<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;


use Illuminate\Support\Facades\DB;

class Helper
{
    public static function inventory_threshold($idproduct_master, $idstore_warehouse, $quantity, $idproduct_batch)
    {
        $inventory_threshold = DB::table('inventory_threshold')->where('idproduct_master', $idproduct_master)->first();
        $inventory = DB::table('inventory')->select('quantity')->where('idproduct_master', $idproduct_master)->first();
        if(!empty($inventory_threshold) && !empty($inventory)){
            if($inventory_threshold->threshold_quantity <= $inventory->quantity) {
                try{
                    $request_data = [
                        'idstore_warehouse_to' => $inventory_threshold->idstore_warehouse,
                        'idstore_warehouse_from' => $idstore_warehouse,
                        'request_type' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'status' => 1
                    ];
    
                    $idstore_request = DB::table('store_request')->insertGetId($request_data);

                    $request_detail_data = [
                        'idstore_request' => $idstore_request,
                        'idproduct_master' => $idproduct_master,
                        'idproduct_batch' => $idproduct_batch,
                        'quantity' => $inventory_threshold->sent_quantity,
                        'quantity_sent' => $inventory_threshold->sent_quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'status' => 1
                    ];
                   DB::table('store_request_detail')->insertGetId($request_detail_data);
                } catch(\Exception $e) {
                    return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
                }
            } 
        }
    }
}