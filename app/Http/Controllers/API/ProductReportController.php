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
            $additional_filter = !empty($_GET['additional_filter']) ?$_GET['additional_filter'] : null;
            $margin_type = !empty($_GET['margin_type']) ?$_GET['margin_type'] : null;
            $exact = !empty($_GET['exact']) ?$_GET['exact'] : 0;
            $filter_filed_1 = !empty($_GET['filter_filed_1']) ?$_GET['filter_filed_1'] : null;
            $filter_filed_2 = !empty($_GET['filter_filed_2']) ?$_GET['filter_filed_2'] : null;
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
                                'product_master.cgst',
                                'product_master.sgst',
                                'product_master.igst',
                                'product_master.cess',
                                'product_batch.selling_price AS selling_price',
                                'product_batch.purchase_price As purchase_price',
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS purchase_price_with_gst'),
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END), 2) AS selling_price_with_gst'),
                                
                                DB::raw('ROUND(product_batch.selling_price - product_batch.purchase_price, 2) AS unit_purchase_margin_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.selling_price - product_batch.purchase_price)/product_batch.selling_price) * 100, 2) AS unit_purchase_margin_without_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.purchase_price, 2) AS unit_profit_margin_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - product_batch.purchase_price)/product_batch.mrp) * 100, 2) AS unit_profit_margin_without_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.selling_price, 2) AS discount_per_unit_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - product_batch.selling_price)/product_batch.mrp) * 100, 2) AS discount_per_unit_without_tax_pr'),
                                
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS unit_purchase_margin_with_tax_rupees'),
                                DB::raw('ROUND((((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/(CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END)) * 100, 2) AS unit_purchase_margin_with_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS unit_profit_margin_with_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2) AS unit_profit_margin_with_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END), 2) AS discount_per_unit_with_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END))/product_batch.mrp) * 100, 2) AS discount_per_unit_with_tax_pr'),
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
            if(!empty($_GET['field']) && $_GET['field']=="hsn"){
                $productmaster->where('product_master.hsn', 'like', $_GET['searchTerm'] . '%');
            }

            if(!empty($additional_filter) && $additional_filter === 'purchase_margin') {
                if(!empty($margin_type) && $margin_type === 'without_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND(((product_batch.selling_price - product_batch.purchase_price)/product_batch.selling_price) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND(((product_batch.selling_price - product_batch.purchase_price)/product_batch.selling_price) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }

                } 
                if(!empty($margin_type) && $margin_type === 'with_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND((((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/(CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END)) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND((((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/(CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END)) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }
                } 
            }

            if(!empty($additional_filter) && $additional_filter === 'profit_margin') {
                if(!empty($margin_type) && $margin_type === 'without_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND(((product_batch.mrp - product_batch.purchase_price)/product_batch.mrp) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND(((product_batch.mrp - product_batch.purchase_price)/product_batch.mrp) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }

                } 
                if(!empty($margin_type) && $margin_type === 'with_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }
                } 
            }

            if(!empty($additional_filter) && $additional_filter === 'discount_margin') {
                if(!empty($margin_type) && $margin_type === 'without_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND(((product_batch.mrp - product_batch.selling_price)/product_batch.mrp) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND(((product_batch.mrp - product_batch.selling_price)/product_batch.mrp) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }

                } 
                if(!empty($margin_type) && $margin_type === 'with_tax') {
                    if(!empty($exact)) {
                        $productmaster->where(DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2)'), $filter_filed_1);
                    } else {
                        $productmaster->whereBetween(DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2)'), [$filter_filed_1, $filter_filed_2]);
                    }
                } 
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
                                'product_batch.selling_price AS selling_price',
                                'product_batch.purchase_price As purchase_price',
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS purchase_price_with_gst'),
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END), 2) AS selling_price_with_gst'),

                                DB::raw('ROUND(product_batch.selling_price - product_batch.purchase_price, 2) AS unit_purchase_margin_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.selling_price - product_batch.purchase_price)/product_batch.selling_price) * 100, 2) AS unit_purchase_margin_without_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.purchase_price, 2) AS unit_profit_margin_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - product_batch.purchase_price)/product_batch.mrp) * 100, 2) AS unit_profit_margin_without_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - product_batch.selling_price, 2) AS discount_per_unit_without_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - product_batch.selling_price)/product_batch.mrp) * 100, 2) AS discount_per_unit_without_tax_pr'),
                                
                                DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS unit_purchase_margin_with_tax_rupees'),
                                DB::raw('ROUND((((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END) - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/(CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END)) * 100, 2) AS unit_purchase_margin_with_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END), 2) AS unit_profit_margin_with_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.purchase_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.purchase_price END))/product_batch.mrp) * 100, 2) AS unit_profit_margin_with_tax_pr'),
                                DB::raw('ROUND(product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END), 2) AS discount_per_unit_with_tax_rupees'),
                                DB::raw('ROUND(((product_batch.mrp - (CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (product_batch.selling_price + (product_batch.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE product_batch.selling_price END))/product_batch.mrp) * 100, 2) AS discount_per_unit_with_tax_pr'),
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
            if(!empty($_GET['field']) && $_GET['field']=="hsn"){
                $productmaster->where('product_master.hsn', 'like', $_GET['searchTerm'] . '%');
            }
            if(!empty($start_date) &&  !empty($end_date)) {
                $productmaster->whereBetween('product_master.created_at',[$start_date, $end_date]);
            }

            $total_product = $productmaster->paginate(10)->total();
            $products = $productmaster->get();
            $purchase_margin_with_tax = 0;
            $purchase_margin_without_tax = 0;
            $profit_margin_with_tax = 0;
            $profit_margin_without_tax = 0;
            $discount_margin_with_tax = 0;
            $discount_margin_without_tax = 0; 
            $selling_cost_with_tax = 0;
            $purchase_cost_without_tax = 0;
            $selling_cost_without_tax = 0;
            $purchase_cost_with_tax = 0;       
            foreach($products as $product)
            {
                $purchase_margin_with_tax = $purchase_margin_with_tax + $product->unit_purchase_margin_with_tax_rupees;
                $purchase_margin_without_tax = $purchase_margin_without_tax + $product->unit_purchase_margin_without_tax_rupees;
                $profit_margin_with_tax = $purchase_margin_with_tax + $product->unit_profit_margin_with_tax_rupees;
                $profit_margin_without_tax = $purchase_margin_with_tax + $product->unit_profit_margin_without_tax_rupees;
                $discount_margin_with_tax = $purchase_margin_with_tax + $product->discount_per_unit_with_tax_rupees;
                $discount_margin_without_tax = $purchase_margin_with_tax + $product->discount_per_unit_without_tax_rupees;
                $purchase_cost_without_tax = $purchase_cost_without_tax + $product->purchase_price;
                $selling_cost_without_tax = $selling_cost_without_tax + $product->selling_price;
                $selling_cost_with_tax = $selling_cost_with_tax + $product->selling_price_with_gst;
                $purchase_cost_with_tax = $purchase_cost_with_tax + $product->purchase_price_with_gst;
            }

            // dd(($purchase_margin_with_tax/100) * $purchase_margin_with_tax);

            $data = [
                'total_product' => $total_product,
                'avg_purchase_margin_with_tax' => round($purchase_margin_with_tax/$total_product, 4),
                'avg_purchase_margin_without_tax' => round($purchase_margin_without_tax/$total_product, 4),
                'avg_profit_margin_with_tax' => round($profit_margin_with_tax/$total_product, 4),
                'avg_profit_margin_without_tax' => round($profit_margin_without_tax/$total_product, 4),
                'avg_discount_margin_with_tax' => round($discount_margin_with_tax/$total_product, 4),
                'avg_discount_margin_without_tax' => round($discount_margin_without_tax/$total_product, 4),
                
                'avg_purchase_margin_with_tax_pr' => round((($purchase_margin_with_tax/$total_product)/100) *  $purchase_margin_with_tax, 2),
                'avg_purchase_margin_without_tax_pr' => round(($purchase_margin_without_tax/$total_product/100) * $purchase_margin_without_tax, 2),
                'avg_profit_margin_with_tax_pr' => round((($profit_margin_with_tax/$total_product)/100) * $profit_margin_with_tax, 2),
                'avg_profit_margin_without_tax_pr' => round((($profit_margin_without_tax/$total_product)/100) * $profit_margin_without_tax, 2),
                'avg_discount_margin_with_tax_pr' => round((($discount_margin_with_tax/$total_product)/100) * $discount_margin_with_tax, 2),
                'avg_discount_margin_without_tax_pr' => round((($discount_margin_without_tax/$total_product)/100) * $discount_margin_without_tax, 2),
                
                'avg_selling_cost_without_tax' => round($selling_cost_without_tax/$total_product, 4),
                'avg_selling_cost_with_tax' => round($selling_cost_with_tax/$total_product, 4),
                'avg_purchase_cost_without_tax' => round($purchase_cost_without_tax/$total_product, 4),
                'avg_purchase_cost_with_tax' => round($purchase_cost_with_tax/$total_product, 4),
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
