<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SystemReportController extends Controller
{
    public function get_performance_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : Carbon::now()->startOfMonth()->format('Y-m-d');;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  Carbon::now()->format('Y-m-d');
        $data =  DB::table('product_master')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->select(
                    'product_master.idproduct_master',
                    'product_master.name',
                    'product_master.barcode',
                    'inventory.idstore_warehouse',
                    'inventory.purchase_price AS purchase_price'         
                );

        $idstore_warehouse = 1;        
        if(!empty($_GET['idstore_warehouse'])) {
            $data->where('inventory.idstore_warehouse', $_GET['idstore_warehouse']);
            $idstore_warehouse = $_GET['idstore_warehouse'];
        }        

        $product_data = $data->get()->chunk(10000);
        $product_max = [];
        $product_min = [];

        foreach($product_data as $product){
            foreach($product as $item) {
                $cogs = $this->product_wise_cogs($start_date, $end_date, $item->idproduct_master, $idstore_warehouse);
                $item->cogs = $cogs['cogs'];
                $item->inventory_turnover_ratio = $cogs['inventory_turnover_ratio'];
            }
            $max_inventory_turnover_ratio = $product->max('inventory_turnover_ratio');
            $max_data = $product->where('inventory_turnover_ratio', $max_inventory_turnover_ratio)->first();
            $product_max[] = $max_data;
        
            $min_inventory_turnover_ratio = $product->min('inventory_turnover_ratio');
            $min_data =  $product->where('inventory_turnover_ratio', $min_inventory_turnover_ratio)->first();
            $product_min[] = $min_data;
        }

        $max_ratios = array_column($product_max, 'inventory_turnover_ratio');
        $maxIndex = array_keys($max_ratios, max($max_ratios))[0];
        $top_seller = $product_max[$maxIndex];


        $min_ratios = array_column($product_min, 'inventory_turnover_ratio');
        $minIndex = array_keys($min_ratios, min($min_ratios))[0];
        $worst_seller = $product_min[$minIndex];
    
        $perfomance_data['top_seller'] = $top_seller;
        $perfomance_data['worst_seller'] = $worst_seller;
        if(empty($top_seller) && empty($worst_seller)) {
            $perfomance_data = [];
        }
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $perfomance_data], 200);                                   
    }

    public function product_wise_cogs($start_date, $end_date, $idproduct_master, $idstore_warehouse) {
        $order_data = DB::table('product_master')
                ->leftJoin('order_detail', 'order_detail.idproduct_master', '=', 'product_master.idproduct_master')
                ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                ->leftJoin('vendor_purchases_detail', 'vendor_purchases_detail.idproduct_master', '=', 'product_master.idproduct_master')
                ->select('product_master.idproduct_master', DB::raw('sum(order_detail.quantity) as order_total_quantity'), DB::raw('sum(order_detail.quantity) as order_total_quantity'), DB::raw('sum(vendor_purchases_detail.quantity) as purchase_total_quantity'), 'vendor_purchases_detail.unit_purchase_price as purchase_price', )
                ->groupBy('product_master.idproduct_master', 'vendor_purchases_detail.unit_purchase_price')
                ->where('product_master.idproduct_master', $idproduct_master)
                ->where('customer_order.idstore_warehouse', $idstore_warehouse)
                ->whereBetween('customer_order.created_at',[$start_date, $end_date])
                ->whereBetween('vendor_purchases_detail.created_at',[$start_date, $end_date])
                ->first();   
        $inventory_data = DB::table('inventory')
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                ->select('product_master.idproduct_master', DB::raw('sum(inventory.quantity) as total_quantity'), 'inventory.purchase_price')
                ->groupBy('product_master.idproduct_master', 'inventory.idstore_warehouse', 'inventory.purchase_price')
                ->where('inventory.idproduct_master', $idproduct_master)
                ->where('inventory.idstore_warehouse', $idstore_warehouse)
                ->first();               
        $beginning_inventory = 0;     
        $purchase_record = 0; 
        $beginning_quantity = 0;
        if(!empty($order_data)) {
            $sales_record = !empty($order_data->order_total_quantity) ? $order_data->order_total_quantity * $order_data->purchase_total_quantity : 0;
            $purchase_record = !empty($order_data->purchase_total_quantity) ? $order_data->purchase_total_quantity * $order_data->purchase_total_quantity : 0;
            $beginning_inventory = abs($purchase_record - $sales_record);
            $beginning_quantity =  abs($order_data->order_total_quantity - $order_data->purchase_total_quantity);
        }

        $inventory = 0;
        $inventory_quantity = 0;
        if(!empty($inventory_data)) {
            $inventory = $inventory_data->total_quantity * $inventory_data->purchase_price;
            $inventory_quantity = $inventory_data->total_quantity;
        }

        $cogs = abs($beginning_inventory + $purchase_record - $inventory);
        $avg_inventory = ($beginning_quantity + $inventory_quantity) / 2;
        $inventory_turnover_ratio = (!empty($avg_inventory) && !empty($cogs)) ? $cogs/$avg_inventory : 0;
        $data = [
            'cogs' => round($cogs, 2),
            'inventory_turnover_ratio' => round($inventory_turnover_ratio, 2)
        ];
        return $data;
    }

    public function get_year_over_year_growth() 
    {
        ini_set('max_execution_time', 14000);
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 50;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $year = !empty($_GET['year']) ? $_GET['year'] : date('Y'); 
        $start_date = $year . '-01-01';
        $end_date = (date('Y') === $year) ?  Carbon::now()->format('Y-m-d') : $year . '-12-31';
        $diff = strtotime($end_date) - strtotime($start_date);
        $days = abs(round($diff / 86400));

        $data =  DB::table('product_master')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->select(
                    'product_master.idproduct_master',
                    'inventory.idstore_warehouse',
                    'category.name As category_name',
                    'sub_category.name as sub_category_name',
                    'sub_sub_category.name AS sub_sub_category_name',
                    'brands.name As brand_name',
                    'product_master.name',
                    'product_master.barcode',
                    'inventory.purchase_price AS purchase_price'        
                );    
        if(!empty($_GET['field']) && $_GET['field']=="brand"){
             $data->where('brands.name', 'like', $_GET['searchTerm'] . '%');
        }
         if(!empty($_GET['field']) && $_GET['field']=="category"){
             $data->where('category.name', 'like', $_GET['searchTerm'] . '%');
        }
        if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
             $data->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
        }
         if(!empty($_GET['field']) && $_GET['field']=="barcode"){
             $barcode=$_GET['searchTerm'];
            $data->where('product_master.barcode', 'like', $barcode . '%');
        }
        if(!empty($_GET['idstore_warehouse'])) {
            $data->where('inventory.idstore_warehouse', $_GET['idstore_warehouse']);
        }
        if(!empty($_GET['field']) && $_GET['field']=="product"){
            $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
        }

        $totalRecords = $data->count();
        $limit = abs($limit - $skip);
        $get_year_over_year_data = $data->skip($skip)->take($limit)->get();
        
        foreach($get_year_over_year_data as $product){
            $cogs = $this->get_COGS($start_date, $end_date, $product->idproduct_master, $product->purchase_price, $product->idstore_warehouse);
            $avg_inventory = ($cogs['beginning_inventory'] + $cogs['quantity'])/2; 
            $cogs_value = $cogs['cogs'];
            $inventory_turnover_ratio = !empty($avg_inventory) ? $cogs_value/$avg_inventory : 0;
            $growth = (!empty($inventory_turnover_ratio)) ? $days/$inventory_turnover_ratio : 0;
            $product->growth = round($growth, 2);
        }
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $get_year_over_year_data, 'total'=> $totalRecords], 200);
      
    }

    public function get_seller_detail($id) 
    {
       $seller_data =  DB::table('vendor')
                       ->select('idvendor','name', 'phone')
                       ->where('idvendor', $id)
                       ->first();
       return $seller_data;                
    }

    
    public function get_inventory_profitability_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $limit = !empty($request->rows) ? $request->rows : 20;
        $skip = !empty($request->first) ? $request->first : 0;

        $data = DB::table('inventory')
                         ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                         ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                         ->select('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                         ->groupBy('inventory.idproduct_master', 'product_master.name', 'product_batch.purchase_price', 'product_batch.selling_price');

        $total = $data->paginate(20)->total();
        $profitability = $data->skip($skip)->take($limit)->get();
        foreach($profitability as $product) {
            $product->profit_report['sku_profit'] =  round(($product->selling_price - $product->purchase_price) * $product->total_quantity, 3);
            $product->profit_report['listing_profit']['gross_margin'] = round($product->selling_price - $product->purchase_price, 3);
            $product->profit_report['listing_profit']['unit_margin'] = round(($product->selling_price - $product->purchase_price)/$product->total_quantity, 3);
            $product->profit_report['trending_profit'] = $this->get_trending_profitability($product->idproduct_master, $start_date, $end_date);
        }
        $total = $profitability->paginate(20)->total();
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
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 10;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;

        $data = DB::table('product_master')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'inventory.idproduct_master')
                ->select('inventory.idproduct_master' ,'product_master.name', 'product_master.barcode',  'product_batch.purchase_price', 'product_batch.selling_price', 'inventory.created_at', 'inventory.quantity As total_quantity');
        if(!empty($_GET['idstore_warehouse'])) {
            $data->where('inventory.idstore_warehouse', $_GET['idstore_warehouse']);
        } 

        if(!empty($start_date) &&  !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }

        if(!empty($_GET['field']) && $_GET['field']=="product"){
            $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
        }

        if(!empty($_GET['field']) && $_GET['field']=="barcode"){
            $barcode=$_GET['searchTerm'];
           $data->where('product_master.barcode', 'like', $barcode . '%');
        }

        $totalRecords = $data->count();
        $limit = abs($limit - $skip);
        $value_report_data =  $data->skip($skip)->take($limit)->get();
        foreach($value_report_data as $item) {
            $snapshot_value = round($item->total_quantity * $item->purchase_price, 2);
            $item->snapshot_value = !empty($snapshot_value) ? $snapshot_value : 0;
            $performance_report['value'] = $item->purchase_price;
            $performance_report['turnover_ratio'] = round($item->total_quantity > 0 ? $item->purchase_price / $item->total_quantity : 0, 2);
            $item->performance_report = $performance_report;
        }     
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $value_report_data, 'total' => $totalRecords], 200);                    
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
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($_GST['start_date']) ? $_GST['start_date'] : null;
        $end_date = !empty($_GST['end_date'])? $_GST['end_date'] :  null;
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 20;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $type = !empty($_GET['type']) ? $_GET['type']  : 'critical_products';

        try {
            $data = DB::table('inventory')
                           ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                           ->select('inventory.idproduct_master', 'product_master.name', 'product_master.barcode', 'inventory.idstore_warehouse', DB::raw('sum(inventory.quantity)/2 as total_quantity'))
                           ->groupBy('inventory.idproduct_master', 'product_master.name', 'product_master.barcode', 'inventory.idstore_warehouse');
                                    
            if(!empty($request->idstore_warehouse)) {
                $data->where('inventory.idstore_warehouse', $request->idstore_warehouse);
            }

            if(!empty($start_date) &&  !empty($end_date)) {
                $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
            }

            if(!empty($_GET['field']) && $_GET['field']=="product"){
                $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
            }

            if(!empty($_GET['field']) && $_GET['field']=="barcode"){
                $barcode=$_GET['searchTerm'];
                $data->where('product_master.barcode', 'like', $barcode . '%');
            }
        
            $stock_levels_report_data = $data->get();
            $productIds = $stock_levels_report_data->pluck('idproduct_master')->unique()->toArray();
            $selled_products = $this->get_selled_quantity($productIds);

            $stock_levels_report_data->each(function ($product) use ($selled_products) {
                $remaining_product = 0;
                foreach ($selled_products as $selled_product) {
                    if ($product->idproduct_master === $selled_product->idproduct_master && $product->idstore_warehouse === $selled_product->idstore_warehouse) {
                        $remaining_product = $product->total_quantity - $selled_product->total_quantity;
                        break;
                    }
                }
                $product->remaining_product = abs($remaining_product);
            });
            
        
            $data = [];
            if($type === 'critical_products') {
                $data = $stock_levels_report_data->whereBetween('remaining_product',[1,10])->skip($skip)->take($limit)->values();
                $total = $stock_levels_report_data->whereBetween('remaining_product',[1,10])->count();
            }

            if($type === 'replenishment_products') {
                $data = $stock_levels_report_data->where('remaining_product', 0)->skip($skip)->take($limit)->values();
                $total = $stock_levels_report_data->where('remaining_product', 0)->count();
            }
            return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data, "total" => $total], 200);                            
        
        } catch (QueryException $e) {
           
        }

        
    }

    public function get_selled_quantity($id)
    {
        $selled_quantity = DB::table('vendor_purchases')
                                        ->join('vendor_purchases_detail', 'vendor_purchases_detail.idvendor_purchases', '=', 'vendor_purchases.idvendor_purchases') 
                                        ->select('vendor_purchases.idstore_warehouse', 'vendor_purchases_detail.idproduct_master', DB::raw('sum(vendor_purchases_detail.quantity) as total_quantity'))
                                        ->groupBy('vendor_purchases.idstore_warehouse', 'vendor_purchases_detail.idproduct_master')
                                        ->whereIn('vendor_purchases_detail.idproduct_master', $id)
                                        ->get();  
        return $selled_quantity;                                
    }

    public function inventory_forecasting_report(Request $request)
    {
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 10;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;

        $data = DB::table('inventory')
                    ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                    ->leftJoin('order_detail', 'order_detail.idproduct_master', '=', 'inventory.idproduct_master')
                    ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                    ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                    ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                    ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                    ->select('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'category.name As category_name', 'sub_category.name as sub_category_name', 'sub_sub_category.name AS sub_sub_category_name', 'brands.name As brand_name', 'inventory.created_at As Date', DB::raw('sum(inventory.quantity) as remaining_quantity'), DB::raw('sum(order_detail.quantity) as selled_quantity'))
                    ->groupBy('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'inventory.created_at','category.name', 'sub_category.name', 'sub_sub_category.name', 'brands.name');

        if(!empty($start_date) && !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }         
        if(!empty($request->idstore_warehouse)) {
            $data->where('idstore_warehouse', $request->idstore_warehouse);
        }
        
        if(!empty($_GET['field']) && $_GET['field']=="product"){
            $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
        }

        if(!empty($_GET['field']) && $_GET['field']=="brand"){
            $data->where('brands.name', 'like', $_GET['searchTerm'] . '%');
       }
        if(!empty($_GET['field']) && $_GET['field']=="category"){
            $data->where('category.name', 'like', $_GET['searchTerm'] . '%');
       }
        if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
            $data->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
       }
       if(!empty($_GET['field']) && $_GET['field']=="barcode"){
            $barcode=$_GET['searchTerm'];
           $data->where('product_master.barcode', 'like', $barcode . '%');
       }
       

        $totalRecords = $data->get()->count();
        $limit = abs($limit - $skip);
        $inventory_forecasting_report =  $data->skip($skip)->take($limit)->get();

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $inventory_forecasting_report, 'total' => $totalRecords], 200);                                
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

    public function get_sales_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 50;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $report_type = !empty($_GET['report_type']) ? $_GET['report_type'] : 'artical_wise';
        
        $final_data = [];

        if($report_type === 'product_wise') {
            $data = DB::table('order_detail')
                            ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                            ->select('product_master.name', DB::raw('sum(order_detail.quantity) as units_sold'), 'order_detail.total_price As price')
                            ->groupBy('product_master.name','order_detail.total_price');

            if(!empty($_GET['searchTerm'])) {
                $data->where('product_master.name', 'like', $_GET['searchTerm']. '%');
            }   
            
            if(!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $data->whereBetween('customer_order.created_at',[$_GET['start_date'], $_GET['end_date']]);
            } 

            if(!empty($_GET['idstore_warehouse'])) {
                $data->where('customer_order.idstore_warehouse', $_GET['idstore_warehouse']);
            }

            $product_data = $data->skip($skip)->take($limit)->get();             
            foreach($product_data as $product) {
                $product->revenue = $product->units_sold * $product->price;
            } 
            $final_data = $product_data;
            $totalRecords = $data->paginate(20)->total();             
        } else if($report_type === 'category_wise') {
            $data = DB::table('order_detail')
                            ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                            ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                            ->select('category.name', DB::raw('sum(order_detail.quantity) as units_sold'), 'order_detail.total_price As price', 'category.idcategory')
                            ->groupBy('category.name','order_detail.total_price', 'category.idcategory');

            if(!empty($_GET['searchTerm'])) {
                $data->where('category.name', 'like', $_GET['searchTerm']. '%');
            }

            if(!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $data->whereBetween('customer_order.created_at',[$_GET['start_date'], $_GET['end_date']]);
            } 

            if(!empty($_GET['idstore_warehouse'])) {
                $data->where('customer_order.idstore_warehouse', $_GET['idstore_warehouse']);
            }

            $categoty_wise_data = $data->skip($skip)->take($limit)->get();              
            foreach($categoty_wise_data as $product) {
                $product->revenue = $product->units_sold * $product->price;
            } 

            $processedData = [];

            foreach ($categoty_wise_data as $item) {
                $itemName = $item->name;
                if (!isset($processedData[$itemName])) {
                    $processedData[$itemName] = [
                        "name" => $itemName,
                        "units_sold" => 0,
                        "revenue" => 0
                    ];
                }   

                $processedData[$itemName]["units_sold"] += $item->units_sold;
                $processedData[$itemName]["revenue"] += $item->revenue;
            }
            $final_data = array_values($processedData);
            $totalRecords = sizeof($final_data);             
        } else if($report_type === 'top_selling') {
             $data = DB::table('order_detail')
                            ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                            ->select(DB::raw('count(product_master.name) as rank' ), 'product_master.name', DB::raw('sum(order_detail.quantity) as units_sold'), 'order_detail.total_price As price')
                            ->groupBy('product_master.name','order_detail.total_price')
                            ->orderBy('units_sold', 'desc');

            if(!empty($_GET['searchTerm'])) {
                $data->where('product_master.name', 'like', $_GET['searchTerm']. '%');
            }
            
            if(!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $data->whereBetween('customer_order.created_at',[$_GET['start_date'], $_GET['end_date']]);
            } 

            if(!empty($_GET['idstore_warehouse'])) {
                $data->where('customer_order.idstore_warehouse', $_GET['idstore_warehouse']);
            }
            
            $product_data = $data->skip($skip)->take($limit)->get();  
            $rank = 1;           
            foreach($product_data as $product) {
                $product->revenue = $product->units_sold * $product->price;
                $product->rank = $rank;
                $rank = $rank + 1;
            } 
            $final_data = $product_data;
            $totalRecords = $data->paginate(20)->total();
        } else if($report_type === 'inventory_status') {
            $data = DB::table('order_detail')
                           ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                           ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                           ->select('product_master.name', 'order_detail.idproduct_master')
                           ->groupBy('product_master.name', 'order_detail.idproduct_master');

           if(!empty($_GET['searchTerm'])) {
               $data->where('product_master.name', 'like', $_GET['searchTerm']. '%');
           }
           
           if(!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
               $data->whereBetween('customer_order.created_at',[$_GET['start_date'], $_GET['end_date']]);
           } 

           if(!empty($_GET['idstore_warehouse'])) {
               $data->where('customer_order.idstore_warehouse', $_GET['idstore_warehouse']);
           }
           
           $product_data = $data->skip($skip)->take($limit)->get();
           $totalRecords = $data->paginate(20)->total();            
           foreach($product_data as $product) {
               $inventory = DB::table('inventory')->select('quantity', 'mrp')->where('idproduct_master', $product->idproduct_master);
               if(!empty($_GET['idstore_warehouse'])) {
                $inventory->where('idstore_warehouse', $_GET['idstore_warehouse']);
               }
               $inventory_data = $inventory->get();
               $stock_quantity = 0;
               $stock_value = 0;
               foreach($inventory_data as $data) {
                 $stock_quantity += $data->quantity;
                 $stock_value += ($data->quantity * $data->mrp);
               }
               $product->stock_quantity = $stock_quantity;
               $product->stock_value = $stock_value;
           } 
           $final_data = $product_data;
       } else {
            $data = DB::table('customer_order')
                  ->leftJoin('users','users.id','=','customer_order.idcustomer')
                  ->join('store_warehouse','store_warehouse.idstore_warehouse','=','customer_order.idstore_warehouse')
                             ->select(
                                'customer_order.idcustomer_order',
                                'store_warehouse.name as store',
                                'users.name as name',
                                'customer_order.pay_mode',
                                'customer_order.total_quantity',
                                'customer_order.total_price',
                                'customer_order.total_cgst',
                                'customer_order.total_sgst',
                                'customer_order.total_discount',
                                'customer_order.discount_type',
                                'customer_order.created_at'
                             );
                             
            if(!empty($start_date) && !empty($end_date)) {
                $data->whereBetween('customer_order.created_at',[$start_date, $end_date]);
            } 

            if(!empty($request->idstore_warehouse)) {
                $data->where('customer_order.idstore_warehouse', $request->idstore_warehouse);
            }

            if(!empty($request->field) && $request->field=="pay_mode"){
                $data->where('customer_order.pay_mode', 'like', $request->searchTerm . '%');
            }

            if(!empty($request->field) && $request->field=="customer"){
                $data->where('users.name', 'like', $request->searchTerm . '%');
            }

            if(!empty($request->field) && $request->field=="discount_type"){
                $data->where('customer_order.discount_type', 'like', $request->searchTerm . '%');
            }

            $totalRecords = $data->paginate(20)->total();
            $limit = abs($limit - $skip);
            $sales_report_data = $data->skip($skip)->take($limit)->get(); 

            foreach($sales_report_data as $sales) {
                $oreder_details = $this->get_oreder_details($sales->idcustomer_order);
                $sales->oreder_details = $oreder_details;
            }
            $final_data = $sales_report_data;
        }                 

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $final_data, 'total' => $totalRecords], 200);                           
    }

    public function get_oreder_details($id)
    {
        $order_details = DB::table('order_detail')
                         ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                         ->select('order_detail.idproduct_master', 'product_master.name' ,'order_detail.quantity', 'order_detail.total_price', 'order_detail.total_sgst', 'order_detail.total_cgst', 'order_detail.discount')  
                         ->where('idcustomer_order', $id)   
                         ->get();                                  
        return $order_details;
    }

    public function get_cogs_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 50;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : Carbon::now()->startOfMonth()->format('Y-m-d');;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  Carbon::now()->format('Y-m-d');
        
        $data =  DB::table('product_master')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->select(
                    'product_master.idproduct_master',
                    'inventory.idstore_warehouse',
                    'category.name As category_name',
                    'sub_category.name as sub_category_name',
                    'sub_sub_category.name AS sub_sub_category_name',
                    'brands.name As brand_name',
                    'product_master.name',
                    'product_master.barcode',
                    'inventory.purchase_price AS purchase_price'        
                );    
        if(!empty($_GET['field']) && $_GET['field']=="brand"){
             $data->where('brands.name', 'like', $_GET['searchTerm'] . '%');
        }
         if(!empty($_GET['field']) && $_GET['field']=="category"){
             $data->where('category.name', 'like', $_GET['searchTerm'] . '%');
        }
         if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
             $data->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
        }
        if(!empty($_GET['field']) && $_GET['field']=="barcode"){
             $barcode=$_GET['searchTerm'];
            $data->where('product_master.barcode', 'like', $barcode . '%');
        }
        if(!empty($_GET['idstore_warehouse'])) {
            $data->where('inventory.idstore_warehouse', $_GET['idstore_warehouse']);
        }
        if(!empty($_GET['field']) && $_GET['field']=="product"){
            $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
        }

        $totalRecords = $data->count();
        $limit = abs($limit - $skip);
        $cogs_report = $data->skip($skip)->take($limit)->get();
        
        foreach($cogs_report as $product){
            $cogs = $this->get_COGS($start_date, $end_date, $product->idproduct_master, $product->purchase_price, $product->idstore_warehouse);
            $product->quantity = $cogs['quantity'];
            $product->cogs_value = $cogs['cogs'];

        }
        
        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $cogs_report, 'total'=> $totalRecords], 200);
    }

    public function get_COGS($start_date, $end_date, $product_id, $purchase_price, $idstore_warehouse)
    {
        $salse_data = DB::table('order_detail')
                ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                ->select('product_master.idproduct_master', DB::raw('sum(order_detail.quantity) as total_quantity'))
                ->groupBy('product_master.idproduct_master')
                ->where('product_master.idproduct_master', $product_id)
                ->whereBetween('customer_order.created_at',[$start_date, $end_date])
                ->first();
        
        $purchase_data = DB::table('vendor_purchases_detail')
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                ->select('product_master.idproduct_master', DB::raw('sum(vendor_purchases_detail.quantity) as total_quantity'), 'vendor_purchases_detail.unit_purchase_price as purchase_price')
                ->groupBy('product_master.idproduct_master', 'vendor_purchases_detail.unit_purchase_price')
                ->where('product_master.idproduct_master', $product_id)
                ->whereBetween('vendor_purchases_detail.created_at',[$start_date, $end_date])
                ->first();

        $inventory_data = DB::table('inventory')
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                ->select('product_master.idproduct_master', DB::raw('sum(inventory.quantity) as total_quantity'))
                ->groupBy('product_master.idproduct_master', 'inventory.idstore_warehouse', 'inventory.idstore_warehouse')
                ->where('product_master.idproduct_master', $product_id)
                ->where('inventory.idstore_warehouse', $idstore_warehouse)
                ->first();        
        $sales_record = !empty($salse_data) ?  $salse_data->total_quantity * $purchase_price : 0;
        $purchase_record = !empty($purchase_data) ?  $purchase_data->total_quantity * $purchase_data->purchase_price : 0;      
        $inventory = !empty($inventory_data) ?  $inventory_data->total_quantity * $purchase_price : 0;

        $beginning_inventory = abs($purchase_record - $sales_record);
        $cogs = $beginning_inventory + $purchase_record - $inventory;
        $beginning_quantity = (!empty($sales_record) ? $salse_data->total_quantity : 0 ) - (!empty($purchase_data) ? $purchase_data->total_quantity : 0 );
        $array = [
            'cogs' => abs($cogs),
            'quantity' => !empty($inventory_data) ?  $inventory_data->total_quantity : 0,
            'beginning_inventory' => abs($beginning_quantity),
        ];    
        return $array;
    }

    public function get_quantity($id) 
    {
        $inventory_quantity = DB::table('inventory')
                              ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                              ->select('inventory.purchase_price', DB::raw('sum(inventory.quantity) as total_quantity'))
                              ->groupBy('inventory.idproduct_master', 'inventory.purchase_price')
                              ->where('inventory.idproduct_master', $id)
                              ->first();
        return $inventory_quantity;                      
    }

    public function get_purchase_order_report(Request $request)
    {
        // $limit = !empty($_GET['limit']) ? $_GET['limit'] : 25;
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 50;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
      
        $data = DB::table('vendor_purchases_detail')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')   
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->leftJoin('vendor_purchases', 'vendor_purchases.idvendor_purchases', '=', 'vendor_purchases_detail.idvendor_purchases')
                ->leftJoin('vendor', 'vendor.idvendor', '=', 'vendor_purchases.idvendor')
                ->select(
                    'inventory.idstore_warehouse',
                    'inventory.idproduct_master',
                    'vendor.name AS vendor_name',
                    'product_master.name',
                    'product_master.idcategory',
                    'category.name As category_name',
                    'product_master.idsub_category',
                    'sub_category.name as sub_category_name',
                    'product_master.idsub_sub_category',
                    'sub_sub_category.name AS sub_sub_category_name',
                    'product_master.idbrand',
                    'brands.name As brand_name',
                    'vendor_purchases_detail.quantity',
                    'product_master.cgst',
                    'product_master.sgst',
                    'product_master.igst',
                    'vendor_purchases_detail.unit_purchase_price as purchase_price',
                    DB::Raw('vendor_purchases_detail.unit_purchase_price * vendor_purchases_detail.quantity As amount')
                );

        if(!empty($_GET['field']) && $_GET['field']=="product"){
            $data->where('product_master.name', 'like', $_GET['searchTerm'] . '%');
        }
        if(!empty($_GET['field']) && $_GET['field']=="brand"){
            $data->where('brands.name', 'like', $_GET['searchTerm'] . '%');
        }
        if(!empty($_GET['field']) && $_GET['field']=="category"){
                    $data->where('category.name', 'like', $_GET['searchTerm'] . '%');
        }
        if(!empty($_GET['field']) && $_GET['field']=="sub_category"){
            $data->where('sub_category.name', 'like', $_GET['searchTerm'] . '%');
        }  
        if(!empty($start_date) &&  !empty($end_date)) {
             $data->whereBetween('vendor_purchases_detail.created_at',[$start_date, $end_date]);
        }
        if(!empty($_GET['field']) && $_GET['field']=="vendor"){
            $data->where('vendor.name', 'like', $_GET['searchTerm'] . '%');
        }     
        if(!empty($_GET['idstore_warehouse'])) {
            $data->where('inventory.idstore_warehouse', $_GET['idstore_warehouse']);
        }

        if(!empty($_GET['field']) && $_GET['field']=="barcode"){
            $barcode=$_GET['searchTerm'];
           $data->where('product_master.barcode', 'like', $barcode . '%');
        }

        $totalRecords = $data->paginate(20)->total();
        $limit = abs($limit - $skip);
        $purchase_order_report = $data->skip($skip)->take($limit)->get(); 
        // dd($purchase_order_report);
        $gross_total = 0;
        foreach($purchase_order_report as $product) {
            $cgst = 0;
            $sgst = 0;
            $igst = 0;
            $product->amount = round($product->amount, 2);
            if(!empty($product->cgst)) {
                $cgst = $product->amount * ($product->cgst/100);
            }
            if(!empty($product->sgst)) {
                $sgst = $product->amount * ($product->sgst/100);
            }
            if(!empty($product->igst)) {
                $sgst = $product->amount * ($product->igst/100);
            }
            $product->cgst_amount = $cgst;
            $product->sgst_amount = $sgst;
            $product->igst_amount = $igst;

            $total_amount_with_tax = $product->amount + $sgst + $cgst;
            $product->total_amount_with_tax = round($total_amount_with_tax, 2);
            $gross_total += $total_amount_with_tax;
        }
        $purchase_order_report['gross_total'] = round($gross_total, 2);

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $purchase_order_report, 'total' => $totalRecords], 200);        
    }

}