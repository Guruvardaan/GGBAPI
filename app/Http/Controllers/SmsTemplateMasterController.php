<?php

namespace App\Http\Controllers;

use App\Models\smsTemplateMaster;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;

class SmsTemplateMasterController extends Controller
{
    public function getsmsTemplate()
    {
        try {
            $smsTemplate = smsTemplateMaster::where('status',1)->get();
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $smsTemplate
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch SMS template data', 'details' => $e->getMessage()], 500);
        }
    }
    public function createsmsTemplate(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'name'=>'required',
            'body'=>'required',
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
            $createRaw = smsTemplateMaster::create([
                'name' => $request->name,
                'body' => $request->body,
                'created_by'=>$request->created_by
            ]);

            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $createRaw
            ]);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create SMS template', 'details' => $e->getMessage()], 500);
        }
    }
    public function updatesmsTemplate(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'name'=>'required',
            'body'=>'required',
            'updated_by' => 'required',
            'template_id' => 'required',
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
            $template_id = $request->input('template_id');
            
            $update_charge = smsTemplateMaster::where('id',$template_id)->update([
                'name'=>$request->name,'body'=>$request->body,'updated_by'=>$request->updated_by
            ]);
            
            if($request->input('status') !=''){
                $update_charge = smsTemplateMaster::where('id',$template_id)->update([
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
    public function deletesmsTemplate(Request $request)
    {
        if ($request->has('template_id')) {
            try {
                $template_id = $request->input('template_id');
                $Details = smsTemplateMaster::where('id',$template_id)->delete();
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => 'Deleted Successfully',
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete SMS template', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide template_id'], 400);
        }
    }
}
