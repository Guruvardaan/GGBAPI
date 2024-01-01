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
            
                $slotsDetails = DB::table('delivery_slots')->where('date',">",date('Y-m-d'))->where('idstore_warehouse',$idstore_warehouse)->where('status',1)->orderBy('date','asc')->orderBy('slot_time_start','asc')->get();
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
                    $create_banner = DB::table('delivery_slots')->where('iddelivery_slots',$iddelivery_slots)->update([
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
}
