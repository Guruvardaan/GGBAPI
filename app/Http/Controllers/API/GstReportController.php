<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExcalExportClass;
use Illuminate\Support\Facades\Http;
use App\Helpers\Helper;

class GstReportController extends Controller
{
    public function get_gstr1()
    {
        $year = !empty($_GET['year']) ? $_GET['year'] : now()->year;
        $month = !empty($_GET['month']) ? $_GET['month'] : now()->month;
        $last_six_month =  !empty($_GET['last_six_month']) ? $_GET['last_six_month'] : 0;

        $data = Helper::gstr1_report($year, $month, $last_six_month);
 
        $url = empty($last_six_month) ? url('api/download-excel-gstr1/' . $year .'/'. $month) : url('api/download-excel-gstr1/' . $year .'/'. $month . '/' . $last_six_month);
        $data['link'] = $url;
        return response()->json(["statusCode" => 1, 'message' => 'sucess', 'data' => $data], 200);                   
    }

    public function get_gstr2()
    {
        $year = !empty($_GET['year']) ? $_GET['year'] : now()->year;
        $month = !empty($_GET['month']) ? $_GET['month'] : now()->month;
        $last_six_month =  !empty($_GET['last_six_month']) ? $_GET['last_six_month'] : 0;

        $data = Helper::gstr2_report($year, $month, $last_six_month);
        $url = empty($last_six_month) ? url('api/download-excel-gstr2/' . $year .'/'. $month) : url('api/download-excel-gstr2/' . $year .'/'. $month . '/' . $last_six_month);
        $data['link'] = $url;

        return response()->json(["statusCode" => 1, 'message' => 'sucess', 'data' => $data], 200);                   
    }

    public function customer_order_artical_wise()
    {
        $year = !empty($_GET['year']) ? $_GET['year'] : now()->year;
        $month = !empty($_GET['month']) ? $_GET['month'] : now()->month;
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;

        $b2c_invoice = Helper::get_b2c_invoice($year, $month, $start_date, $end_date);
        $data['b2c_large_invoice'] = $b2c_invoice['b2c_large_invoice'];
        $data['b2c_small_invoice'] = $b2c_invoice['b2c_small_invoice'];
        $data['nil_reted'] = Helper::get_nil_reted_invoice($year, $month, $start_date, $end_date);
        $data['export_nvoices'] = [];
        $data['tax_liability_on_advance'] = [];
        $data['set_off_tax_on_advance_of_prior_period'] = [];
        
        $b2c_small_quantity = !empty($data['b2c_small_invoice']['total']['total_quantity']) ? $data['b2c_small_invoice']['total']['total_quantity'] : 0;
        $b2c_large_quantity = !empty($data['b2c_large_invoice']['total']['total_quantity']) ? $data['b2c_large_invoice']['total']['total_quantity'] : 0;
        $nil_reted_quantity = !empty($data['nil_reted']['total']['total_quantity']) ? $data['nil_reted']['total']['total_quantity'] : 0;

        $b2c_small_amount = !empty($data['b2c_small_invoice']['total']['total_amount']) ? $data['b2c_small_invoice']['total']['total_amount'] : 0;
        $b2c_large_amount = !empty($data['b2c_large_invoice']['total']['total_amount']) ? $data['b2c_large_invoice']['total']['total_amount'] : 0;
        $nil_reted_amount = !empty($data['nil_reted']['total']['total_amount']) ? $data['nil_reted']['total']['total_amount'] : 0;

        $b2c_small_taxable_amount = !empty($data['b2c_small_invoice']['total']['total_taxable_amount']) ? $data['b2c_small_invoice']['total']['total_taxable_amount'] : 0;
        $b2c_large_taxable_amount = !empty($data['b2c_large_invoice']['total']['total_taxable_amount']) ? $data['b2c_large_invoice']['total']['total_taxable_amount'] : 0;
        $nil_reted_taxable_amount = !empty($data['nil_reted']['total']['total_taxable_amount']) ? $data['nil_reted']['total']['total_taxable_amount'] : 0;

        $b2c_small_cgst = !empty($data['b2c_small_invoice']['total']['total_cgst']) ? $data['b2c_small_invoice']['total']['total_cgst'] : 0;
        $b2c_large_cgst = !empty($data['b2c_large_invoice']['total']['total_cgst']) ? $data['b2c_large_invoice']['total']['total_cgst'] : 0;
        $nil_reted_cgst = !empty($data['nil_reted']['total']['total_cgst']) ? $data['nil_reted']['total']['total_cgst'] : 0;

        $b2c_small_sgst = !empty($data['b2c_small_invoice']['total']['total_sgst']) ? $data['b2c_small_invoice']['total']['total_sgst'] : 0;
        $b2c_large_sgst = !empty($data['b2c_large_invoice']['total']['total_sgst']) ? $data['b2c_large_invoice']['total']['total_sgst'] : 0;
        $nil_reted_sgst = !empty($data['nil_reted']['total']['total_sgst']) ? $data['nil_reted']['total']['total_sgst'] : 0;

        $b2c_small_gst = !empty($data['b2c_small_invoice']['total']['total_gst']) ? $data['b2c_small_invoice']['total']['total_gst'] : 0;
        $b2c_large_gst = !empty($data['b2c_large_invoice']['total']['total_gst']) ? $data['b2c_large_invoice']['total']['total_gst'] : 0;
        $nil_reted_gst = !empty($data['nil_reted']['total']['total_gst']) ? $data['nil_reted']['total']['total_gst'] : 0;
            
        $total_quantity = $b2c_small_quantity + $b2c_large_quantity + $nil_reted_quantity;
        $total_amount = $b2c_small_amount + $b2c_large_amount + $nil_reted_amount;
        $total_taxable_amount = $b2c_small_taxable_amount + $b2c_large_taxable_amount + $nil_reted_taxable_amount;
        $total_cgst = $b2c_small_cgst + $b2c_large_cgst + $nil_reted_cgst;
        $total_sgst = $b2c_small_sgst + $b2c_large_sgst + $nil_reted_sgst;
        $total_gst = $b2c_small_gst + $b2c_large_gst + $nil_reted_gst;
        
        $data['gross_total'] = [
            'quantity' => $total_quantity,
            'amount' => $total_amount,
            'taxable_amount' => $total_taxable_amount,
            'cgst' => $total_cgst,
            'sgst' => $total_sgst,
            'igst' => 0.00,
            'cess' => 0.00,
            'total_gst' => $total_gst,
        ];
        return response()->json(["statusCode" => 1, 'message' => 'sucess', 'data' => $data], 200);                   
    }
    
    public function get_order_detail_b2c_invoice($id)
    {
        $order_detail = DB::table('order_detail')
                        ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                        ->select('product_master.hsn as HSN_code', 'order_detail.quantity', 'order_detail.total_price as amount', 'order_detail.total_sgst as SGST', 'order_detail.total_cgst as CGST')
                        ->where('order_detail.idcustomer_order', $id)
                        ->where('total_sgst', '<>', 0)
                        ->where('total_cgst', '<>', 0)
                        ->get();
        return $order_detail;                
    }

    public function filter_customer_order_data($data)
    {
        $b2bSmallInvoice = [];
        $nilRated = [];
        $t = [];

        foreach ($data as $invoice) {
            $sgstCgstZero = true;
        
            foreach ($invoice->products as $product) {
                if ($product['CGST_amount'] != 0 || $product['SGST_amount'] != 0) {
                    $sgstCgstZero = false;
                    break;
                } else {
                    $sgstCgstZero = true;
                    break;
                }
            }
        
            $formattedInvoice = [
                "desc" => $invoice->desc,
                "invoice_date" => $invoice->invoice_date,
                "invoice_no" => $invoice->invoice_no,
                "invoice_value" => $invoice->invoice_value,
                "local_or_central" => $invoice->local_or_central,
                "invoice_type" => $invoice->invoice_type,
                "GSTIN" => $invoice->GSTIN,
                "products" => $invoice->products
            ];
        
            if ($sgstCgstZero) {
                $nilRated[] = $formattedInvoice;
            } else {
                $b2bSmallInvoice[] = $formattedInvoice;
            }
        }
        
        $result = [
            "b2b_small_invoice" => $b2bSmallInvoice,
            "nil_rated" => $nilRated
        ];
    }
}
