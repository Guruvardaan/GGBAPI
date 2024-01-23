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
        $limit = !empty($request->limit) ? $request->limit : 25; 

        $profitability = DB::table('inventory')
                         ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
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
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $limit = !empty($request->limit) ? $request->limit : 25;

        $data = DB::table('inventory')
                            ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
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
        ini_set('max_execution_time', 14000);
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        $limit = !empty($request->rows) ? $request->rows : 20;
        $skip = !empty($request->first) ? $request->first : 0;

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
        
        $totalRecords = $data->count();
        $limit = abs($limit - $skip);
        $stock_levels_report_data = $data->skip($skip)->take($limit)->get();
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

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $data, "total" => $totalRecords], 200);                            
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
                    ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory.idproduct_master')
                    ->select('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'inventory.created_at As Date', DB::raw('sum(inventory.quantity) as selled_quantity'))
                    ->groupBy('inventory.idproduct_master', 'product_master.name', 'inventory.idstore_warehouse', 'inventory.created_at');

        if(!empty($start_date) && !empty($end_date)) {
            $data->whereBetween('inventory.created_at',[$start_date, $end_date]);
        }         
        if(!empty($request->idstore_warehouse)) {
            $data->where('idstore_warehouse', $request->idstore_warehouse);
        }                       
        
        $inventory_forecasting_report = $data->paginate($limit)->toArray();
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

    public function get_sales_report(Request $request)
    {
        ini_set('max_execution_time', 14000);
        $limit = !empty($request->rows) ? $request->rows : 50;
        $skip = !empty($request->first) ? $request->first : 0;
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;

        $data = DB::table('customer_order')
                 ->join('users','users.id','=','customer_order.idcustomer')
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

        $totalRecords = $data->get()->count();
        $limit = abs($limit - $skip);
        $sales_report_data = $data->skip($skip)->take($limit)->get(); 

        foreach($sales_report_data as $sales) {
            $oreder_details = $this->get_oreder_details($sales->idcustomer_order);
            $sales->oreder_details = $oreder_details;
        }                     

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $sales_report_data, 'total' => $totalRecords], 200);                           
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
        $limit = !empty($request->rows) ? $request->rows : 50;
        $skip = !empty($request->first) ? $request->first : 0;
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;



        $data =  DB::table('product_master')
                ->leftJoin('product_batch', 'product_batch.idproduct_master', '=', 'product_master.idproduct_master')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                // ->leftJoin
                ->select(
                    'product_master.idproduct_master',
                    'product_batch.idstore_warehouse',
                    'product_master.idcategory',
                    'category.name As category_name',
                    'product_master.idsub_category',
                    'sub_category.name as sub_category_name',
                    'product_master.idsub_sub_category',
                    'sub_sub_category.name AS sub_sub_category_name',
                    'product_master.idbrand',
                    'brands.name As brand_name',
                    'product_master.name',
                    'product_master.barcode',
                    'product_batch.purchase_price AS purchase_price'        
                );    
        if(!empty($request->field) && $request->field=="brand"){
             $data->where('brands.name', 'like', $request->searchTerm . '%');
        }
         if(!empty($request->field) && $request->field=="category"){
             $data->where('category.name', 'like', $request->searchTerm . '%');
        }
         if(!empty($request->field) && $request->field=="sub_category"){
             $data->where('sub_category.name', 'like', $request->searchTerm . '%');
        }
         if(!empty($request->field) && $request->field=="barcode"){
             $barcode=$request->searchTerm;
            $data->where('product_master.barcode', 'like', $barcode . '%');
        }
        
        if(!empty($start_date) &&  !empty($end_date)) {
            $data->whereBetween('product_master.created_at',[$start_date, $end_date]);
        }
        if(!empty($request->idstore_warehouse)) {
            $data->where('product_batch.idstore_warehouse', $request->idstore_warehouse);
        }
        if(!empty($request->field) && $request->field=="product"){
            $data->where('product_master.name', 'like', $request->searchTerm . '%');
        }

        $totalRecords = $data->get()->count();
        $limit = abs($limit - $skip);
        $cogs_report = $data->skip($skip)->take($limit)->get(); 

        foreach($cogs_report as $product) {
            $inventory = $this->get_quantity($product->idproduct_master);
            $product->total_quantity = 0;
            $product->cogs = 0;
            if(!empty($inventory)) {
                $product->total_quantity = $inventory->total_quantity;
                $product->cogs = round($inventory->total_quantity * $inventory->purchase_price, 2);   
            }
        }

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $cogs_report, 'total'=> $totalRecords], 200);
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
        $limit = !empty($request->rows) ? $request->rows : 50;
        $skip = !empty($request->first) ? $request->first : 0;
        $start_date =  !empty($request->start_date) ? $request->start_date : null;
        $end_date = !empty($request->end_date)? $request->end_date :  null;
        // dd($request->field);

        $data = DB::table('vendor_purchases_detail')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')   
                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->select(
                    'inventory.idstore_warehouse',
                    'inventory.idproduct_master',
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
                    'inventory.purchase_price',
                    DB::Raw('inventory.purchase_price * vendor_purchases_detail.quantity As amount')
                );

        if(!empty($request->field) && $request->field=="product"){
            $data->where('product_master.name', 'like', $request->searchTerm . '%');
        }
        if(!empty($request->field) && $request->field=="brand"){
            $data->where('brands.name', 'like', $request->searchTerm . '%');
        }
        if(!empty($request->field) && $request->field=="category"){
                    $data->where('category.name', 'like', $request->searchTerm . '%');
        }
        if(!empty($request->field) && $request->field=="sub_category"){
            $data->where('sub_category.name', 'like', $request->searchTerm . '%');
        }  
        if(!empty($start_date) &&  !empty($end_date)) {
             $data->whereBetween('vendor_purchases_detail.created_at',[$start_date, $end_date]);
        }     
        if(!empty($request->idstore_warehouse)) {
            $data->where('inventory.idstore_warehouse', $request->idstore_warehouse);
        }

        $totalRecords = $data->get()->count();
        $limit = abs($limit - $skip);
        $purchase_order_report = $data->skip($skip)->take($limit)->get(); 
        $gross_total = 0;
        foreach($purchase_order_report as $product) {
            $cgst = 0;
            $sgst = 0;
            $product->amount = round($product->amount, 2);
            if(!empty($product->cgst)) {
                $cgst = $product->amount * ($product->cgst/100);
            }
            if(!empty($product->sgst)) {
                $sgst = $product->amount * ($product->sgst/100);
            }

            $total_amount_with_tax = $product->amount + $sgst + $cgst;
            $product->total_amount_with_tax = round($total_amount_with_tax, 2);
            $gross_total += $total_amount_with_tax;
        }
        $purchase_order_report['gross_total'] = round($gross_total, 2);

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $purchase_order_report, 'total' => $totalRecords], 200);        
    }

}