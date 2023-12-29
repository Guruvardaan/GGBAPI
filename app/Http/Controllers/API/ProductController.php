<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductMaster;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        try {
            $req = json_decode($request->getContent());
            // $user = auth()->guard('api')->user();
            // dd(!empty($req->manufacturer) ? $req->manufacturer : null);
            if (ProductMaster::where('barcode', $req->barcode)->exists()) {
                return response()->json(["statusCode" => 1, "message" => "Barcode already exists."], 200);
            }

            $r = [
                'name' =>  $req->name,
                'barcode' =>  $req->barcode,
                'sgst' =>  $req->sgst,
                'cgst' =>  $req->cgst,
                'description' =>  $req->description,
                'idbrand' =>  $req->idbrand,
                'idcategory' =>  $req->idcategory,
                'idsub_category' =>  $req->idsub_category,
                'idsub_sub_category' =>  1341,
                'manufacturer' => !empty($req->manufacturer) ? $req->manufacturer : null,
                'shelf_life'=> !empty($req->shelf_life) ? $req->shelf_life : null,
                'unit' => !empty($req->unit) ? $req->unit : null,
                'packaging_type' => !empty($req->packaging_type) ? $req->packaging_type : null,
                'ingredients'=> !empty($req->ingredients) ? $req->ingredients : null,
                'status' =>  $req->status
            ];
            ProductMaster::create($r);
            return response()->json(["statusCode" => 0, "message" => "Success"], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }
}
