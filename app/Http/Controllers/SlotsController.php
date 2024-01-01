<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class SlotsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getSlots(Request $request)
    {
        if ($request->has('idstore_warehouse')) {
            try {
                $idstore_warehouse = $request->input('idstore_warehouse');
            
                $slotsDetails = DB::table('delivery_slots')->where('idstore_warehouse',$idstore_warehouse)->where('status',1)->orderBy('date','asc')->orderBy('slot_time_start','asc')->get();
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => $slotsDetails
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch slots data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide idstore_warehouse'], 400);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createSlots(Request $request)
    { 
        $validator = \Validator::make($request->all(),[
            'idstore_warehouse'=>'required',
            'date'=>'required',
            'is_servicable'=>'required',
            'slot_time_start' => 'required',
            'slot_time_end' => 'required',
            'max_orders'=>'required',
            'available_slots'=>'required',
            'created_by'=>'required'
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

            $create_slot = DB::table('delivery_slots')->insert([
                'slot_time_start' => $request->slot_time_start,
                'slot_time_end' => $request->slot_time_end,
                'date' => date("Y-m-d",strtotime($request->date)),
                'available_slots'=>$request->available_slots,
                'max_orders'=>$request->max_orders,
                'idstore_warehouse'=>$request->idstore_warehouse,
                'is_servicable'=>$request->is_servicable,
                'created_by'=>$request->created_by,
                'status'=>1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $create_slot
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch slots data', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateSlots(Request $request)
    {
        if ($request->has('iddelivery_slots')) {
            $validator = \Validator::make($request->all(),[
                'idstore_warehouse'=>'required',
                'date'=>'required',
                'is_servicable'=>'required',
                'slot_time_start' => 'required',
                'slot_time_end' => 'required',
                'max_orders'=>'required',
                'available_slots'=>'required',
                'updated_by'=>'required',
                'iddelivery_slots'=>'required'
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
                $iddelivery_slots = $request->input('iddelivery_slots');
                $slotsDetails = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->first();
                if($slotsDetails == ''){
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'error',
                        'data' => 'invalid iddelivery_slots'
                    ]);
                }
                
                $update_slot = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->update([
                    'slot_time_start' => $request->slot_time_start,'slot_time_end' => $request->slot_time_end,'date' => date("Y-m-d",strtotime($request->date)),'max_orders' => $request->max_orders,'available_slots' => $request->available_slots,'idstore_warehouse' => $request->idstore_warehouse,'is_servicable' => $request->is_servicable,'updated_by' => $request->updated_by,'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if($request->input('status') !=''){
                    $updateslot = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->update([
                        'status'=>trim($request->status)
                    ]);
                }
                
                $slotsDetails = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->get();

                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => $slotsDetails
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch slots data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide iddelivery_slots'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroySlot(Request $request)
    {
        if ($request->has('iddelivery_slots')) {
            try {
                $iddelivery_slots = $request->input('iddelivery_slots');
                $slotsDetails = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->delete();
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => 'Deleted Successfully',
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete slots data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide iddelivery_slots'], 400);
        }
    }

    public function createBulkSlots(Request $request){
        $validator = \Validator::make($request->all(),[
            'slots' => 'required',
            'created_by'=>'required'
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
            if($request->slots){
                foreach($request->slots as $s){
                    $storeDetails=DB::table('store_warehouse')->select('idstore_warehouse')->where('is_store',1)->whereAnd('status',1)->get();
                    $collection = collect($storeDetails);
                    $idstore_warehouse = $collection->pluck("idstore_warehouse");
                    foreach($idstore_warehouse as $storeid){
                        for($i=0;$i<15;$i++){ // for next 15 days
                            $date = date("Y-m-d", strtotime("+ ".$i." day") ); // date
                            $slotsCount = DB::table('delivery_slots')
                            ->whereBetween('slot_time_start',[$s["slot_time_start"],$s["slot_time_end"]])
                            ->where('idstore_warehouse',$storeid)
                            ->where('date',$date)
                            ->where('status',1)
                            ->count();
                            //echo $slotsCount."=";
                            if($slotsCount<=0){ // if slot is not exist then create new
                                $create_slot = DB::table('delivery_slots')->insert([
                                    'slot_time_start' => $s["slot_time_start"],
                                    'slot_time_end' => $s["slot_time_end"],
                                    'date' => $date,
                                    'available_slots'=>100,
                                    'max_orders'=>100,
                                    'idstore_warehouse'=>$storeid,
                                    'is_servicable'=>1,
                                    'created_by'=>$request->created_by,
                                    'status'=>1,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
            }

            return response()->json([
                'statusCode' => '1',
                'message' => 'slots required'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch slots data', 'details' => $e->getMessage()], 500);
        }
    }
    public function updateSlotStatus(Request $request){
        $validator = \Validator::make($request->all(),[
            'status' => 'required',
            'date' => 'required',
            'idstore_warehouse'=>'sometimes|required',
            'updated_by'=>'required'
        ]);        
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json([
                'statusCode' => '1',
                'message' => 'All fields are required',
                'data' => $errors->toJson()
            ]);
        }
        if($request->input('status') !=''){
            try{

                if($request->input('idstore_warehouse') !=''){
                    $updateSlot = DB::table('delivery_slots')->where('idstore_warehouse',$request->idstore_warehouse)->where('date',$request->date)->update([
                        'status'=>trim($request->status),'updated_by'=>trim($request->updated_by),'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }else{
                    $updateSlot = DB::table('delivery_slots')->where('date',$request->date)->update([
                        'status'=>trim($request->status),'updated_by'=>trim($request->updated_by),'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
            }
            catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch slots data', 'details' => $e->getMessage()], 500);
            }
        }
        return response()->json([
            'statusCode' => '1',
            'message' => 'status required'
        ]);
    }
}
