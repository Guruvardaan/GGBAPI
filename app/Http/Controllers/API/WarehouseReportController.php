<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseReportController extends Controller
{
    public function get_warehouse_report(Request $request)
    {
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;

        $warehouses =  DB::table('store_request')
        ->leftJoin('store_warehouse', 'store_warehouse.idstore_warehouse', '=', 'store_request.idstore_warehouse_to')
        ->select(
            'store_warehouse.name AS warehouse_name',
            'store_warehouse.idstore_warehouse',
            'store_request.idstore_request'
            );
            
        if(!empty($request->idstore_warehouse)) {
            $warehouses->where('store_warehouse.idstore_warehouse', $request->idstore_warehouse);
        }    

        if(!empty($start_date) &&  !empty($end_date)) {
            $warehouses->whereBetween('store_request.created_at',[$start_date, $end_date]);
        }

        $warehouseData = $warehouses->get();

        foreach($warehouseData as $store) {
            $data = $this->get_requested_warehouse($store->idstore_request);
            $product_report = $this->get_product_report($store->idstore_request);
            $store->store_who_requested = $data->store_who_requested;
            $store->product_report = $product_report;
        }    
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $warehouseData], 200);
    }

    public function get_products()
    {
        $products = DB::table('inventory')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                            ->select('inventory.idproduct_master', DB::raw('SUM(quantity) as selled_quantity'))
                            ->groupBy('inventory.idproduct_master')->get();
        foreach($products as $product) {
            $productData = $this->get_product_name_and_barcode($product->idproduct_master);
            $product->product_name = $productData['name'];
            $product->product_barcode = $productData['barcode'];
            $quantity = $this->get_product_quantity($product->idproduct_master);
            $product->total_quantity = $quantity['quantity'] + $product->selled_quantity;
            $product->remaining_quanity = $product->total_quantity - $product->selled_quantity;
        }                        
       
        return $products;                
    }

    public function get_product_quantity($id)
    {
        $quantity = DB::table('product_batch')->select('quantity')->where('idproduct_master', $id)->first();
        return (array)$quantity;
    }

    public function get_warehouse_id($id)
    {
        $store_warehouse = DB::table('product_batch')->select('idstore_warehouse')->where('idproduct_master', $id)->first();
        return (array)$store_warehouse;
    }

    public function get_product_name_and_barcode($id)
    {
        $data = DB::table('product_master')->select('name', 'barcode')->where('idproduct_master', $id)->first();
        return (array)$data;
    }


    public function get_requested_warehouse($id)
    {
        $requested_warehouse =  DB::table('store_request')
        ->leftJoin('store_warehouse', 'store_warehouse.idstore_warehouse', '=', 'store_request.idstore_warehouse_from')
        ->select(
            'store_warehouse.name AS store_who_requested',
            )->where('idstore_request', $id)->first();
        return $requested_warehouse;
    }

    public function get_product_report($id)
    {
        $product_report =  DB::table('store_request_detail')
        ->leftJoin('store_request', 'store_request.idstore_request', '=', 'store_request_detail.idstore_request')
        ->select(
            'store_request_detail.quantity As quantity_asked',
            'store_request_detail.quantity_sent',
            'store_request_detail.idproduct_master'
        )
        ->where('store_request_detail.idstore_request', $id)->get();
        foreach($product_report as $product) {
            $productData = $this->get_product_data($product->idproduct_master);
            if(!empty($productData)) {
                $product->product_name = $productData->name;
                $product->barcode = $productData->barcode;
                $product->category_name = $productData->category_name;
                $product->sub_category_name = $productData->sub_category_name;
                $product->sub_sub_category_name = $productData->sub_sub_category_name;
                $product->brands_name = $productData->brands_name;
            }    

        }
        return $product_report;
    }

    public function get_product_data($id)
    {
        $product_data = DB::table('product_master')
                            ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                            ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                            ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                            ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                            ->where('product_master.idproduct_master', $id)
                            ->select(
                                'product_master.name',
                                'product_master.barcode',
                                'category.name As category_name',
                                'sub_category.name As sub_category_name',
                                'sub_sub_category.name AS sub_sub_category_name',
                                'brands.name As brands_name'
                            )
                            ->first();
        return $product_data;
    }
}
