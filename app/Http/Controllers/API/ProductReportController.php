<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReportController extends Controller
{
   

    public function get_product_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        try{
            $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
            $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;
            $limit = !empty($_GET['rows']) ? $_GET['rows'] : 10;
            $skip = !empty($_GET['first']) ?$_GET['first'] : 0;
            $start_selling_margin_pr = !empty($_GET['start_selling_margin_pr']) ? $_GET['start_selling_margin_pr'] : 0;
            $end_selling_margin_pr = !empty($_GET['end_selling_margin_pr']) ? $_GET['end_selling_margin_pr'] : 0;
            $start_purchase_margin_pr = !empty($_GET['start_purchase_margin_pr']) ? $_GET['start_purchase_margin_pr'] : 0;
            $end_purchase_margin_pr = !empty($_GET['end_purchase_margin_pr']) ? $_GET['end_purchase_margin_pr'] : 0;
            $start_discount_margin_pr = !empty($_GET['start_discount_margin_pr']) ? $_GET['start_discount_margin_pr'] : 0;
            $end_discount_margin_pr = !empty($_GET['end_discount_margin_pr']) ? $_GET['end_discount_margin_pr'] : 0;
        
            $product_with_distinct_barcode = $this->get_product_with_distinct_barcode();

            $productmaster = DB::table('product_master')
                            ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'product_master.idproduct_master')
                            ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                            ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                            ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                            ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                            ->select(
                                'product_master.idproduct_master',
                                'category.name As category_name',
                                'sub_category.name as sub_category_name',
                                'sub_sub_category.name AS sub_sub_category_name',
                                'brands.name As brand_name',
                                'product_master.name',
                                'product_master.description',
                                'product_master.barcode',
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
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS purchase_price'),
                                DB::raw('ROUND((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)), 2) AS selling_margin_rupees'),
                                DB::raw('ROUND((CASE WHEN (product_batch.mrp  - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) != 0 THEN ((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.selling_price) * 100 ELSE 0 END), 2) AS selling_margin_percentage'),
                                DB::raw('ROUND((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)), 2) AS purchase_margin_rupees'),
                                DB::raw('ROUND((CASE WHEN product_batch.mrp != 0 THEN ((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.mrp) * 100 ELSE 0 END), 2) AS purchase_margin_percentage'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.selling_price, 2) As discount_amount'),
                                DB::raw('ROUND((product_batch.mrp - product_batch.selling_price)/100 * product_batch.mrp, 2) As discount_pr'),
                            )
                            ->whereIn('product_master.barcode', $product_with_distinct_barcode);
      
            if(!empty($_GET['idstore_warehouse'])) {
                $productmaster->where('product_batch.idstore_warehouse', $_GET['idstore_warehouse']);
            }

            if(!empty($_GET['field']) && $_GET['field']=="product"){
                $productmaster->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="brand"){
                $productmaster->where('brands.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="category"){
                $productmaster->where('category.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
                $productmaster->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="barcode"){
                $barcode=$_GET['searchTerm'];
                $productmaster->where('product_master.barcode', 'like', $barcode . '%');
            }

            if(!empty($start_date) &&  !empty($end_date)) {
                $productmaster->whereBetween('product_master.created_at',[$start_date, $end_date]);
            }
            
            if(!empty($start_selling_margin_pr) &&  !empty($end_selling_margin_pr)) {
                $productmaster->whereBetween(DB::raw('(CASE WHEN (product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) != 0 THEN ((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.selling_price) * 100 ELSE 0 END)'),[$start_selling_margin_pr, $end_selling_margin_pr]);
            }

            if($start_selling_margin_pr === 0 && !empty($end_selling_margin_pr)) {
                $productmaster->where(DB::raw('(CASE WHEN (product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) != 0 THEN ((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.selling_price) * 100 ELSE 0 END)'), '<=', $start_selling_margin_pr);
            }

            if(empty($start_selling_margin_pr) &&  !empty($end_selling_margin_pr)) {
                $productmaster->whereBetween(DB::raw('(CASE WHEN (product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) != 0 THEN ((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.selling_price) * 100 ELSE 0 END)'),[$start_selling_margin_pr, $end_selling_margin_pr]);
            }

            if(!empty($start_purchase_margin_pr) &&  !empty($end_purchase_margin_pr)) {
                $productmaster->whereBetween(DB::raw('(CASE WHEN product_batch.mrp != 0 THEN ((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.mrp) * 100 ELSE 0 END)'),[$start_purchase_margin_pr, $end_purchase_margin_pr]);
            }

            if($start_purchase_margin_pr === 0 && !empty($end_purchase_margin_pr)) {
                $productmaster->where(DB::raw('(CASE WHEN product_batch.mrp != 0 THEN ((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.mrp) * 100 ELSE 0 END)'), '<=', $start_purchase_margin_pr);
            }

            if(empty($start_purchase_margin_pr) &&  !empty($end_purchase_margin_pr)) {
                $productmaster->whereBetween(DB::raw('(CASE WHEN product_batch.mrp != 0 THEN ((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.mrp) * 100 ELSE 0 END)'),[$start_purchase_margin_pr, $end_purchase_margin_pr]);
            }

            if(!empty($start_discount_margin_pr) &&  !empty($end_discount_margin_pr)) {
                $productmaster->whereBetween(DB::raw('ROUND((product_batch.mrp - product_batch.selling_price)/100 * product_batch.mrp, 2)'), [$start_discount_margin_pr, $end_discount_margin_pr]);
            }

            if($start_discount_margin_pr === 0 && !empty($end_discount_margin_pr)) {
                $productmaster->where(DB::raw('ROUND((product_batch.mrp - product_batch.selling_price)/100 * product_batch.mrp, 2)'), '<=', $start_discount_margin_pr);
            }

            if(empty($start_discount_margin_pr) &&  !empty($end_discount_margin_pr)) {
                $productmaster->whereBetween(DB::raw('ROUND((product_batch.mrp - product_batch.selling_price)/100 * product_batch.mrp, 2)'), [$start_discount_margin_pr, $end_discount_margin_pr]);
            }

            $totalRecords = $productmaster->count();
            $limit = abs($limit - $skip);
            $products = $productmaster->skip($skip)->take($limit)->get();

            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $products, 'total' => $totalRecords], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }

    public function product_report_state()
    {
            ini_set('max_execution_time', 14000);
            $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
            $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;

            $product_with_distinct_barcode = $this->get_product_with_distinct_barcode();
            // $total_product =  DB::table('product_master')->select('idproduct_master')->whereIn('product_master.barcode', $product_with_distinct_barcode)->count();
            $productmaster = DB::table('product_master')
                            ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'product_master.idproduct_master')
                            ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                            ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                            ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                            ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                            ->select(
                                DB::raw('ROUND((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)), 2) AS selling_margin_rupees'),
                                // DB::raw('ROUND((CASE WHEN (product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) != 0 THEN ((product_batch.selling_price - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.selling_price) * 100 ELSE 0 END), 2) AS selling_margin_percentage'),
                                DB::raw('ROUND((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)), 2) AS purchase_margin_rupees'),
                                // DB::raw('ROUND((CASE WHEN product_batch.mrp != 0 THEN ((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END)) / product_batch.mrp) * 100 ELSE 0 END), 2) AS purchase_margin_percentage'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.selling_price, 2) As discount_amount'),
                                // DB::raw('ROUND((product_batch.mrp - product_batch.selling_price)/100 * product_batch.mrp, 2) As discount_pr'),
                            )
                            ->whereIn('product_master.barcode', $product_with_distinct_barcode);
            //   
            
            if(!empty($_GET['idstore_warehouse'])) {
                $productmaster->where('product_batch.idstore_warehouse', $_GET['idstore_warehouse']);
            }

            if(!empty($_GET['field']) && $_GET['field']=="product"){
                $productmaster->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="brand"){
                $productmaster->where('brands.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="category"){
                $productmaster->where('category.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
                $productmaster->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($_GET['field']) && $_GET['field']=="barcode"){
                $barcode=$_GET['searchTerm'];
                $productmaster->where('product_master.barcode', 'like', $barcode . '%');
            }

            if(!empty($start_date) &&  !empty($end_date)) {
                $productmaster->whereBetween('product_master.created_at',[$start_date, $end_date]);
            }

            $total_product = $productmaster->paginate(10)->total();
            $products = $productmaster->get();
            $selling_margin = 0;
            $purchase_margin = 0;
            $discount_margin = 0;       
            foreach($products as $product)
            {
                $selling_margin = $selling_margin + $product->selling_margin_rupees;
                $purchase_margin = $purchase_margin + $product->purchase_margin_rupees;
                $discount_margin = $discount_margin + $product->discount_amount;
            }

            $avg_selling_margin = $selling_margin/$total_product;
            $avg_purchase_margin = $purchase_margin/$total_product;
            $avg_discount_margin = $discount_margin/$total_product;

            $avg_selling_margin_pr = $avg_selling_margin/100;
            $avg_purchase_margin_pr = $avg_purchase_margin/100;
            $avg_discount_margin_pr = $avg_discount_margin/100;


            $data = [
                'total_product' => $total_product,
                'avg_selling_margin' => round($avg_selling_margin, 2),
                'avg_purchase_margin' => round($avg_purchase_margin, 2),
                'avg_discount_margin' => round($avg_discount_margin, 2),
                'avg_selling_margin_pr' => round($avg_selling_margin_pr, 2),
                'avg_purchase_margin_pr' => round($avg_purchase_margin_pr, 2),
                'avg_discount_margin_pr' => round($avg_discount_margin_pr, 2),
            ];
            
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data], 200);
    }

    public function get_product_with_distinct_barcode()
    {
        $all_products =  DB::table('product_master')->select(DB::raw('DISTINCT(barcode)'))->where('barcode', '<>', '')->get()->toArray();
        $product_array = [];
        foreach($all_products as $key => $product){
            $product_array[$key] = $product->barcode;
        }
        
        return $product_array;
    }
}
