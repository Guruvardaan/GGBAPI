<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  Illuminate\Support\Carbon;

class SystemReportController extends Controller
{
    public function get_performance_report(Request $request)
    {
        $get_best_seller = DB::table('vendor_purchases')
                                    ->select('idvendor', DB::raw('sum(quantity) as total_sales')) 
                                    ->groupBy('idvendor')
                                    ->orderBy('total_sales', 'desc')
                                    ->first();
        $get_worst_seller = DB::table('vendor_purchases')
                                    ->select('idvendor', DB::raw('sum(quantity) as total_sales')) 
                                    ->groupBy('idvendor')
                                    ->orderBy('total_sales', 'asc')
                                    ->first();  
        $get_year_over_year_growth = $this->get_year_over_year_growth();
        $data['get_best_seller'] =  $this->get_seller_detail($get_best_seller->idvendor);
        $data['get_best_seller']->total_sales = $get_best_seller->total_sales;
        $data['get_worst_seller'] = $this->get_seller_detail($get_worst_seller->idvendor);
        $data['get_worst_seller']->total_sales = $get_worst_seller->total_sales;
        $data['get_year_over_year_growth'] = $get_year_over_year_growth;               
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data], 200);                                   
    }

    public function get_year_over_year_growth() 
    {
        $get_current_year_data = DB::table('vendor_purchases_detail')
                    ->select(DB::raw('sum(quantity) as total_sales'))
                    ->whereYear('created_at', date('Y'))
                    ->get()[0];
        $get_previous_year_data = DB::table('vendor_purchases_detail')
                    ->select(DB::raw('sum(quantity) as total_sales'))
                    ->whereYear('created_at',  date('Y')-1)
                    ->get()[0];                     
        $total_salled_quantity = (!empty($get_current_year_data->total_sales) ? $get_current_year_data->total_sales : 0) - (!empty($get_previous_year_data->total_sales) ? $get_previous_year_data->total_sales : 0);      
        $year_over_year_growth['percentage'] = !empty($get_previous_year_data->total_sales) ? $total_salled_quantity/($get_previous_year_data->total_sales * 100) : 100;
        $year_over_year_growth['total_salled_quantity'] = $total_salled_quantity;
        return $year_over_year_growth;            
    }

    public function get_seller_detail($id) 
    {
       $seller_data =  DB::table('vendors')
                       ->select('id As idvendor','name', 'phone')
                       ->where('id', $id)
                       ->first();
       return $seller_data;                
    }

    
    public function get_inventory_profitability_report(Request $request)
    {
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;

        $profitability = DB::table('inventory')
                         ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                         ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                         ->select('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                         ->groupBy('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price')
                         ->get();
        foreach($profitability as $product) {
            $product->profit_report['sku_profit'] =  round(($product->selling_price - $product->purchase_price) * $product->total_quantity, 3);
            $product->profit_report['listing_profit']['gross_margin'] = round($product->selling_price - $product->purchase_price, 3);
            $product->profit_report['listing_profit']['unit_margin'] = round(($product->selling_price - $product->purchase_price)/$product->total_quantity, 3);
            $product->profit_report['trending_profit'] = $this->get_trending_profitability($product->idproduct_master, $start_date, $end_date);
        }
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $profitability], 200);
    }

    public function get_trending_profitability($id, $start_date = null, $end_date = null) 
    {
        $start_date = !empty($start_date) ? $start_date : Carbon::now()->subdays(30);
        $end_date = !empty($end_date)? $end_date :  Carbon::now();
        // dd($end_date);

        $trending_profitability = DB::table('inventory')
                         ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                         ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                         ->select('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', 'inventory.created_at',DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                         ->groupBy('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', 'inventory.created_at')
                         ->where('inventory.idproduct_master', $id)
                         ->whereBetween('inventory.created_at',[$start_date, $end_date])
                         ->get();
        $trending_profit = 0;
        // dd($trending_profitability);
        foreach($trending_profitability as $product) {
            $trending_profit += round(($product->selling_price - $product->purchase_price) * $product->total_quantity, 3);    
        }                
        
        return $trending_profit;
    }

}
