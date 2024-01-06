<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use  App\Helpers\Helper;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\PDF;

class PurchaseOrderController extends Controller
{
    public function place_order(Request $request)
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
                        'idproduct_master' => !empty($product["idproduct_master"]) ? $product["idproduct_master"] : $this->get_product_with_barcode($product['barcode']),
                        'quantity' => $product["quantity"],
                        'status' => 1,
                        "created_at" => now(),
                        "updated_at" => now() 
                    ];
                }
                $purchase_order_data += [
                    'total_quantity' => $total_quantity,
                    'status' => 0,
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

    public function get_product_with_barcode($barcode)
    {
        $product = DB::table('product_master')->select('idproduct_master')->where('barcode', $barcode)->first();
        return $product->idproduct_master;
    }

    public function get_puchase_order()
    {
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date']: null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;
        $get_purchase_order = Helper::get_purchase_data($start_date, $end_date);
        $url = url('api/generate-pdf/' . $start_date .'/'. $end_date);

        return response()->json(["statusCode" => 0, "message" => "success.", "data" => $get_purchase_order, "pdf_link" => $url], 200);
    }

    public function generate_pdf($start_date = null, $end_date = null)
    {
        $start_date = !empty($start_date) ? $start_date : null;
        $end_date = !empty($end_date) ? $end_date : null; 
        $get_purchase_order = Helper::get_purchase_data($start_date, $end_date);
        $purchase_data = $get_purchase_order;
        // dd($purchase_data);
        $pdf = PDF::loadView('purchase_data_pdf_view', compact('purchase_data'));
        return $pdf->stream('pruchase_order.pdf');
    }

    public function loadDataview()
    {
        $get_purchase_order = Helper::get_purchase_data(null, null);
        $purchase_data = $get_purchase_order;
        return view('purchase_data_pdf_view', compact('purchase_data'));
    }
}
