<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Input;

class BrandsUpdateController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function uploadProductExcel(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'file' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json([
                'statusCode' => '1',
                'message' => 'All fields are required',
                'data' => $errors->toJson()
            ]);
        }
        DB::beginTransaction();
        $ExcelData=[];
        $failedData=[];
        try {
            $ExcelData=Excel::toArray($ExcelData,$request->file('file'));
            unset($ExcelData[0][0]); // unset for column head
            $ExcelRowData=$ExcelData[0];
            foreach($ExcelRowData as $row){
                if($row[5]!=''){
                    $brands = DB::table('brands')->where('name', '=', $row[5])->first();
                    if ($brands === null) { // insert new brand if not exist
                        $brandID=DB::table('brands')->insertGetId(
                            array(
                                'status'     =>   1, 
                                'name'   =>   $row[5],
                                'created_by' =>'-1'
                            )
                    );
                    if($brandID){ // update brand logo
                        DB::table('brands')->where('idbrand',$brandID)->update(['logo' => $brandID.'.png']);
                    }
                    }else{  // already exist brand ID
                        $brandID=$brands->idbrand;
                    }
                    // product master update brand according to barcode
                    if($brandID){
                        $is_update=DB::table('product_master')->where('barcode',$row[1])->update([
                            'idbrand' => $brandID
                        ]);
                        DB::commit();
                    }else{
                        $failedData[]=array(['barcode'=>$row[1],'brand'=>$row[5]]);
                    }
                }else{
                    $failedData[]=array(['barcode'=>$row[1],'brand'=>$row[5]]);
                }
            }
            if(empty($failedData)){
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
            }else{
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'success',
                    'data'=>$failedData
                ]);
            }
            
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
    }
}
