<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReportController extends Controller
{
   

    public function get_product_report(Request $request)
    {
        try{
            $productmaster = DB::table('product_master')
                            ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'product_master.idproduct_master')
                            ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                            ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                            ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                            ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                            ->select(
                                'product_master.idproduct_master',
                                'product_master.idcategory',
                                'category.name As category_name',
                                'product_master.idsub_category',
                                'sub_category.name as sub_category_name',
                                'product_master.idsub_sub_category',
                                'sub_sub_category.name AS sub_sub_category_name',
                                'product_master.idbrand',
                                'brands.name As brand_name',
                                'product_master.name',
                                'product_master.description',
                                'product_master.barcode',
                                'product_master.image',
                                'product_master.hsn',
                                'product_batch.mrp',
                                'product_master.discount',
                                'product_master.cgst',
                                'product_master.sgst',
                                'product_master.igst',
                                'product_master.cess',
                                'product_master.created_at',
                                'product_master.updated_at',
                                'product_master.created_by',
                                'product_master.updated_by',
                                'product_master.status',
                                'product_batch.selling_price AS selling_price',
                                'product_batch.purchase_price AS purchase_price'        
                            );
            $products = [];             

            if(!empty($request->idcategory)) {
                $productmaster->where('product_master.idcategory', $request->idcategory);
            } 
            if(!empty($request->idsub_category)) {
                $productmaster->where('product_master.idsub_category', $request->idsub_category);
            }
            if(!empty($request->idsub_sub_category)) {
                $productmaster->where('product_master.idsub_sub_category', $request->idsub_sub_category);    
            } 
            if(!empty($request->idbrand)) {
               $productmaster->where('product_master.idbrand', $request->idbrand);
            } 
            
            $products = $productmaster->get();

            foreach($products as $product)
            {   
                $product->selling_margin_percentage = round(($product->selling_price - $product->purchase_price) / $product->selling_price * 100, 2);
                $product->selling_margin_rupees = $product->selling_price - $product->purchase_price;
                $product_price = $product->mrp - round($product->mrp  - $product->mrp * (100/(100 + ($product->cgst + $product->igst + $product->sgst))), 2); 
                $product->purchase_margin_percentage  = round(($product_price - $product->purchase_price) / $product_price * 100, 2);
                $product->purchase_margin_rupees = $product_price - $product->purchase_price;
            }
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $products], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }
}
