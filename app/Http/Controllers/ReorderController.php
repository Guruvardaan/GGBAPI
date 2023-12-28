<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;

class ReorderController extends Controller
{
    
    public function reorder(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'idcustomer_order' => 'required',
            'current_cart_id' => 'required',
            'idstore_warehouse' => 'required'
        ]);
        
        if ($validator->fails()) { 
                $errors = $validator->errors();
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'all fields are required',
                    'data' => $errors->toJson()
                ]);
        }

        try {
            $idcustomer_order = $request->input('idcustomer_order');
            $current_cart_id = $request->input('current_cart_id');
            $idstore_warehouse = $request->input('idstore_warehouse');
            $customer_order = DB::table('customer_order')
                 ->select('idcustomer_order')
                 ->where('idcustomer_order',$idcustomer_order)
                 ->where('idstore_warehouse',$idstore_warehouse)
                 ->count();
            if($customer_order){

                $current_order_temp_count = DB::table('customer_order_temp')
                ->where('idcustomer_order_temp',$current_cart_id)
                ->where('idstore_warehouse',$idstore_warehouse)
                 ->count();

                $customer_current_order_temp = DB::table('customer_order_temp')
                ->where('idcustomer_order_temp',$current_cart_id)
                ->where('idstore_warehouse',$idstore_warehouse)
                 ->get();
                 //print_r($customer_current_order_temp); exit;
                if($current_order_temp_count>0){ // if current temp cart 
                    $customer_order = DB::table('customer_order')
                    ->where('idcustomer_order',$idcustomer_order)
                    ->where('idstore_warehouse',$idstore_warehouse)
                     ->get();
                    $order_detail = DB::table('order_detail')
                     ->where('idcustomer_order',$idcustomer_order)
                     ->get();
                    
                    if($order_detail){
                        $record=1;
                        $total_quantity=$total_price=$total_cgst=$total_sgst=$total_discount=0;
                        foreach($order_detail as $ord){
                            $inventory_details = DB::table('inventory')
                            ->where('idstore_warehouse',$idstore_warehouse)
                            ->where('idinventory',$ord->idinventory)
                            ->get();
                            
                            if($inventory_details[0]->quantity >= $ord->quantity){ // stock check
                                // add to cart fuctionality
                                if($record==1){ // insert into customer_order_temp
                                    $temp_order_data = array();
                                
                                    $updated_total_quantity = ($customer_order[0]->total_quantity + $customer_current_order_temp[0]->total_quantity);
                                    $updated_total_price = ($customer_order[0]->total_price + $customer_current_order_temp[0]->total_price);
                                    $updated_total_cgst=($customer_order[0]->total_cgst + $customer_current_order_temp[0]->total_cgst);
                                    $updated_total_sgst=($customer_order[0]->total_sgst + $customer_current_order_temp[0]->total_sgst);
                                    $updated_total_discount=($customer_order[0]->total_discount + $customer_current_order_temp[0]->total_discount);

                                    DB::table('customer_order_temp')->where('idcustomer_order_temp',$customer_current_order_temp[0]->idcustomer_order_temp)->update(
                                        [
                                            'idstore_warehouse' => $customer_order[0]->idstore_warehouse,
                                            'idcustomer' => $customer_order[0]->idcustomer,
                                            'is_online' => $customer_order[0]->is_online,
                                            'is_pos'=>$customer_order[0]->is_pos,
                                            'is_paid_online'=>$customer_order[0]->is_paid_online,
                                            'is_paid'=>$customer_order[0]->is_paid,
                                            'is_delivery'=>$customer_order[0]->is_delivery,
                                            'total_quantity' => ($customer_order[0]->total_quantity + $customer_current_order_temp[0]->total_quantity),
                                            'total_price' => ($customer_order[0]->total_price + $customer_current_order_temp[0]->total_price),
                                            'total_cgst'=>($customer_order[0]->total_cgst + $customer_current_order_temp[0]->total_cgst),
                                            'total_sgst'=>($customer_order[0]->total_sgst + $customer_current_order_temp[0]->total_sgst),
                                            'total_discount'=>($customer_order[0]->total_discount + $customer_current_order_temp[0]->total_discount),
                                            'discount_type'=>$customer_order[0]->discount_type,
                                            //'promocode'=>$customer_order[0]->promocode,
                                            'created_by'=>$customer_order[0]->created_by,
                                            'updated_by'=>$customer_order[0]->updated_by,
                                            'status'=>$customer_order[0]->status,
                                        ]
                                       );
                                }

                                // check if already item added into cart
                                $items_incart = DB::table('order_detail_temp')
                                ->where('idcustomer_order_temp',$customer_current_order_temp[0]->idcustomer_order_temp)
                                ->where('idproduct_master',$ord->idproduct_master)
                                ->where('idinventory',$ord->idinventory)
                                ->get();
                                if($items_incart){
                                    DB::table('order_detail_temp')->where('idcustomer_order_temp',$customer_current_order_temp[0]->idcustomer_order_temp)->whereAnd('idproduct_master',$ord->idinventory)->whereAnd('idinventory',$ord->idinventory)->update(
                                        [
                                            'idcustomer_order_temp' => $customer_current_order_temp[0]->idcustomer_order_temp,
                                            'idproduct_master' => $ord->idproduct_master,
                                            'idinventory' => $ord->idinventory,
                                            'quantity'=>($ord->quantity + $items_incart[0]->quantity),
                                            'total_price'=>($ord->total_price + $items_incart[0]->total_price),
                                            'total_sgst'=>($ord->total_sgst + $items_incart[0]->total_sgst),
                                            'discount'=>($ord->discount + $items_incart[0]->discount),
                                            'total_cgst' => ($ord->total_cgst + $items_incart[0]->total_cgst),
                                            'unit_mrp' => $ord->unit_mrp,
                                            'unit_selling_price'=>$ord->unit_selling_price,
                                            'created_by'=>$ord->created_by,
                                            'updated_by'=>$ord->updated_by,
                                            'status'=>$ord->status,
                                        ]
                                   );
                                }else{
                                    // insert into order_detail_temp
                                    $temp_order_details = array([
                                        'idcustomer_order_temp' => $customer_current_order_temp[0]->idcustomer_order_temp,          //$temp_order_id
                                        'idproduct_master' => $ord->idproduct_master,
                                        'idinventory' => $ord->idinventory,
                                        'quantity'=>$ord->quantity,
                                        'total_price'=>$ord->total_price,
                                        'total_sgst'=>$ord->total_sgst,
                                        'discount'=>$ord->discount,
                                        'total_cgst' => $ord->total_cgst,
                                        'unit_mrp' => $ord->unit_mrp,
                                        'unit_selling_price'=>$ord->unit_selling_price,
                                        'created_by'=>$ord->created_by,
                                        'updated_by'=>$ord->updated_by,
                                        'status'=>$ord->status,
                                    ]);
                                    DB::table('order_detail_temp')->insert(
                                        $temp_order_details
                                   );
                                }
    
                               $record++;
    
                            }else{
                                // out of stock - remove item from the cart and adjust total, tax and discount accordingly
                                $total_quantity=$total_quantity + $ord->quantity;
                                $total_price=$total_price + $ord->total_price;
                                $total_cgst=$total_cgst + $ord->total_sgst;
                                $total_sgst=$total_sgst + $ord->total_cgst;
                                $total_discount=$total_discount + $ord->discount;
                            }
                        }
    
                        // update temp customer order is quantity is out of stock
                        $updated_qty=$updated_total_quantity-$total_quantity;
                        $updated_price=$updated_total_price-$total_price;
                        $updated_cgst=$updated_total_cgst-$total_cgst;
                        $updated_sgst=$updated_total_sgst-$total_sgst;
                        $updated_discount=$updated_total_discount-$total_discount;
    
                        $updatedRaw = DB::table('customer_order_temp')->where('idcustomer_order_temp',$customer_current_order_temp[0]->idcustomer_order_temp)->update([
                            'total_quantity' => $updated_qty,'total_price' => $updated_price,'total_cgst' => $updated_cgst,'total_sgst' => $updated_sgst,'total_discount' => $updated_discount
                        ]);
    
    
                        return response()->json([
                            'statusCode' => '0',
                            'message' => 'success'
                        ]);
                    }else{
                        return response()->json([
                            'statusCode' => '1',
                            'message' => 'Order details are not found!'
                        ]);
                    }
                }else{
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'current temp cart not found!'
                    ]);
                }
               
            }else{
                return response()->json([
                    'statusCode' => '1',
                    'message' => 'Order is not found!'
                ]);
            }
        
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
        }
    }
}
