<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;

class ProductsController extends Controller
{
    
    public function newlyAddedProducts(Request $request)
    {
        if ($request->has('idstore_warehouse') && $request->input('idstore_warehouse')!='') {
            try {
                $idstore_warehouse = $request->input('idstore_warehouse');
                $productQuery = Helper::prepareProductQuery();
                $inventory = $productQuery->where('inventory.idstore_warehouse', $idstore_warehouse)
                ->orderBy('product_master.idproduct_master','desc')
                ->limit(50)
                ->get();

                $idproduct_master=[];
                $collection = collect($inventory);
                $idproduct_master = $collection->pluck("idproduct_master");

                // update old 'new' record to 'gen'
                DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->where('listing_type','new')->update([
                    'listing_type' => 'gen'
                ]);

                if(!empty($idproduct_master)){
                    // update latest 'new'
                    DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->whereIn('inventory.idproduct_master', $idproduct_master)->update([
                        'listing_type' => 'new'
                    ]);
                }
                
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
                
            
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch product data', 'details' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Failed to provide warehouse store'], 400);
        }
    }

    public function frequentlyBoughtProducts(Request $request){
        $idproduct_master=[];
        if ($request->has('idstore_warehouse') && $request->input('idstore_warehouse')!='') {
            try {
                $idstore_warehouse = $request->input('idstore_warehouse');
                
                $frequentlyProducts=DB::select("SELECT c.idproduct_master, c.bought_with, count(*) as times_bought_together
                FROM (
                SELECT a.idproduct_master as idproduct_master, b.idproduct_master as bought_with
                FROM order_detail a
                INNER join order_detail b
                ON a.idorder_detail = b.idorder_detail AND a.idproduct_master != b.idproduct_master) c
                GROUP BY c.idproduct_master, c.bought_with
                ORDER BY times_bought_together DESC");

                
                    $collection = collect($frequentlyProducts);
                    $idproduct_master = $collection->pluck("idproduct_master");
                    $productQuery = Helper::prepareProductQuery();
                    $inventory = $productQuery->where('inventory.idstore_warehouse', $idstore_warehouse)
                    ->whereIn('product_master.idproduct_master', $idproduct_master)
                    ->limit(50)
                    ->get();
                    $updatedIds=[];
                    
                    $collection_Ids = collect($inventory);
                    $updatedIds = $collection_Ids->pluck("idproduct_master");

                    // update old 'frequent' record to 'gen'
                    DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->where('listing_type','frequent')->update([
                        'listing_type' => 'gen'
                    ]);

                    if(!empty($updatedIds)){
                        // update latest 'frequent'
                        DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->whereIn('inventory.idproduct_master', $updatedIds)->update([
                            'listing_type' => 'frequent'
                        ]);
                    }

                    return response()->json([
                        'statusCode' => '0',
                        'message' => 'success'
                    ]);
                
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch product data', 'details' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Failed to provide warehouse store'], 400);
        }
    }

    public function mostPopularProducts(Request $request){
        if ($request->has('idstore_warehouse') && $request->input('idstore_warehouse')!='') {
            try {
                $idstore_warehouse = $request->input('idstore_warehouse');
                $productQuery = Helper::prepareProductQuery();
                $popularProducts = $productQuery->leftJoin('order_detail','product_master.idproduct_master','=','order_detail.idproduct_master')
                ->selectRaw('product_master.*, COALESCE(sum(order_detail.quantity),0) total')
                ->where('inventory.idstore_warehouse', $idstore_warehouse)
                ->groupBy('product_master.idproduct_master')
                ->orderBy('total','desc')
                ->limit(50)
                ->get();

                $collection = collect($popularProducts);
                $idproduct_master = $collection->pluck("idproduct_master");
                // update old 'popular' record to 'gen'
                DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->where('listing_type','popular')->update([
                    'listing_type' => 'gen'
                ]);

                if(!empty($idproduct_master)){
                    // update latest 'popular'
                    DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->whereIn('inventory.idproduct_master', $idproduct_master)->update([
                        'listing_type' => 'popular'
                    ]);
                }
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
                
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch product data', 'details' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Failed to provide warehouse store'], 400);
        }

    }
    public function dealsOfTheDayProducts(Request $request)
    {
        if ($request->has('idstore_warehouse') && $request->input('idstore_warehouse')!='') {
            try {
                $idstore_warehouse = $request->input('idstore_warehouse');
                
                $inventory = DB::table("inventory")
                ->select('idproduct_master')
                ->where('idstore_warehouse',$idstore_warehouse)
                ->orderBy('discount','desc')
                ->limit(50)
                ->get();

                $idproduct_master=[];
                $collection = collect($inventory);
                $idproduct_master = $collection->pluck("idproduct_master");

                // update old 'day_deal' record to 'gen'
                DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->where('listing_type','day_deal')->update([
                    'listing_type' => 'gen'
                ]);

                if(!empty($idproduct_master)){
                    // update latest 'day_deal'
                    DB::table('inventory')->where('idstore_warehouse',$idstore_warehouse)->whereIn('inventory.idproduct_master', $idproduct_master)->update([
                        'listing_type' => 'day_deal'
                    ]);
                }
                
                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success'
                ]);
                
            
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch product data', 'details' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Failed to provide warehouse store'], 400);
        }
    }
}
