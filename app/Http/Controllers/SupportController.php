<?php

namespace App\Http\Controllers;

use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class SupportController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function createIssue(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'image' => 'required|mimes:jpg,jpeg,png,bmp,tiff |max:4096',
            'title' => 'required',
            'description'=>'required',
            'category'=>'required',
            'idcustomer'=>'sometimes|required',
            'idcustomer_order'=>'sometimes|required'
        ],$messages = [
            'mimes' => 'Please insert image only',
            'max'   => 'Image should be less than 4 MB'
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

            $file=$request->file('image');
            $imagename='';
            if($file){
                $destination_path='uploads/support';
                $imagename=time().'_'.$file->getClientOriginalName();
                $file->move($destination_path,$imagename);
            }

            $create_issue = Support::create([
                'image' => $imagename,
                'title' => $request->title,
                'description'=>$request->description,
                'category'=>$request->category,
                'idcustomer'=>$request->idcustomer?$request->idcustomer:'',
                'idcustomer_order'=>$request->idcustomer_order?$request->idcustomer_order:'',
            ]);
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $create_issue
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
    }
    /**
     * Show the list of created issues.
     */
    public function getIssues(Request $request)
    {
        try {
            $issue_id = $request->input('issue_id')?$request->input('issue_id'):'';
            
            $issueDetails = Helper::getIssues($issue_id);
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $issueDetails
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
        
    }
    /**
     * Show the list of created support categories.
     */
    public function getSupportCategories()
    {
        try {
            $supportCategories = Helper::getSupportCategories();
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $supportCategories
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
        
    }
}
