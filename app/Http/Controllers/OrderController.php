<?php
namespace App\Http\Controllers;
use App\Models\Customer_order;
use App\Models\order_detail;
use App\Models\product_batch;
use App\Models\Product_master;
use App\Models\User;
use App\Models\Counter;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getUserData(Request $request)
    {
        if ($request->input('order_id')) {
            try {
                
                $orderId = $request->input('order_id');  

                $order = order_detail::where('idorder_detail', $orderId)->select('idcustomer_order', 'idproduct_master', 'idorder_detail')->first();  // Fetch order details by order_id

                $List_order = Customer_order::where('idcustomer_order', $order['idcustomer_order'])->first();

                $Product = Product_master::where('idproduct_master', $order['idproduct_master'])->first();

                $Purchase = product_batch::where('idproduct_master', $Product['idproduct_master'])->select('purchase_price')->first();
           
                $Counter_name=Counter::where('idcounter', $List_order['idcounter'])->first();
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
                $orderList = order_detail::all();  // Fetch all data from the users table
                return response()->json(['orders_list' => $orderList]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to order data', 'details' => $e->getMessage()], 500);
            }
        }
    }
}
