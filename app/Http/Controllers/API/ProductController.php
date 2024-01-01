<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductMaster;
use Illuminate\Support\Facades\DB;
use  App\Helpers\Helper;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        try {
            $req = json_decode($request->getContent());
            $user = auth()->guard('api')->user();
            
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

    public function findByBarcode($barcode, $storeId = 0, $exact = 0)
    {
        $idStore = $storeId;
        try {
            $user = auth()->guard('api')->user();
            if ($user->user_type === 'A') {
                if ($storeId == 0) {
                    throw new Exception("invalid store access");
                }
                $idStore = $storeId;
            } else {
                $userAccess = DB::table('staff_access')
                    ->leftJoin('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
                    ->select(
                        'staff_access.idstore_warehouse',
                        'staff_access.idstaff_access',
                        'store_warehouse.is_store',
                        'staff_access.idstaff'
                    )
                    ->where('staff_access.idstaff', $user->id)
                    ->first();
                $idStore = $userAccess->idstore_warehouse;
            }


            $productmaster = DB::table('product_master')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->select(
                    'product_master.idbrand',
                    'brands.name AS brand',
                    'product_master.idproduct_master',
                    'product_master.idcategory',
                    'category.name AS category',
                    'product_master.idsub_category',
                    'sub_category.name AS scategory',
                    'product_master.idsub_sub_category',
                    'sub_sub_category.name AS sscategory',
                    'product_master.name AS prod_name',
                    'product_master.description',
                    'product_master.barcode',
                     'product_master.hsn',
                    'product_master.sgst',
                    'product_master.cgst',
                    'product_master.igst',
                    'product_master.status',
                    'inventory.quantity',
                    'inventory.idinventory',
                    'inventory.selling_price',
                     'inventory.purchase_price',
                    'inventory.mrp',
                    'inventory.product',
                    'inventory.copartner',
                    'inventory.land',
                    'inventory.discount',
                    'inventory.instant_discount_percent',
                    'inventory.listing_type',
                    'inventory.listing_type AS origListType'
                );
                
            if ($exact == 1) {
                $productmaster->where('product_master.barcode', $barcode);
            } else {
                $productmaster->where(function ($query) use ($barcode) {
                    return $query
                        ->where('product_master.barcode', 'like', $barcode . '%')
                        ->orWhere('product_master.name', 'like', $barcode . '%');
                });
            }

            $get_product = $productmaster->get();
            
            $res = $productmaster->where('inventory.idstore_warehouse', $idStore)
                ->limit(40)
                ->get();
            
            // data not found in invenntory.    
            $type = 0;    
            if(empty($res->toArray())) {
               $type = 1;
            }    
            
            $allProds = Helper::getBatchesAndMemberPrices($res, $idStore);
          
            return response()->json(["statusCode" => 0, "message" => $exact, 'type' => $type,  "data" => $allProds], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }

    public function add_inventory_and_batch(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'type' => 'required|integer',
            'selling_price' => 'required',
            'mrp' => 'required',
            'idproduct_master' => 'required|integer',
            'idstore_warehouse' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $product = DB::table('product_master')->where('idproduct_master', $request->idproduct_master)->first();
            if(!empty($product)) {
                $product_batch  = DB::table('product_batch')->where('idstore_warehouse', $request->idstore_warehouse)
                                    ->where('idproduct_master', $request->idproduct_master)
                                    ->where('status', 1)
                                    ->get();

                if(!empty($product_batch->toArray())) {
                    $product = $product_batch->last();
                } else {
                    $product_batch_data = [
                        'idstore_warehouse' => $request->idstore_warehouse,
                        'idproduct_master' => $request->idproduct_master,
                        'name' => 'ADD',
                        'selling_price' => $request->selling_price,
                        'mrp' => $request->mrp,
                        'quantity' => 10,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 6,
                    ];
                    $id = DB::table('product_batch')->insertGetId($product_batch_data);
                    $product = $this->get_product_batch($id);
                }              

                $inventory_data = [
                    'idstore_warehouse' => $request->idstore_warehouse,
                    'idproduct_master' => $request->idproduct_master,
                    'selling_price' => $product->selling_price,
                    'mrp' => $request->mrp,
                    'quantity' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 6,
                ];
                $invetory_id = DB::table('inventory')->insertGetId($inventory_data);
                return response()->json(["statusCode" => 1, 'message' => 'Data added sucessfully'], 200);
            }
            return response()->json(["statusCode" => 1, 'message' => 'product not found'], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }    
    }

    public function get_product_batch($id) 
    {
        $data = DB::table('product_batch')->where('idproduct_batch', $id)->where('status', 1)->get();
        return $data->last();        
    }

}
