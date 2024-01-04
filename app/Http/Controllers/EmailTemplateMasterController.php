<?php

namespace App\Http\Controllers;

use App\Models\emailTemplateMaster;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;

class EmailTemplateMasterController extends Controller
{
    public function getemailTemplate()
    {
        try {
            $emailTemplate = emailTemplateMaster::where('status',1)->get();
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $emailTemplate
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch email template data', 'details' => $e->getMessage()], 500);
        }
    }
    public function createemailTemplate(Request $request)
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
            $createRaw = emailTemplateMaster::create([
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
            return response()->json(['error' => 'Failed to create email template', 'details' => $e->getMessage()], 500);
        }
    }
    public function updateemailTemplate(Request $request)
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
            
            $update_charge = emailTemplateMaster::where('id',$template_id)->update([
                'name'=>$request->name,'body'=>$request->body,'updated_by'=>$request->updated_by
            ]);
            
            if($request->input('status') !=''){
                $update_charge = emailTemplateMaster::where('id',$template_id)->update([
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
    public function deleteemailTemplate(Request $request)
    {
        if ($request->has('template_id')) {
            try {
                $template_id = $request->input('template_id');
                $Details = emailTemplateMaster::where('id',$template_id)->delete();
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => 'Deleted Successfully',
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete email template', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide template_id'], 400);
        }
    }
}
