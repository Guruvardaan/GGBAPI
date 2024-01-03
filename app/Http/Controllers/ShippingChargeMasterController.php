<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShippingChargeMaster;

use DB;
use Helper;

class ShippingChargeMasterController extends Controller
{
    public function getShippingCharge()
    {
        // echo Helper::getShippingCharge(999); exit; // function for get shippning rate by order amount
        try {
            $shippingDetails = ShippingChargeMaster::where('status',1)->get();
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $shippingDetails
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch shipping data', 'details' => $e->getMessage()], 500);
        }
    }
    public function createShippingCharge(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'shipping_charge'=>'required',
            'order_amount'=>'required',
            'title'=>'sometimes|required',
            'created_by' => 'required'
        ]);
        
        if ($validator->fails()) { 
                $errors = $validator->errors();
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'All fields are required',
                    'data' => $errors->toJson()
                ]);
        }
        try{
            $create_charge = ShippingChargeMaster::create([
                'shipping_charge' => $request->shipping_charge,
                'title' => $request->title?$request->title:'',
                'order_amount' => $request->order_amount,
                'created_by'=>$request->created_by
            ]);

            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $create_charge
            ]);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create shipping data', 'details' => $e->getMessage()], 500);
        }
    }
    public function updateShippingCharge(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'shipping_charge'=>'required',
            'order_amount'=>'required',
            'title'=>'sometimes|required',
            'updated_by' => 'required',
            'shipping_id' => 'required',
            'status'=>'sometimes|required',
        ]);
        
        if ($validator->fails()) { 
                $errors = $validator->errors();
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'All fields are required',
                    'data' => $errors->toJson()
                ]);
        }
        try {
            $shipping_id = $request->input('shipping_id');
            
            $update_charge = ShippingChargeMaster::where('id',$shipping_id)->update([
                'title' => $request->title?$request->title:'','shipping_charge'=>$request->shipping_charge,'order_amount'=>$request->order_amount,'updated_by'=>$request->updated_by
            ]);
            
            if($request->input('status') !=''){
                $update_charge = ShippingChargeMaster::where('id',$shipping_id)->update([
                    'status'=>$request->status
                ]);
            }
            return response()->json([
                'statusCode' => '0',
                'message' => 'success'
            ]);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update shipping data', 'details' => $e->getMessage()], 500);
        }
    }
    public function deleteShippingCharge(Request $request)
    {
        if ($request->has('shipping_id')) {
            try {
                $shipping_id = $request->input('shipping_id');
                $Details = ShippingChargeMaster::where('id',$shipping_id)->delete();
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => 'Deleted Successfully',
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete shipping charge', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide shipping_id'], 400);
        }
    }
}
