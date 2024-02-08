<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  Illuminate\Support\Carbon;
use App\Models\OrderDetail;

class OrderReport extends Controller
{
    public function getOrder(Request $request)
    {
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 20;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] : null;
        
        if ($request->input('order_id')) {
            try {

                $orderId = $request->input('order_id');

                $order = DB::table('order_detail')->where('idorder_detail', $orderId)->select('idcustomer_order', 'idproduct_master', 'idorder_detail')->first();  // Fetch order details by order_id

                $List_order = DB::table('customer_order')->where('idcustomer_order', $order['idcustomer_order'])->first();

                $Product = DB::table('product_master')->where('idproduct_master', $order['idproduct_master'])->first();

                $Purchase = DB::table('product_batch')->where('idproduct_master', $Product['idproduct_master'])->select('purchase_price')->first();

                $Counter_name = DB::table('counter')->where('idcounter', $List_order['idcounter'])->first();
                $User = User::where('id', $List_order['idcustomer'])->first();

                if (!$order) {
                    return response()->json(['error' => 'Order not found'], 404);
                }

                return response()->json(
                    [
                        'Order_No' => $order['idorder_detail'],
                        'Date' => $List_order['created_at'],
                        'Counter_Name' => $Counter_name['name'],
                        'Customer_name' => $User['name'],
                        'Biller_name' => $User['name'],
                        'Discount_Coupon' => $List_order['discount_type'],
                        'Profit_per_bill' => ($Product['mrp'] - ($Purchase['purchase_price'] + $Product['discount'] + $Product['cgst'])),
                    ]
                );
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
            }
        } else {
            try {
                $data = DB::table('customer_order')
                             ->leftJoin('users', 'users.id', 'customer_order.idcustomer')
                             ->leftJoin('store_warehouse', 'store_warehouse.idstore_warehouse', 'customer_order.idstore_warehouse')
                             ->select(
                                'customer_order.idcustomer_order',
                                'customer_order.created_at as bill_date',
                                'customer_order.idcustomer',
                                'users.name As customer_name',
                                'users.contact as phone_no',
                                'store_warehouse.name as store_name',
                                'customer_order.pay_mode As payment_type',
                                'customer_order.is_delivery',
                                'customer_order.total_quantity',
                                'customer_order.discount_type',
                                'customer_order.total_discount',
                                'customer_order.total_cgst as total_cgst_amount',
                                DB::raw('Round((customer_order.total_cgst * 100)/(customer_order.total_price - customer_order.total_cgst -customer_order.total_sgst ), 2) As total_cgst_pr'),
                                'customer_order.total_sgst as total_sgst_amount',
                                DB::raw('Round((customer_order.total_sgst * 100)/(customer_order.total_price - customer_order.total_cgst -customer_order.total_sgst ), 2) As total_sgst_pr'),
                             );
                
                if(!empty($_GET['field']) && $_GET['field']=="bill_no"){
                    $data->where('customer_order.idcustomer_order', $_GET['searchTerm']);
                }

                if(!empty($_GET['field']) && $_GET['field']=="bill_date"){
                    $data->whereDate('customer_order.created_at', $_GET['searchTerm']);
                }

                if(!empty($_GET['field']) && $_GET['field']=="payment_type"){
                    $data->where('customer_order.pay_mode', $_GET['searchTerm']);
                }

                if(!empty($start_date) && !empty($end_date)) {
                    $data->whereBetween('customer_order.created_at',[$start_date, $end_date]);
                }

                if(!empty($_GET['field']) && $_GET['field']=="customer_name"){
                    $data->where('users.name', 'like', $_GET['searchTerm'] . '%');
                }

                if(!empty($_GET['field']) && $_GET['field']=="store_name"){
                    $data->where('store_warehouse.name', 'like', $_GET['searchTerm'] . '%');
                }

                if(!empty($_GET['idstore_warehouse'])) {
                    $data->where('customer_order.idstore_warehouse', $request->idstore_warehouse);
                }

                $totalRecords = $data->count();
                $limit = abs($limit - $skip);
                $orderList = $data->skip($skip)->take($limit)->get();
                $gross_profit_rs = 0;
                $gross_price = 0;
                foreach($orderList as $order) {
                    $order->totigst_pr = 0;
                    $order->igst_amount = 0;
                    $order->membership_type = $this->get_membership($order->idcustomer, $order->bill_date);
                    $order->order_type = '';
                    $order->delivery_type = (!empty($order->is_delivery)) ? 'Home Delivery' : 'Take Away';
                    $total_mrp = 0;
                    $total_paid_amount = 0;
                    $total_profit = 0;
                    $products = $this->get_detail($order->idcustomer_order);
                    $array = [];
                    foreach($products as $product) {
                        $total_mrp = $total_mrp + ($product->unit_mrp * $product->quantity);
                        $total_paid_amount = $total_paid_amount + $product->total_price;
                        $total_profit = $total_profit + $product->profit;
                        $array[] = $product->bill_in_loss;
                    }

                    $bill_in_loss = (in_array(1, $array)) ? 1 : 0;
                  
                    $order->products = $products;
                    $order->total_mrp = $total_mrp;
                    $order->total_paid_amount = $total_paid_amount;
                    $order->total_profit = $total_profit;
                    $order->bill_in_loss = $bill_in_loss;
                }             

                return response()->json(['orders_list' => $orderList, 'total' => $totalRecords]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to order data', 'details' => $e->getMessage()], 500);
            }
        }
    }

    public function getOrderData(Request $request)
    {
        if ($request->has('customer_order_id')) {
            try {
                $orderId = $request->input('customer_order_id');

                $orderDetails = OrderDetail::where('idcustomer_order', $orderId)->get();  // Fetch order details by customer_order_id

                $details = [];

                foreach ($orderDetails as $orderDetail) {
                    $detail = [
                        'order_id' => $orderDetail->idorder_detail,
                        'product' => [],
                    ];

                    $product = ProductMaster::where('idproduct_master', $orderDetail->idproduct_master)->first();
                    if ($product) {
                        $detail['product'] = [
                            'name' => $product->name,
                            'img' => $product->image,
                            'mrp' => $product->mrp,
                            'discount' => $product->discount,
                            'cgst' => $product->cgst,
                            'sgst' => $product->sgst
                        ];
                    }

                    $details[] = $detail;
                }
                $customerId = CustomerOrder::where('idcustomer_order', $orderId)->select('idcustomer')->first();
                $userData = User::where('id', $customerId['idcustomer'])->first();
                $address = CustomerAddress::where('idcustomer', $customerId['idcustomer'])->first();

                return response()->json(
                    [
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'contact' => $userData['contact'],
                        'address' => [
                            'name' => $address['name'],
                            'address' => $address['address'],
                            'pincode' => $address['pincode'],
                            'landmark' => $address['landmark'],
                            'phone' => $address['phone']
                        ],
                        'order_details' => $details
                    ]
                );
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to fetch order data', 'details' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to provide customer_order_id'], 400);
        }
    }

    public function get_detail($id)
    {
        $order_detail = DB::table('order_detail')
                        ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                        ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                        ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                        ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                        ->leftJoin('inventory', 'inventory.idinventory', 'order_detail.idinventory')
                        ->select(
                            'product_master.idproduct_master',
                            'category.name As category_name',
                            'sub_category.name as sub_category_name',
                            'brands.name As brand_name',
                            'product_master.name',
                            'product_master.barcode',
                            'product_master.hsn',
                            'order_detail.quantity',
                            'order_detail.discount',
                            'order_detail.total_price',
                            'order_detail.unit_mrp',
                            DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (inventory.purchase_price + (inventory.purchase_price * (product_master.cgst + product_master.sgst))/100) ELSE inventory.purchase_price END), 2) AS purchase_price_with_gst'),
                            'inventory.purchase_price As purchase_price_without_gst',
                            'inventory.selling_price as selling_price_with_gst',
                            DB::raw('ROUND((CASE WHEN product_master.cgst IS NOT NULL AND product_master.sgst IS NOT NULL THEN (inventory.selling_price - (inventory.selling_price * (product_master.cgst + product_master.sgst))/100) ELSE inventory.selling_price END), 2) AS selling_price_without_gst'),
                            'order_detail.total_cgst as total_cgst_amount',
                            DB::raw('Round((order_detail.total_cgst * 100)/(order_detail.total_price - order_detail.total_cgst - order_detail.total_sgst ), 2) As total_cgst_pr'),
                            'order_detail.total_sgst as total_sgst_amount',
                            DB::raw('Round((order_detail.total_sgst * 100)/(order_detail.total_price - order_detail.total_cgst - order_detail.total_sgst ), 2) As total_sgst_pr'),
                        )->where('order_detail.idcustomer_order', $id)
                        ->get();
        //
        foreach($order_detail as $order) {
            $profit = $order->unit_mrp - ($order->purchase_price_with_gst + $order->discount);
            $order->profit = round($profit * $order->quantity, 2);
            $bill_in_loss = 0;
            if($profit < 0) {
                $bill_in_loss = 1;
            }
            $order->bill_in_loss = $bill_in_loss;
        }           
        return $order_detail;                
    }

    public function get_inventory_data($id)
    {
        $data = DB::table('inventory')
                ->select('purchase_price', 'mrp')
                ->where('idinventory', $id)
                ->first(); 
        return $data;           
    }

    public function get_membership($id, $date)
    {
        $get_data = DB::table('wallet_transaction')
                    ->leftJoin('membership_plan', 'membership_plan.idmembership_plan', 'wallet_transaction.idmembership_plan')
                    ->select('membership_plan.name As membership_type')
                    ->where('wallet_transaction.idcustomer', $id)
                    ->where('wallet_transaction.created_at', $date)
                    ->first();
        $membership_type = null;
        if(!empty($get_data)) {
            if($get_data->membership_type === 'Instant Discount') {
                $membership_type = 'Instant';
            }
            if($get_data->membership_type === 'Wish Basket - Product') {
                $membership_type = 'Product';
            }
            if($get_data->membership_type === 'Wish Basket - Land') {
                $membership_type = 'Land';
            }
            if($get_data->membership_type === 'Wish Basket - CoPartner') {
                $membership_type = 'CoPartner';
            }
        }            
        return $membership_type;           
    }
}
