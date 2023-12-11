<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function get_inventory_report(Request $request)
    {
        $inventories_data = DB::table('inventory')
                            ->leftJoin('store_warehouse', 'store_warehouse.idstore_warehouse', '=', 'inventory.idstore_warehouse')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                            ->select('store_warehouse.idstore_warehouse', 'product_master.idproduct_master', 'inventory.quantity As total_quantity');

        if(!empty($request->idstore_warehouse)) {
            $inventories_data->where('store_warehouse.idstore_warehouse', $request->idstore_warehouse);
        }       

        $inventories = $inventories_data->get();
        
        foreach($inventories as $inventory) {
            if(!empty($inventory->idproduct_master)) {
                $vendor_data = $this->get_vendor_detail($inventory->idproduct_master);
                $expiry_data = $this->get_expire_report($inventory->idproduct_master);
                $product_data = $this->get_product_data($inventory->idproduct_master);
            
                $expiry_data->amount = $expiry_data->mrp * $expiry_data->quantity;
                $inventory->selled_product = $vendor_data->quantity;
                $inventory->remaining_quanity = $inventory->total_quantity - $vendor_data->quantity;
                $inventory->expire = $vendor_data->expiry;
                $inventory->expiry_report = $expiry_data;
                $inventory->product_name = $product_data->name;
                $inventory->product_barcode = $product_data->barcode;
                $inventory->category = $product_data->category_name;
                $inventory->sub_category = $product_data->sub_category_name;
                $inventory->sub_sub_category = $product_data->sub_sub_category_name;
                $inventory->brands = $product_data->brands_name;
            }
        }              
                            
        // foreach($inventories as $inventory) {
        //     $productData = $this->get_product_name_and_barcode($inventory->idproduct_master);
        //     $inventory->product_name = $productData['name'];
        //     $inventory->product_barcode = $productData['barcode'];
        //     $quantity = $this->get_product_quantity($inventory->idproduct_master);
        //     $inventory->total_quantity = $quantity['quantity'] + $inventory->selled_quantity;
        //     $inventory->remaining_quanity = $inventory->total_quantity - $inventory->selled_quantity;
        // }               
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $inventories], 200);
    }

    public function get_product_quantity($id)
    {
        $quantity = DB::table('product_batch')->select('quantity')->where('idproduct_master', $id)->first();
        return (array)$quantity;
    }

    public function get_product_name_and_barcode($id)
    {
        $data = DB::table('product_master')->select('name', 'barcode')->where('idproduct_master', $id)->first();
        return (array)$data;
    }

    public function get_vendor_detail($id)
    {
        $vendors = DB::table('vendor_purchases_detail')->select('quantity', 'expiry')->where('idproduct_master', $id)->first();
        return $vendors;
    }

    public function get_expire_report($id)
    {
        $expireAmount = DB::table('vendor_purchases_detail')->select('quantity', 'mrp', 'expiry')->where('idproduct_master', $id)->first();
        return $expireAmount;
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
