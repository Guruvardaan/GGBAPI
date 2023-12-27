<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use  App\Helpers\Helper;

class InventoryThresholdController extends Controller
{
    public function index()
    {
        $data =  DB::table('inventory_threshold')->select('*')->get();
        return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Geted Sucessfully.", "data" => $data], 200);
    }

    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'idproduct_master' => 'required|integer',
                'idstore_warehouse' => 'required|integer',
                'threshold_quantity' => 'required|integer',
                'sent_quantity' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } 
            
            $data = [
                'idproduct_master' => $request->idproduct_master,
                'idstore_warehouse' =>  $request->idstore_warehouse,
                'threshold_quantity' =>  $request->threshold_quantity,
                'sent_quantity' =>  $request->sent_quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ];
    
            $id = DB::table('inventory_threshold')->insertGetId($data);
            $createdData = [];
            if(!empty($id)) {
                $createdData = DB::table('inventory_threshold')->find($id);
            }
            
            return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Added Sucessfully.", "data" => $createdData], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $data = [];
        if(!empty($id)) {
            $data = DB::table('inventory_threshold')->find($id);
        }

        return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Geted Sucessfully.", "data" => $data], 200);
    }

    public function update(Request $request, string $id)
    {
        try{
            $validator = Validator::make($request->all(), [
                'idproduct_master' => 'required|integer',
                'idstore_warehouse' => 'required|integer',
                'threshold_quantity' => 'required|integer',
                'sent_quantity' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } 
            
            $data = [
                'idproduct_master' => $request->idproduct_master,
                'idstore_warehouse' =>  $request->idstore_warehouse,
                'threshold_quantity' =>  $request->threshold_quantity,
                'sent_quantity' =>  $request->sent_quantity,
                'updated_at' => now(),
            ];
    
            $update = DB::table('inventory_threshold')->where('id', $id)->update($data);
            if(empty($update)) {
                return response()->json(["statusCode" => 0, "message" => "Record Not Found"], 200);
            }

            $updatedData = [];
            if(!empty($id)) {
                $updatedData = DB::table('inventory_threshold')->find($id);
            }
            
            return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Updated Sucessfully.", "data" => $updatedData], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
             $delete = DB::table('inventory_threshold')->delete($id);
             if(empty($update)) {
                return response()->json(["statusCode" => 0, "message" => "Record Not Found"], 200);
            }
             return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Deleted Sucessfully."], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }     
    }

    public function get_inventory_threshold_products()
    {
        $idstore_warehouse = !empty($_GET['idstore_warehouse']) ? $_GET['idstore_warehouse'] : null;

        $threshold_data = DB::table('inventory_threshold')
                                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'inventory_threshold.idproduct_master')
                                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory_threshold.idproduct_master')
                                ->select('inventory.idstore_warehouse', 'inventory_threshold.idproduct_master','product_master.name', 'inventory_threshold.threshold_quantity', DB::raw('sum(inventory.quantity) as quantity'))
                                ->groupBy('inventory.idstore_warehouse', 'inventory_threshold.idproduct_master','product_master.name', 'inventory_threshold.threshold_quantity');
       
        if(!empty($idstore_warehouse)) {
            $threshold_data->where('inventory.idstore_warehouse', $idstore_warehouse);
        }

       $inventory_threshold = $threshold_data->get();                         
       
       $data = [];                  
       foreach($inventory_threshold as $product) {
         if(!empty($product->quantity) && !empty($product->threshold_quantity)) {
            if($product->quantity <= $product->threshold_quantity) {
                $data[] = $product;
            }
         }
       }               
       $data = $this->data_formatting($data);
       return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data], 200);      
    }

    public function data_formatting($data)
    {
        $transformedData = [];

        foreach ($data as $item) {
            $idstore_warehouse = $item->idstore_warehouse;
            $warehouse_name = $this->get_warehouse_name($idstore_warehouse);
            
            $key = "{$item->idstore_warehouse}";
            if (!isset($transformedData[$key])) {
                $transformedData[$key] = [
                    'idstore_warehouse' => $idstore_warehouse,
                    'warehouse_name' => $warehouse_name,
                    'products' => [],
                ];
            }

            $transformedData[$key]['products'][] = [
                'idproduct_master' => $item->idproduct_master,
                'product_name' => $item->name,
                'threshold_quantity' => $item->threshold_quantity,
                'quantity' => $item->quantity,
            ];
        }

        $transformedData = array_values($transformedData);

        return $transformedData;
    }

    public function get_warehouse_name($id)
    {
        $warehouse = DB::table('store_warehouse')->where('idstore_warehouse', $id)->first();
        return !empty($warehouse) ? $warehouse->name : ''; 
    }

}
