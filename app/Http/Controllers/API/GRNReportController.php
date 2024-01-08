<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use  App\Helpers\Helper;
use Illuminate\Support\Carbon;

class GRNReportController extends Controller
{
    public function add_order(Request $request)
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
                    
                    $date = !empty($product['expiry']) ? Carbon::parse($product['expiry']) : '';
                    $expiry = !empty($date) ? $date->format('d-M-y') : '';
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $sent_quantity = Helper::check_sent_quantity($product_id);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => $sent_quantity,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now() 
                    ];
                }
                foreach($order["extra_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'extra_product' => 1,
                    ];
                }
                foreach($order["free_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'free_product' => 1,
                    ];
                }
                foreach($order["expired_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'expired_product' => 1,
                    ];
                }
                $purchase_order_data += [
                    'total_quantity' => $total_quantity,
                    'note1' => !empty($order['note1']) ? $order['note1'] : '',
                    'note2' => !empty($order['note2']) ? $order['note2'] : '', 
                    'image1' => !empty($order['image1']) ? $order['image1'] : '',
                    'image2' => !empty($order['image2']) ? $order['image2'] : '', 
                    'status' => 0,
                    "created_at" => now(),
                    "updated_at" => now()
                ];

                $idpurchase_order = DB::table('grn_purchase_order')->insertGetId($purchase_order_data);

                foreach($purchase_order_detail_data as $data) {
                    $data['idgrn_purchase_order'] = $idpurchase_order;
                    $idpurchase_order_detail = DB::table('grn_purchase_order_detail')->insertGetId($data);
                }
            }
            
            return response()->json(["statusCode" => 0, "message" => "Data added Sucessfully."], 200);

        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }  
    }

    public function get_product_with_barcode($barcode)
    {
        $product = DB::table('product_master')->select('idproduct_master')->where('barcode', $barcode)->first();
        return $product->idproduct_master;
    }

    public function get_grn_puchase_order()
    {
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date']: null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;
        $get_purchase_order = Helper::get_grn_purchase_data($start_date, $end_date);

        return response()->json(["statusCode" => 0, "message" => "success.", "data" => $get_purchase_order], 200);
    }

    public function edit_order(Request $request, $id)
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
                    
                    $date = !empty($product['expiry']) ? Carbon::parse($product['expiry']) : '';
                    $expiry = !empty($date) ? $date->format('d-M-y') : '';
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $sent_quantity = Helper::check_sent_quantity($product_id);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => $sent_quantity,
                        'status' => 1,
                        'updated_at' => now() 
                    ];
                }
                foreach($order["extra_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'updated_at' => now(),
                        'extra_product' => 1,
                    ];
                }
                foreach($order["free_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'updated_at' => now(),
                        'free_product' => 1,
                    ];
                }
                foreach($order["expired_products"] as $product) {
                    $total_quantity += $product["quantity"];
                
                    $product_id = !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']);
                    $purchase_order_detail_data[] = [
                        'idproduct_master' => $product_id,
                        'quantity' => $product["quantity"],
                        'sent_quantity' => 0,
                        'status' => 1,
                        'updated_at' => now(),
                        'expired_product' => 1,
                    ];
                }
                $purchase_order_data += [
                    'total_quantity' => $total_quantity,
                    'note1' => !empty($order['note1']) ? $order['note1'] : '',
                    'note2' => !empty($order['note2']) ? $order['note2'] : '', 
                    'image1' => !empty($order['image1']) ? $order['image1'] : '',
                    'image2' => !empty($order['image2']) ? $order['image2'] : '', 
                    'status' => 0,
                    "updated_at" => now()
                ];

                DB::table('grn_purchase_order')->where('id', $id)->update($purchase_order_data);

                foreach($purchase_order_detail_data as $data) {
                    $idpurchase_order_detail = DB::table('grn_purchase_order_detail')->where('idgrn_purchase_order', $id)->where('idproduct_master', $data['idproduct_master'])->update($data);
                }
            }
            
            return response()->json(["statusCode" => 0, "message" => "Data updated Sucessfully."], 200);

        } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
        }  
    }

    public function confirm_grn($id)
    {
        $order = DB::table('grn_purchase_order')->where('id', $id)->first();
        if(empty($order->confirmed)) {
            $order_data = Helper::get_grn_order_detail($id);
            foreach($order_data as $data) {
                $inventory = $this->set_inventory($data->idproduct_master, $data->quantity, $order->idstore_warehouse);
            }
            $update = DB::table('grn_purchase_order')->where('id', $id)->update(['confirmed' => 1]);
            return response()->json(["statusCode" => 0, "message" => "Order confirmed successfully."], 200);
        } else {
            return response()->json(["statusCode" => 0, "message" => "Order alrady confirmed."], 200);
        }
    }

    public function set_inventory($idproduct_master, $quantity, $idstore_warehouse)
    {
       try{
        $inventory_data = DB::table('inventory')->select('idproduct_master', 'quantity')->where('idproduct_master', $idproduct_master)->where('idstore_warehouse', $idstore_warehouse)->first();
        if(!empty($inventory_data)) {
            $total_quantity = $quantity + $inventory_data->quantity;
            $update = DB::table('inventory')->where('idproduct_master', $idproduct_master)->where('idstore_warehouse', $idstore_warehouse)->update(['quantity' => $total_quantity, 'updated_at' => now(),]);
        } else {
            $product_data = DB::table('product_batch')->select('purchase_price', 'selling_price', 'mrp')->where('idproduct_master', $idproduct_master)->get()->last();
            $get_data = [
                'idstore_warehouse' => $idstore_warehouse,
                'idproduct_master' => $idproduct_master,
                'selling_price' => !empty($product_data->selling_price) ? $product_data->selling_price : 0,
                'purchase_price' => !empty($product_data->selling_price) ? $product_data->purchase_price : 0,
                'mrp' => !empty($product_data->mrp) ? $product_data->mrp : 0,
                'quantity' => $quantity,
                'created_at' => now(),
                'status' => 1 
            ];

            $id = DB::table('inventory')->insertGetId($get_data);
        }
       } catch(\Exception $e) {
            return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
       }  
    }
}
