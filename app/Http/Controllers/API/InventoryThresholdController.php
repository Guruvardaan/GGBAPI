<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use  App\Helpers\Helper;

class InventoryThresholdController extends Controller
{
    public function index()
    {
        $data =  DB::table('inventory_threshold')->select('*')->get();
        return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Geted Sucessfully.", "data" => $data], 200);
    }

    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'idproduct_master' => 'required|integer',
                'idstore_warehouse' => 'required|integer',
                'threshold_quantity' => 'required|integer',
                'sent_quantity' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } 
            
            $data = [
                'idproduct_master' => $request->idproduct_master,
                'idstore_warehouse' =>  $request->idstore_warehouse,
                'threshold_quantity' =>  $request->threshold_quantity,
                'sent_quantity' =>  $request->sent_quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ];
    
            $id = DB::table('inventory_threshold')->insertGetId($data);
            $createdData = [];
            if(!empty($id)) {
                $createdData = DB::table('inventory_threshold')->find($id);
            }
            
            return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Added Sucessfully.", "data" => $createdData], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $data = [];
        if(!empty($id)) {
            $data = DB::table('inventory_threshold')->find($id);
        }

        return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Geted Sucessfully.", "data" => $data], 200);
    }

    public function update(Request $request, string $id)
    {
        try{
            $validator = Validator::make($request->all(), [
                'idproduct_master' => 'required|integer',
                'idstore_warehouse' => 'required|integer',
                'threshold_quantity' => 'required|integer',
                'sent_quantity' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } 
            
            $data = [
                'idproduct_master' => $request->idproduct_master,
                'idstore_warehouse' =>  $request->idstore_warehouse,
                'threshold_quantity' =>  $request->threshold_quantity,
                'sent_quantity' =>  $request->sent_quantity,
                'updated_at' => now(),
            ];
    
            $update = DB::table('inventory_threshold')->where('id', $id)->update($data);
            if(empty($update)) {
                return response()->json(["statusCode" => 0, "message" => "Record Not Found"], 200);
            }

            $updatedData = [];
            if(!empty($id)) {
                $updatedData = DB::table('inventory_threshold')->find($id);
            }
            
            return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Updated Sucessfully.", "data" => $updatedData], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
             $delete = DB::table('inventory_threshold')->delete($id);
             if(empty($update)) {
                return response()->json(["statusCode" => 0, "message" => "Record Not Found"], 200);
            }
             return response()->json(["statusCode" => 0, "message" => "Inventory Threshold Deleted Sucessfully."], 200);
        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }     
    }

    public function get_inventory_threshold_products()
    {
        $idstore_warehouse = !empty($_GET['idstore_warehouse']) ? $_GET['idstore_warehouse'] : null;

        $threshold_data = DB::table('inventory_threshold')
                                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'inventory_threshold.idproduct_master')
                                ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'inventory_threshold.idproduct_master')
                                ->leftJoin('vendor_purchases_detail', 'vendor_purchases_detail.idproduct_master', '=', 'inventory_threshold.idproduct_master')
                                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                                ->select('inventory.idstore_warehouse', 'inventory_threshold.idproduct_master','product_master.name', 'product_master.barcode', 'brands.name As brand_name', 'inventory_threshold.threshold_quantity','vendor_purchases_detail.expiry', DB::raw('sum(inventory.quantity) as quantity'))
                                ->groupBy('inventory.idstore_warehouse', 'inventory_threshold.idproduct_master','product_master.name', 'product_master.barcode', 'brands.name', 'inventory_threshold.threshold_quantity','vendor_purchases_detail.expiry');
       
        if(!empty($idstore_warehouse)) {
            $threshold_data->where('inventory.idstore_warehouse', $idstore_warehouse);
        }

       $inventory_threshold = $threshold_data->get();                         
       $near_by_expried_products = $this->get_near_by_expried_product($idstore_warehouse); 
       $expiry_in_10days = [];
       $expiry_in_10days_threshold = [];
       foreach($near_by_expried_products as $product) {
        if(!empty($product->threshold_quantity) && $product->quantity <= $product->threshold_quantity) {
            $expiry_in_10days_threshold[] = $product;
        } else {
            $expiry_in_10days[] = $product;
        }
       }
       $expiry_in_10days_threshold = $this->data_formatting($expiry_in_10days_threshold);
       $expiry_in_10days = $this->data_formatting($expiry_in_10days);   
       $data = [];              
       foreach($inventory_threshold as $product) {
         if(!empty($product->quantity) && !empty($product->threshold_quantity)) {
            if($product->quantity <= $product->threshold_quantity) {
                $data[] = $product;
            }
         }
       }               
       $data = $this->data_formatting($data);

       $filterData = $this->filtered_data($data, $expiry_in_10days_threshold, $expiry_in_10days);
       return response()->json(["statusCode" => 0, "message" => "Success", "data" => $filterData], 200);      
    }

    public function data_formatting($data)
    {
        $transformedData = [];

        foreach ($data as $item) {
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
                'barcode' => $item->barcode,
                'brand' => $item->brand_name,
                'threshold_quantity' => $item->threshold_quantity,
                'quantity' => $item->quantity,
            ];
        }

        $transformedData = array_values($transformedData);

        return $transformedData;
    }

    public function get_warehouse_name($id)
    {
        $warehouse = DB::table('store_warehouse')->where('idstore_warehouse', $id)->first();
        return !empty($warehouse) ? $warehouse->name : ''; 
    }

    public function get_near_by_expried_product($idstore_warehouse = null)
    {
        $get_product = DB::table('vendor_purchases_detail')
                       ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                       ->leftJoin('inventory_threshold', 'inventory_threshold.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                       ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                       ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                       ->select('vendor_purchases_detail.idproduct_master', 'inventory.idstore_warehouse', 'product_master.name', 'product_master.barcode', 'brands.name As brand_name', 'inventory_threshold.threshold_quantity', 'inventory.quantity', 'vendor_purchases_detail.expiry')
                       ->where('expiry', '>', now()->toDateString())
                       ->where('expiry', '<', now()->addDays(10));
        if(!empty($idstore_warehouse)) {
            $data = $get_product->where('inventory.idstore_warehouse', $idstore_warehouse);
        }               
        $data = $get_product->get();
        return $data;               
    }

    public function place_order_threshold_product(Request $request)
    {
        try{
            
            $validator = Validator::make($request->all(), [
                'order_data' => 'required|array',
            ],[
               'order_data.required' => 'Please enter order data.', 
               'order_data.array' => 'Please enter order data in array format.',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } 

            foreach($request->order_data  as $order) {
                $purchase_order_data = [
                    'idvendor' => $order["idvendor"],
                    'idstore_warehouse' =>$order["idstore_warehouse"]
                ];
                $total_quantity = 0;
                $purchase_order_detail_data = [];
                foreach($order["products"] as $product) {
                    $total_quantity += $product["quantity"];
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product["idproduct_master"],
                        'quantity' => $product["quantity"],
                        'status' => 1,
                        "created_at" => now(),
                        "updated_at" => now() 
                    ];
                }
                $purchase_order_data += [
                    'total_quantity' => $total_quantity,
                    'status' => 1,
                    "created_at" => now(),
                    "updated_at" => now()
                ];

                $idpurchase_order = DB::table('purchase_order')->insertGetId($purchase_order_data);

                foreach($purchase_order_detail_data as $data) {
                    $data['idpurchase_order'] = $idpurchase_order;
                    $idpurchase_order_detail = DB::table('purchase_order_detail')->insertGetId($data);
                }
            }
            
            return response()->json(["statusCode" => 0, "message" => "Order Placed Sucessfully."], 200);

        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }     
    }
    
    public function get_threshold_order()
    {
        $idstore_warehouse = !empty($_GET['idstore_warehouse']) ? $_GET['idstore_warehouse'] : null;
        $idvendor = !empty($_GET['idvendor']) ? $_GET['idvendor'] : null;

        $get_data = DB::table('purchase_order')
                    ->select('id as idpurchase_order', 'idvendor', 'idstore_warehouse','total_quantity');
        
        if(!empty($idstore_warehouse)) {
            $get_data->where('idstore_warehouse', $idstore_warehouse);
        } 
        if(!empty($idvendor)) {
            $get_data->where('idvendor', $idvendor);
        }            
        $threshold_order = $get_data->get();
        
        foreach($threshold_order as $order) {
            $order_detail = $this->get_order_detail($order->idpurchase_order);
            $order->products = $order_detail;
        }       
        return response()->json(["statusCode" => 0, "message" => "success", "data" => $threshold_order], 200); 
    }

    public function get_order_detail($id)
    {
        $get_detail_data = DB::table('purchase_order_detail')
                           ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'purchase_order_detail.idproduct_master') 
                           ->select('purchase_order_detail.idproduct_master', 'product_master.name', 'purchase_order_detail.quantity')
                           ->where('purchase_order_detail.idpurchase_order', $id) 
                           ->get();
        return $get_detail_data;                                     
    }

    public function filtered_data($threshold_products, $expiry_in_10days_threshold_products, $expiry_in_10days_products)
    {
        $result = [];
        foreach ($threshold_products as $threshold_store) {
            $store_id = $threshold_store['idstore_warehouse'];
            $result[$store_id]['idstore_warehouse'] = $store_id;
            $result[$store_id]['warehouse_name'] = $threshold_store['warehouse_name'];
            $result[$store_id]['products']['threshold_products'] = $threshold_store['products'];
            $result[$store_id]['products']['expiry_in_10days_threshold_products'] = [];
            $result[$store_id]['products']['expiry_in_10days_products'] = [];
        }

        foreach ($expiry_in_10days_threshold_products as $expiry_store) {
            $store_id = $expiry_store['idstore_warehouse'];
            $result[$store_id]['idstore_warehouse'] = $store_id;
            $result[$store_id]['warehouse_name'] = $expiry_store['warehouse_name'];
            $result[$store_id]['products']['expiry_in_10days_threshold_products'] = $expiry_store['products'];
        }

        foreach ($expiry_in_10days_products as $expiry_store) {
            $store_id = $expiry_store['idstore_warehouse'];
            $result[$store_id]['idstore_warehouse'] = $store_id;
            $result[$store_id]['warehouse_name'] = $expiry_store['warehouse_name'];
            $result[$store_id]['products']['expiry_in_10days_products'] = $expiry_store['products'];
        }

        $result = array_values($result);
        foreach($result as $array) {
            if(empty($array['products']['threshold_products'])) {
                $array['products']['threshold_products'] = [];
            }
            if(empty($array['products']['expiry_in_10days_threshold_products'])) {
                $array['products']['expiry_in_10days_threshold_products'] = [];
            }
            if(empty($array['products']['expiry_in_10days_products'])) {
                $array['products']['expiry_in_10days_products'] = [];
            }
        }
        return $result;
    }
}