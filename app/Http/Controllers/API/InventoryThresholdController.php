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
}
