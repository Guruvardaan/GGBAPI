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
                             ->leftJoin('counters', 'counters.id', 'customer_order.idcounter')
                             ->leftJoin('store_warehouse', 'store_warehouse.idstore_warehouse', 'customer_order.idstore_warehouse')
                             ->select(
                                'customer_order.idcustomer_order',
                                'customer_order.idstore_warehouse',
                                'users.name As customer_name',
                                'store_warehouse.name as store_warehouse',
                                'customer_order.total_quantity as quantity',
                                'customer_order.total_price As price',
                                'customer_order.total_cgst As cgst',
                                'customer_order.total_sgst As sgst',
                                'customer_order.total_discount',
                                'customer_order.discount_type',
                                'customer_order.created_at'
                             );
                
                if(!empty($_GET['field']) && $_GET['field']=="bill_no"){
                    $data->where('customer_order.idcustomer_order', $_GET['searchTerm']);
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

                $totalRecords = $data->count();
                $limit = abs($limit - $skip);
                $orderList = $data->skip($skip)->take($limit)->get();
                $gross_profit_rs = 0;
                $gross_price = 0;
                foreach($orderList as $order) {
                    $products = $this->get_detail($order->idcustomer_order);
                    $order->products = $products;
                    $profit_ar_rs = 0;
                    $gr_mrp = 0;
                    $billed_in_loss = 0;
                    foreach($products as $product) {
                        $inventory_data = $this->get_inventory_data($product->idinventory);
                        $profit_rs = 0;
                        $profit_pr = 0;
                        if(!empty($inventory_data)) {
                            $gr_mrp = $gr_mrp + $inventory_data->mrp;
                            $profit_rs = $inventory_data->mrp -($inventory_data->purchase_price + $product->discount + $product->cgst + $product->sgst); 
                            $profit_pr = !empty($profit_rs) ? ($profit_rs/$inventory_data->mrp) * 100 : 0;
                        }
                        $profit_ar_rs = $profit_ar_rs + $profit_rs; 
                        $product->profit_rs = $profit_rs;
                        $product->profit_pr = round($profit_pr);
                        if($profit_rs <= 0) {
                            $billed_in_loss = 1;
                        }
                        $product->billed_in_loss = $billed_in_loss;
                    }
                    $order->billed_in_loss = $billed_in_loss;
                    $profit_ar_pr = !empty($profit_ar_rs) ? ($profit_ar_rs/$gr_mrp) * 100 : 0;
                    $order->profit_rs = round($profit_ar_rs, 2);
                    $order->profit_pr = round($profit_ar_pr, 2);
                    $gross_profit_rs = $gross_profit_rs + $profit_ar_rs;
                    $gross_price = $gross_price + $gr_mrp;
                }             
                $gross_profit_pr = !empty($gross_profit_rs) ? ($gross_profit_rs/$gross_price) * 100 : 0;
                $orderList['gross_profit_rs'] = round($gross_profit_rs, 2);
                $orderList['gross_profit_pr'] = round($gross_profit_pr, 2);
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
                        ->leftJoin('package_prod_list', 'package_prod_list.idproduct_master', '=', 'order_detail.idproduct_master')
                        ->leftJoin('package', 'package.idpackage', '=', 'package_prod_list.idpackage')
                        ->select('product_master.name as product_name', 'product_master.hsn as HSN_code', 'order_detail.idinventory','order_detail.quantity', 'order_detail.total_price as price', 'order_detail.total_sgst as sgst', 'order_detail.total_cgst as cgst', 'package.name As package_name', 'order_detail.discount')
                        ->where('order_detail.idcustomer_order', $id)
                        ->get();
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
}
