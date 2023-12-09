<?php
namespace App\Http\Controllers;

use App\Models\Customer_order;
use App\Models\order_detail;  // Adjust the model name based on your application
use App\Models\Product_master;
use App\Models\User;
use App\Models\Customer_address;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    public function getOrderData(Request $request)
    {
        if ($request->has('customer_order_id')) {
            try {
                $orderId = $request->input('customer_order_id');

                $orderDetails = order_detail::where('idcustomer_order', $orderId)->get();  // Fetch order details by customer_order_id

                $details = [];

                foreach ($orderDetails as $orderDetail) {
                    $detail = [
                        'order_id' => $orderDetail->idorder_detail,
                        'product' => [],
                    ];

                    $product = Product_master::where('idproduct_master', $orderDetail->idproduct_master)->first();
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
                $customerId = Customer_order::where('idcustomer_order', $orderId)->select('idcustomer')->first();
                $userData = User::where('id', $customerId['idcustomer'])->first();
                $address=Customer_address::where('idcustomer', $customerId['idcustomer'])->first();

                return response()->json(
                    [
                         'name' => $userData['name'], 
                         'email' => $userData['email'],
                         'contact'=>$userData['contact'], 
                         'address'=>[
                            'name'=>$address['name'],
                            'address'=>$address['address'],
                            'pincode'=>$address['pincode'],
                            'landmark'=>$address['landmark'],
                            'phone'=>$address['phone']
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
}
