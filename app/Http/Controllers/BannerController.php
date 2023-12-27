<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getBanner(Request $request)
    {
        
        try {
            $banner_type = $request->input('banner_type')?$request->input('banner_type'):'';
            $type = $request->input('type')?$request->input('type'):'';
            $type_id = $request->input('type_id')?$request->input('type_id'):'';

            $bannerDetails = Helper::getBanners($banner_type,$type,$type_id);
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $bannerDetails
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createBanner(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'image' => 'required|mimes:jpg,jpeg,png,bmp,tiff |max:4096',
            'title' => 'required',
            'sub_title' => 'required',
            'link'=>'required',
            'banner_type'=>'required',
            'type'=>'required',
            'type_id'=>'required',
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

                $destination_path='uploads/banners';

                $imagename=time().'_'.$file->getClientOriginalName();

                $file->move($destination_path,$imagename);
            }

            $create_banner = Banner::create([
                'image' => $imagename,
                'title' => $request->title,
                'sub_title' => $request->sub_title,
                'link'=>$request->link,
                'banner_type'=>$request->banner_type,
                'type'=>$request->type,
                'type_id'=>$request->type_id,
            ]);

            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $create_banner
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBanner(Request $request, Banner $banner)
    {
        if ($request->has('banner_id')) {
            $validator = \Validator::make($request->all(),[
                'image' => 'sometimes|required|mimes:jpg,jpeg,png,bmp,tiff |max:4096',
                'title' => 'required',
                'sub_title' => 'required',
                'link'=>'required',
                'banner_type'=>'required',
                'type'=>'required',
                'type_id'=>'required',
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
                $banner_id = $request->input('banner_id');
                $bannerDetails = Banner::where('id',$banner_id)->first();

                if($bannerDetails == ''){
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'error',
                        'data' => 'invalid banner_id'
                    ]);
                }

                $file=$request->file('image');

                $oldimage=$bannerDetails->image;
                $imagename='';
                if($file !=''){
                    $destination_path='uploads/banners';
                    $imagename=time().'_'.$file->getClientOriginalName();
                    $file->move($destination_path,$imagename);
                    if($oldimage !=''){
                        unlink(public_path('/uploads/banners/'.$oldimage));
                    }
                }

                if($request->file('image') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'image' => $imagename,
                    ]);
                }
                if($request->input('title') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'title' => $request->title,
                    ]);
                }
                if($request->input('sub_title') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'sub_title' => $request->sub_title,
                    ]);
                }
                if($request->input('link') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'link'=>$request->link,
                    ]);
                }
                if($request->input('banner_type') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'banner_type'=>trim($request->banner_type),
                    ]);
                }
                if($request->input('type') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'type' => $request->type,
                    ]);
                }
                if($request->input('type_id') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'type_id'=>$request->type_id,
                    ]);
                }
                if($request->input('status') !=''){
                    $create_banner = Banner::where('id',$banner_id)->update([
                        'status'=>trim($request->status),
                    ]);
                }

                $bannerDetails = Banner::where('id',$banner_id)->get();

                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => $bannerDetails
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide banner_id'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyBanner(Request $request)
    {
        if ($request->has('banner_id')) {
            try {
                $banner_id = $request->input('banner_id');

                $bannerDetails = Banner::where('id',$banner_id)->delete();
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => 'Deleted Successfully',
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide banner_id'], 400);
        }
    }
}
