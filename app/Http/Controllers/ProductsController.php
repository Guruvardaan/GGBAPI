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
                ->limit(10)
                ->get();

                
                return response()->json([
                    'statusCode' => '0',
                    'message' => 'success',
                    'data' => $inventory
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

                if($frequentlyProducts){ 
                    foreach($frequentlyProducts as $id){
                        $idproduct_master[]=$id->idproduct_master;
                    }
                    $productQuery = Helper::prepareProductQuery();
                    $inventory = $productQuery->where('inventory.idstore_warehouse', $idstore_warehouse)
                    ->whereIn('product_master.idproduct_master', $idproduct_master)
                    ->limit(10)
                    ->get();
                    return response()->json([
                        'statusCode' => '0',
                        'message' => 'success',
                        'data' => $product_master
                    ]);
                }else{
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'Frequently Bought Products are not found!'
                    ]);
                }
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
                // $popularProducts = DB::table('product_master')
                //     ->leftJoin('order_detail','product_master.idproduct_master','=','order_detail.idproduct_master')
                //     ->selectRaw('product_master.*, COALESCE(sum(order_detail.quantity),0) total')
                //     ->groupBy('product_master.idproduct_master')
                //     ->orderBy('total','desc')
                //     ->take(10)
                //     ->get();
                $idstore_warehouse = $request->input('idstore_warehouse');
                $productQuery = Helper::prepareProductQuery();
                $popularProducts = $productQuery->leftJoin('order_detail','product_master.idproduct_master','=','order_detail.idproduct_master')
                ->selectRaw('product_master.*, COALESCE(sum(order_detail.quantity),0) total')
                ->where('inventory.idstore_warehouse', $idstore_warehouse)
                ->groupBy('product_master.idproduct_master')
                ->orderBy('total','desc')
                ->limit(10)
                ->get();
                if($popularProducts){
                    return response()->json([
                        'statusCode' => '0',
                        'message' => 'success',
                        'data' => $popularProducts
                    ]);
                }else{
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'popular products are not found!',
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch product data', 'details' => $e->getMessage()], 500);
            }
        }else{
            return response()->json(['error' => 'Failed to provide warehouse store'], 400);
        }

    }
}
