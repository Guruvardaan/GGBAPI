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
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : Carbon::now()->startOfMonth()->format('Y-m-d');;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  Carbon::now()->format('Y-m-d');
        $type = !empty($_GET['type']) ? $_GET['type'] : 'default';
        $limit = !empty($_GET['rows']) ? $_GET['rows'] : 10;
        $skip = !empty($_GET['first']) ? $_GET['first'] : 0;
        $field = !empty($_GET['field']) ? $_GET['field'] : null;
        $searchTerm = !empty($_GET['searchTerm']) ? $_GET['searchTerm'] : null;
        $link = '';
        
        if($type === 'default') {
            $b2c_invoice = Helper::get_b2c_invoice($year, $month, $start_date, $end_date);
            $data['b2c_large_invoice'] = $b2c_invoice['b2c_large_invoice'];
            $data['b2c_small_invoice'] = $b2c_invoice['b2c_small_invoice'];
            $data['nil_reted'] = Helper::get_nil_reted_invoice($year, $month, $start_date, $end_date);
            $data['export_nvoices'] = [];
            $data['tax_liability_on_advance'] = [];
            $data['set_off_tax_on_advance_of_prior_period'] = [];
            $total = $b2c_invoice['total'] + $data['nil_reted']['records'];
        
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
                'taxable_amount' => abs($total_taxable_amount),
                'cgst' => $total_cgst,
                'sgst' => $total_sgst,
                'igst' => 0.00,
                'cess' => 0.00,
                'total_gst' => $total_gst,
            ];
        
            $url =url('api/download-excel-gstr1-detail/' . $start_date .'/'. $end_date);
            $link = $url;
        } 
        if ($type === "b2c_small_invoice") {
            $b2c_invoice = Helper::get_b2c_invoice($year, $month, $start_date, $end_date, $limit, $skip, $field, $searchTerm);
            $data = $b2c_invoice['b2c_small_invoice'];
            $total = !empty($b2c_invoice['b2c_small_invoice']) ? $b2c_invoice['total'] : 0;;
        }
        if ($type === "b2c_large_invoice") {
            $b2c_invoice = Helper::get_b2c_invoice($year, $month, $start_date, $end_date, $limit, $skip, $field, $searchTerm);
            $data = $b2c_invoice['b2c_large_invoice'];
            $total = !empty($b2c_invoice['b2c_large_invoice']) ? $b2c_invoice['total'] : 0;
        }
        if ($type === "nil_reted") {
            $nil_reted = Helper::get_nil_reted_invoice($year, $month, $start_date, $end_date, $limit, $skip, $field, $searchTerm);
            $data = $nil_reted;
            $total = $nil_reted['records'];
        }
        if ($type === "export_nvoices") {
            $data = [];
            $total = 0;
        }
        if ($type === "tax_liability_on_advance") {
            $data = [];
            $total = 0;
        }
        if ($type === "set_off_tax_on_advance_of_prior_period") {
            $data = [];
            $total = 0;
        }
        return response()->json(["statusCode" => 1, 'message' => 'sucess', 'link' => $link,  'data' => $data, 'total' => $total], 200);                   
    }
    
    public function purchase_order_artical_wise()
    {
        $year = !empty($_GET['year']) ? $_GET['year'] : now()->year;
        $month = !empty($_GET['month']) ? $_GET['month'] : now()->month;
        $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  null;

        $b2b_purchase_invoice = Helper::get_b2b_purchase_invoice($year, $month, $start_date, $end_date);
        $nil_reted_invoice = Helper::get_b2b_purchase_nil_reted_invoice($year, $month, $start_date, $end_date);
        $data['b2b_other_taxable_invoices'] = $b2b_purchase_invoice;
        $data['import_of_goods_invoices'] = [];
        $data['import_of_services_invoices'] = [];
        $data['nil_reted'] = $nil_reted_invoice;
        $data['itc_reversal'] = [];
        $data['tax_paid_on_reverse_charges'] = [];
        $data['tax_paid_under_reverse_charge_on_advance'] = [];

        $b2b_other_taxable_invoices_quantity = !empty($data['b2b_other_taxable_invoices']['total']['total_quantity']) ? $data['b2b_other_taxable_invoices']['total']['total_quantity'] : 0;
        $nil_reted_quantity = !empty($data['nil_reted']['total']['total_quantity']) ? $data['nil_reted']['total']['total_quantity'] : 0;

        $b2b_other_taxable_invoices_amount = !empty($data['b2b_other_taxable_invoices']['total']['total_amount']) ? $data['b2b_other_taxable_invoices']['total']['total_amount'] : 0;
        $nil_reted_amount = !empty($data['nil_reted']['total']['total_amount']) ? $data['nil_reted']['total']['total_amount'] : 0;

        $b2b_other_taxable_invoices_taxable_amount = !empty($data['b2b_other_taxable_invoices']['total']['total_taxable_amount']) ? $data['b2b_other_taxable_invoices']['total']['total_taxable_amount'] : 0;
        $nil_reted_taxable_amount = !empty($data['nil_reted']['total']['total_taxable_amount']) ? $data['nil_reted']['total']['total_taxable_amount'] : 0;

        $b2b_other_taxable_invoices_cgst = !empty($data['b2b_other_taxable_invoices']['total']['total_cgst']) ? $data['b2b_other_taxable_invoices']['total']['total_cgst'] : 0;
        $nil_reted_cgst = !empty($data['nil_reted']['total']['total_cgst']) ? $data['nil_reted']['total']['total_cgst'] : 0;

        $b2b_other_taxable_invoices_sgst = !empty($data['b2b_other_taxable_invoices']['total']['total_sgst']) ? $data['b2b_other_taxable_invoices']['total']['total_sgst'] : 0;
        $nil_reted_sgst = !empty($data['nil_reted']['total']['total_sgst']) ? $data['nil_reted']['total']['total_sgst'] : 0;

        $b2b_other_taxable_invoices_gst = !empty($data['b2b_other_taxable_invoices']['total']['total_gst']) ? $data['b2b_other_taxable_invoices']['total']['total_gst'] : 0;
        $nil_reted_gst = !empty($data['nil_reted']['total']['total_gst']) ? $data['nil_reted']['total']['total_gst'] : 0;
            
        $total_quantity = $b2b_other_taxable_invoices_quantity + $nil_reted_quantity;
        $total_amount = $b2b_other_taxable_invoices_amount + $nil_reted_amount;
        $total_taxable_amount = $b2b_other_taxable_invoices_taxable_amount + $nil_reted_taxable_amount;
        $total_cgst = $b2b_other_taxable_invoices_cgst + $nil_reted_cgst;
        $total_sgst = $b2b_other_taxable_invoices_sgst + $nil_reted_sgst;
        $total_gst = $b2b_other_taxable_invoices_gst + $nil_reted_gst;
        
        $data['gross_total'] = [
            'quantity' => $total_quantity,
            'amount' => $total_amount,
            'taxable_amount' => abs($total_taxable_amount),
            'cgst' => $total_cgst,
            'sgst' => $total_sgst,
            'igst' => 0.00,
            'cess' => 0.00,
            'total_gst' => $total_gst,
        ];
        $url =url('api/download-excel-gstr2-detail/' . $start_date .'/'. $end_date);
        $link = $url;
        return response()->json(["statusCode" => 1, 'message' => 'sucess', 'link' => $link, 'data' => $data], 200);
    }
}
