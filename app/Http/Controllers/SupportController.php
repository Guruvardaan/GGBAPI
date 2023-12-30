<?php

namespace App\Http\Controllers;

use App\Models\Support;
use App\Models\SupportDetail;
use App\Models\SupportImage;
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
        if ($request->has('support_id')) {
            $validator = \Validator::make($request->all(),[
                'image' => 'required_without:description',
                'image.*' => 'image|mimes:jpg,jpeg,png,bmp,tiff |max:4096',
                'description'=>'required_without:image',
                'idcustomer'=>'sometimes|required',
                'created_by'=>'required',
                'support_id'=>'required'
            ],$messages = [
                'mimes' => 'Please insert image only',
                'max'   => 'Image should be less than 4 MB'
            ]);
        }else{
            $validator = \Validator::make($request->all(),[
                'image' => 'required',
                'image.*' => 'image|mimes:jpg,jpeg,png,bmp,tiff |max:4096',
                'title' => 'required',
                'description'=>'required',
                'category'=>'required',
                'idcustomer'=>'required',
                'created_by'=>'required',
                'idcustomer_order'=>'sometimes|required'
            ],$messages = [
                'mimes' => 'Please insert image only',
                'max'   => 'Image should be less than 4 MB'
            ]);
        }
        
        
        if ($validator->fails()) { 
                $errors = $validator->errors();
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'All fields are required',
                    'data' => $errors->toJson()
                ]);
        }
        DB::beginTransaction();
        try {
            if(isset($request->support_id) && $request->support_id!=''){
                $issueData = Support::where('id', $request->support_id)->first();
            }else{
                $issueData = Support::create([
                    'title' => $request->title,
                    'category'=>$request->category,
                    'idcustomer'=>$request->idcustomer?$request->idcustomer:'',
                    'idcustomer_order'=>$request->idcustomer_order?$request->idcustomer_order:'',
                ]);
            }
            
            if($issueData){
                $create_issue_detail = SupportDetail::create([
                    'support_id'=>$issueData->id,
                    'description'=>$request->description?$request->description:'',
                    'created_by'=>$request->created_by
                ]);
                if($create_issue_detail){
                    $files=$request->file('image');
                    $imagename='';
                    if($files){
                        $destination_path='uploads/support';
                        foreach($files as $file){
                            $imagename=time().'_'.$file->getClientOriginalName();
                            $move=$file->move($destination_path,$imagename);
                            if($move){
                                $create_issue_image = SupportImage::create([
                                    'image' => $imagename,
                                    'support_id' =>$issueData->id,
                                    'support_detail_id'=>$create_issue_detail->id
                                ]);
                                if($create_issue_image){
                                    DB::commit();
                                }else{
                                    DB::rollback();
                                    return response()->json([
                                        'statusCode' => '1',
                                        'message' => 'Error while storing image'
                                    ]);
                                }
                                
                            }else{
                                DB::rollback();
                                return response()->json([
                                    'statusCode' => '1',
                                    'message' => 'Error while uploading image. please try again!'
                                ]);
                            }

                        }
                    }else{ // if there is no media while creating issue
                        DB::commit();
                    }
                }else{
                    DB::rollback();
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'Error while storing issue details'
                    ]);
                }
            }else{
                DB::rollback();
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'Error while creating/getting issue'
                ]);
            }
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
    }
    /**
     * Show the list of created issues.
     */
    public function getIssuesByID(Request $request)
    {
        try {
            $support_id = $request->input('support_id')?$request->input('support_id'):'';
            
            $issueDetails = Helper::getIssues($support_id);
            
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
     * Show the list of created issues.
     */
    public function getIssuesByCustomer(Request $request)
    {
        try {
            $idcustomer = $request->input('idcustomer')?$request->input('idcustomer'):'';
            
            $issueDetails = Helper::getIssuesByCustomer($idcustomer);
            
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
