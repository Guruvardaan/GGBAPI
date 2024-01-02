<?php
namespace App\Http\Controllers;
use App\Models\Customer_order;
use App\Models\order_detail;
use App\Models\product_batch;
use App\Models\Product_master;
use App\Models\User;
use App\Models\Counter;
use Illuminate\Http\Request;

use DB;
use Helper;

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
    public function getOnlineOrder()
    {
        $user = auth()->guard('api')->user();
        if($user){
            $userAccess = DB::table('staff_access')
                        ->leftJoin('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
                        ->select(
                            'staff_access.idstore_warehouse',
                            'staff_access.idstaff_access',
                            'store_warehouse.is_store',
                            'staff_access.idstaff'
                        )
                        ->where('staff_access.idstaff', $user->id)
                        ->first();
            $idstore_warehouse = $userAccess->idstore_warehouse;
            
            $order = Customer_order::where('idstore_warehouse', $idstore_warehouse)->where('is_online', 1)->orderBy('idcustomer_order','desc')->get();
            $i=0;
            $orderData=[];
            foreach($order as $o){
                $orderData[$i]=$o;
                $orderDetails = order_detail::where('idcustomer_order', $o->idcustomer_order)->get();
            
                $productQuery = Helper::prepareProductQuery();
                $Products = $productQuery->leftJoin('order_detail','product_master.idproduct_master','=','order_detail.idproduct_master')
                ->selectRaw('order_detail.*,product_master.idbrand,brands.name AS brand,product_master.idproduct_master,product_master.idcategory,category.name AS category,product_master.idsub_category,sub_category.name AS scategory,product_master.idsub_sub_category,sub_sub_category.name AS sscategory,product_master.name AS prod_name,product_master.description,
                product_master.barcode,product_master.hsn')
                ->where('inventory.idstore_warehouse', $o->idstore_warehouse)
                ->where('order_detail.idcustomer_order', $o->idcustomer_order)
                ->get();

                $orderData[$i]['order_detail']=$Products;
                
                $i++;
            }
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data'=>$orderData
            ]);
        }else{
            return response()->json([
                'statusCode' => '1',
                'message' => 'user authentication required'
            ]);
        }
    }
    public function updateOrderStatus(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'status' => 'required',
            'idcustomer_order' => 'required',
            'updated_by'=>'required'
        ]);        
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json([
                'statusCode' => '1',
                'message' => 'All fields are required',
                'data' => $errors->toJson()
            ]);
        }

        try{            
            $updateOrdStatus = DB::table('customer_order')->where('idcustomer_order',$request->idcustomer_order)->update([
                'status'=>trim($request->status),'updated_by'=>trim($request->updated_by),'updated_at' => date('Y-m-d H:i:s')
            ]);

            // enable if update status of order details also
            // $updateOrdDetStatus = DB::table('order_detail')->where('idcustomer_order',$request->idcustomer_order)->update([
            //     'status'=>trim($request->status),'updated_by'=>trim($request->updated_by),'updated_at' => date('Y-m-d H:i:s')
            // ]);
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success'
            ]);
        }
        catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update order status', 'details' => $e->getMessage()], 500);
        }
    }
}
