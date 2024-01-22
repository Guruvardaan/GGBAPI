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
            $start_date =  !empty($request->start_date) ? $request->start_date : null;
            $end_date = !empty($request->end_date)? $request->end_date :  null;
            // $limit = !empty($request->limit) ? $request->limit : 50; 
            $limit = !empty($request->rows) ? $request->rows : 50;
            $skip = !empty($request->first) ? $request->first : 0;
        
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
            
            if(!empty($request->idstore_warehouse)) {
                $productmaster->where('product_batch.idstore_warehouse', $request->idstore_warehouse);
            }

            if(!empty($request->field) && $request->field=="product"){
                $productmaster->where('product_master.name', 'like', $request->searchTerm . '%');
            }
            if(!empty($request->field) && $request->field=="brand"){
                $productmaster->where('brands.name', 'like', $request->searchTerm . '%');
            }
            if(!empty($request->field) && $request->field=="category"){
                $productmaster->where('category.name', 'like', $request->searchTerm . '%');
            }
            if(!empty($request->field) && $request->field=="sub_category"){
                $productmaster->where('sub_category.name', 'like', $request->searchTerm . '%');
            }
            if(!empty($request->field) && $request->field=="barcode"){
                $barcode=$request->searchTerm;
                $productmaster->where('product_master.barcode', 'like', $barcode . '%');
            }

            if(!empty($start_date) &&  !empty($end_date)) {
                $productmaster->whereBetween('product_master.created_at',[$start_date, $end_date]);
            }    

            $totalRecords = $productmaster->get()->count();
            $products = $productmaster->skip($skip)->take($limit)->get();

            foreach($products as $product)
            {   $product->selling_margin_rupees = $product->mrp - $product->selling_price;
                $product->selling_margin_percentage = ($product->selling_margin_rupees !== 0) ? ($product->selling_margin_rupees/$product->mrp) * 100 : 0;
                $cgst = !empty($product->cgst) ? $product->cgst : 0;
                $sgst = !empty($product->sgst) ? $product->sgst : 0;
                $gst = $cgst + $sgst;
                $product->purchase_margin_rupees = !empty($gst) ? $product->mrp - ($product->purchase_price +  (($product->purchase_price * $gst)/100)) : $product->mrp - $product->purchase_price;
                $product->purchase_margin_percentage = !empty($product->purchase_margin_rupees) ? ($product->purchase_margin_rupees *  $product->mrp)/ 100 : 0;
            }
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $products, 'total' => $totalRecords], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }
}
