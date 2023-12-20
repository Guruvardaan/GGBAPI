<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        $limit = !empty($request->limit) ? $request->limit : 25; 

        $profitability = DB::table('inventory')
                         ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                         ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                         ->select('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                         ->groupBy('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price')
                         ->paginate($limit);
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
                         ->paginate(20);
        $trending_profit = 0;
        foreach($trending_profitability as $product) {
            $trending_profit += round(($product->selling_price - $product->purchase_price) * $product->total_quantity, 3);    
        }                
        
        return $trending_profit;
    }

    public function get_value_report(Request $request)
    {
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $limit = !empty($request->limit) ? $request->limit : 25;

        $data = DB::table('inventory')
                            ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                            ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                            ->select('inventory.idproduct_master','inventory.idstore_warehouse' ,'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', 'inventory.created_at', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                            ->groupBy('inventory.idproduct_master','inventory.idstore_warehouse' ,'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', 'inventory.created_at');
        if(!empty($request->idstore_warehouse)) {
            $data->where('inventory.idstore_warehouse', $request->idstore_warehouse);
        } 

        if(!empty($start_date) &&  !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }

        $value_report_data = $data->paginate($limit)->toArray();                   
        $value_report_data = $this->data_formatting($value_report_data);        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $value_report_data], 200);                    
    }

    public function data_formatting($data)
    {
        $transformedData = [];
        // dd($data['current_page']);

        foreach ($data['data'] as $item) {
            $idstore_warehouse = $item->idstore_warehouse;
            $warehouse_name = $this->get_warehouse_name($idstore_warehouse);
            
            $key = "{$item->idstore_warehouse}";
            if (!isset($transformedData[$key])) {
                $transformedData[$key] = [
                    'idstore_warehouse' => $idstore_warehouse,
                    'warehouse_name' => $warehouse_name,
                    'products' => [],
                ];
            }

            $transformedData[$key]['products'][] = [
                'idproduct_master' => $item->idproduct_master,
                'product_name' => $item->name,
                'snapshot_value' => round($item->total_quantity * $item->purchase_price, 2),
                'performance_report' => [
                    'value' => $item->purchase_price,
                    'turnover_ratio' => $item->total_quantity > 0 ? $item->purchase_price / $item->total_quantity : 0,
                ]
            ];
        }

        $transformedData = array_values($transformedData);

        $transformedData['current_page'] = $data['current_page'];
        $transformedData['first_page_url'] = $data['first_page_url'];
        $transformedData['from'] = $data['from'];
        $transformedData['last_page'] = $data['last_page'];
        $transformedData['last_page_url'] = $data['last_page_url'];
        $transformedData['links'] = $data['links'];
        $transformedData['next_page_url'] = $data['next_page_url'];
        $transformedData['path'] = $data['path'];
        $transformedData['per_page'] = $data['per_page'];
        $transformedData['prev_page_url'] = $data['prev_page_url'];
        $transformedData['to'] = $data['to'];
        $transformedData['total'] = $data['total'];

        // $transformedData[0]['test'] = 1;
        // dd($transformedData);

        foreach($transformedData as $key => $data){
            $trending_value = 0;
            if(is_numeric($key)) {
                foreach($data['products'] as $product) {
                    $trending_value +=  $product['snapshot_value'];
                }
                $transformedData[$key]['trending_value'] = round($trending_value, 2);
            }
        }
        return $transformedData;
    }

    public function get_warehouse_name($id)
    {
        $warehouse = DB::table('store_warehouse')->where('idstore_warehouse', $id)->first();
        return !empty($warehouse) ? $warehouse->name : ''; 
    }

    public function get_stock_levels_report(Request $request)
    {
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;

        $data = DB::table('inventory')
                           ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                           ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                           ->select('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                           ->groupBy('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse');
                                    
        if(!empty($request->idstore_warehouse)) {
            $data->where('inventory.idstore_warehouse', $request->idstore_warehouse);
        }
        
        if(!empty($start_date) &&  !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }
        
        $stock_levels_report_data = $data->get();
        foreach($stock_levels_report_data as $key => $product) {
            $selled_products = $this->get_selled_quantity($product->idproduct_master);
            $remaining_product = 0;
            foreach($selled_products as $selled_product) {
                if($product->idproduct_master === $selled_product->idproduct_master) {
                    if($product->idstore_warehouse === $selled_product->idstore_warehouse) {
                        $remaining_product = $product->total_quantity - $selled_product->total_quantity;
                        $product->remaining_product = abs($remaining_product);
                        break;
                    } else {
                        $remaining_product = $product->total_quantity;
                        $product->remaining_product = abs($remaining_product);
                    }
                }
            }
        }
        
        $data = [];
        $data['critical_products'] = $stock_levels_report_data->whereBetween('remaining_product',[1,10]);
        $data['replenishment_products'] = $stock_levels_report_data->where('remaining_product', 0);

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data], 200);                            
    }

    public function get_selled_quantity($id)
    {
        $selled_quantity = DB::table('vendor_purchases')
                                        ->rightJoin('vendor_purchases_detail', 'vendor_purchases_detail.idvendor_purchases', '=', 'vendor_purchases.idvendor_purchases') 
                                        ->select('vendor_purchases.idstore_warehouse', 'vendor_purchases_detail.idproduct_master', DB::raw('sum(vendor_purchases_detail.quantity) as total_quantity'))
                                        ->groupBy('vendor_purchases.idstore_warehouse', 'vendor_purchases_detail.idproduct_master')
                                        ->where('vendor_purchases_detail.idproduct_master', $id)
                                        ->get();  
        return $selled_quantity;                                
    }

    public function inventory_forecasting_report(Request $request)
    {
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $limit = !empty($request->limit) ? $request->limit : 25;

        $data = DB::table('inventory')
                    ->rightJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                    ->select('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'inventory.created_at As Date', DB::raw('sum(inventory.quantity) as selled_quantity'))
                    ->groupBy('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'inventory.created_at');

        if(!empty($start_date) && !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }         
        if(!empty($request->idstore_warehouse)) {
            $data->where('idstore_warehouse', $request->idstore_warehouse);
        }                       
        
        $inventory_forecasting_report = $data->paginate(2)->toArray();
        $inventory_forecasting_report = $this->forecasting_data_formatting($inventory_forecasting_report);

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $inventory_forecasting_report], 200);                                
    }              
    
    public function forecasting_data_formatting($data)
    {
        $transformedData = [];
        // dd($data);

        foreach ($data['data'] as $item) {
            $idstore_warehouse = $item->idstore_warehouse;
            $warehouse_name = $this->get_warehouse_name($idstore_warehouse);
            
            $key = "{$item->idstore_warehouse}";
            if (!isset($transformedData[$key])) {
                $transformedData[$key] = [
                    'idstore_warehouse' => $idstore_warehouse,
                    'warehouse_name' => $warehouse_name,
                    'products' => [],
                ];
            }

            $transformedData[$key]['products'][] = [
                'idproduct_master' => $item->idproduct_master,
                'product_name' => $item->name,
                'Date' => $item->Date,
                'selled_quantity' => $item->selled_quantity,
            ];
        }

        $transformedData = array_values($transformedData);
        $transformedData['current_page'] = $data['current_page'];
        $transformedData['first_page_url'] = $data['first_page_url'];
        $transformedData['from'] = $data['from'];
        $transformedData['last_page'] = $data['last_page'];
        $transformedData['last_page_url'] = $data['last_page_url'];
        $transformedData['links'] = $data['links'];
        $transformedData['next_page_url'] = $data['next_page_url'];
        $transformedData['path'] = $data['path'];
        $transformedData['per_page'] = $data['per_page'];
        $transformedData['prev_page_url'] = $data['prev_page_url'];
        $transformedData['to'] = $data['to'];
        $transformedData['total'] = $data['total'];
        return $transformedData;
    }
}
