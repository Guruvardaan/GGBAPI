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
                if(!empty($vendor_data)) {
                    $inventory->selled_product = $vendor_data->quantity;
                    $inventory->remaining_quanity = $inventory->total_quantity - $vendor_data->quantity;
                    $inventory->expire = $vendor_data->expiry;
                }

                if(!empty($expiry_data)) {
                    $expiry_data->amount = $expiry_data->mrp * $expiry_data->quantity;
                    $inventory->expiry_report = $expiry_data;
                }

                if($product_data) {
                    $inventory->product_name = $product_data->name;
                    $inventory->product_barcode = $product_data->barcode;
                    $inventory->category = $product_data->category_name;
                    $inventory->sub_category = $product_data->sub_category_name;
                    $inventory->sub_sub_category = $product_data->sub_sub_category_name;
                    $inventory->brands = $product_data->brands_name;
                }
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
                                'category.idcategory',
                                'sub_category.name As sub_category_name',
                                'sub_category.idsub_category',
                                'sub_sub_category.name AS sub_sub_category_name',
                                'sub_sub_category.idsub_sub_category',
                                'brands.name As brands_name',
                                'brands.idbrand',
                            )
                            ->first();
        return $product_data;
    }

    public function expried_and_expiring_inventory(Request $request)
    {
        $ids = $this->get_product_ids();
    
        $inventories_data = DB::table('product_master')
        ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
        ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
        ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
        ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
        // ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
        ->select('category.idcategory', 'sub_category.idsub_category', 'sub_sub_category.idsub_sub_category', 'product_master.idproduct_master', 'inventory.idstore_warehouse', 'inventory.quantity As total_quantity')
        ->whereIn('inventory.idproduct_master', $ids)
        ->groupBy('category.idcategory', 'sub_category.idsub_category', 'sub_sub_category.idsub_sub_category', 'product_master.idproduct_master', 'inventory.idstore_warehouse', 'inventory.quantity');

        $inventories = $inventories_data->get();

        foreach($inventories as $inventory) {
            $expired_data = $this->get_expired_product($inventory->idproduct_master);
            $expiring_data = $this->get_expiring_in_30days($inventory->idproduct_master);
            $product_data = $this->get_product_data($inventory->idproduct_master);
            if(!empty($product_data)) {
                $inventory->product_name = $product_data->name;
            }
            $inventory->expried_amount = 0;
            $inventory->expiring_in_30days_amount = 0;
            $x_value_expried = 0;
            $y_value_expried = 0;
            $x_value_expiring = 0;
            $y_value_expiring = 0;
            if(!empty($expired_data)) {
                $remaining_quanity = $inventory->total_quantity - $expired_data->quantity;
                $inventory->expried_amount = abs($remaining_quanity * $expired_data->mrp);
                $x_value_expried = abs($expired_data->mrp);
                $y_value_expried = abs($remaining_quanity);
            }
            if(!empty($expiring_data)) {
                $remaining_quanity = $inventory->total_quantity - $expiring_data->quantity;
                $inventory->expiring_in_30days_amount = abs($remaining_quanity * $expiring_data->mrp);
                $x_value_expiring = abs($remaining_quanity);
                $y_value_expiring = abs($expiring_data->mrp);
            }
            
            $inventory->expired_graph_data = ['x_value' => $x_value_expried, 'y_value' => $y_value_expried];
            $inventory->expiring_graph_data = ['x_value' => $x_value_expiring, 'y_value' => $y_value_expiring];
        }

        $inventories = $this->data_formatting($inventories);

        return response()->json(["statusCode" => 0, "message" => "Success", "data" => $inventories], 200);
    }

    public function get_expired_product($id) {
        $expiredData = DB::table('vendor_purchases_detail')->select('quantity', 'mrp', 'expiry')->where('idproduct_master', $id)->where('expiry', '<', now()->toDateString())->first();
        return $expiredData;
    }

    public function get_expiring_in_30days($id) {
        $expiredData = DB::table('vendor_purchases_detail')->select('quantity', 'mrp', 'expiry')->where('idproduct_master', $id)->where('expiry', '>', now()->toDateString())->where('expiry', '<', now()->addDays(30))->first();
        return $expiredData;
    }

    public function get_product_ids()
    {
        $expiredProducts = DB::table('vendor_purchases_detail')
            ->select('idproduct_master')
            ->where('expiry', '<', now()->toDateString())
            ->where('expiry', '<>', '')
            ->get();
        $expiringProducts = DB::table('vendor_purchases_detail')
            ->select('idproduct_master')
            ->where('expiry', '>', now()->toDateString())
            ->where('expiry', '<', now()->addDays(30))
            ->get();    
        
        foreach($expiredProducts as $expiredProduct) {
            $ids[] = $expiredProduct->idproduct_master;
        }

        foreach($expiringProducts as $expiringProduct) {
            $ids[] = $expiringProduct->idproduct_master;
        }

        return $ids;
    }

    public function data_formatting($data)
    {
        $transformedData = [];

        foreach ($data as $item) {
            $idcategory = $item->idcategory;
            $idsub_category = $item->idsub_category;
            $idsub_sub_category = $item->idsub_sub_category;

            $key = "{$idcategory}-{$idsub_category}-{$idsub_sub_category}";
            if (!isset($transformedData[$key])) {
                $transformedData[$key] = [
                    'idcategory' => $idcategory,
                    'idsub_category' => $idsub_category,
                    'idsub_sub_category' => $idsub_sub_category,
                    'products' => [],
                ];
            }

            $transformedData[$key]['products'][] = [
                'idproduct_master' => $item->idproduct_master,
                'idstore_warehouse' => $item->idstore_warehouse,
                'total_quantity' => $item->total_quantity,
                'product_name' => $item->product_name,
                'expried_amount' => $item->expried_amount,
                'expiring_in_30days_amount' => $item->expiring_in_30days_amount,
                'expired_graph_data' => $item->expired_graph_data,
                'expiring_graph_data' => $item->expiring_graph_data,
            ];
        }

        $transformedData = array_values($transformedData);

        return $transformedData;
    }

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
        $data['get_best_seller'] =  $get_best_seller;
        $data['get_worst_seller'] = $get_worst_seller;
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
}
