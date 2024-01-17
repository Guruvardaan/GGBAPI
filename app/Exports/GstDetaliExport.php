<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Helpers\Helper;

class GstDetaliExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    Private $start_date = null;
    Private $end_date = null;
    Private $report = null;

    public function __construct($start_date, $end_date, $report)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->report = $report;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Desc',
            'GSTIN',
            'Invoice Date',
            'Invoice No',
            'Invoice Value',
            'Local/Central',
            'Invoice Type',
            'HSN Code',
            'Quantity',
            'Amount',
            'Taxable Amount',
            'SGST %',
            'SGST Amount',
            'CGST %',
            'CGST Amount',
            'IGST %',
            'IGST Amount',
            'Cess',
            'Total GST'
        ];
    }

    public function array(): array
    {
        if($this->report === 'gstr1_detail') {
            $b2c_invoice = Helper::get_b2c_invoice(null, null, $this->start_date, $this->end_date);
            $data['b2c_large_invoice'] = $b2c_invoice['b2c_large_invoice'];
            $data['b2c_small_invoice'] = $b2c_invoice['b2c_small_invoice'];
            $data['nil_reted'] = Helper::get_nil_reted_invoice(null, null, $this->start_date, $this->end_date);

            $data = $this->formating_data($data);
            // dd($data);
            $formattedData = array_map(function ($row) {
                return array_map(function ($value) {
                    if(gettype($value) === 'string') {
                        return $value;
                    } else {
                        return number_format((float)$value, 2, '.', '');
                    }
                }, $row);
            }, $data);

            return $formattedData;
        }

        // if($this->report === 'gstr2_detail') {
        //     $data['b2c_large_invoice'] =Helper::get_b2b_purchase_invoice(null, null, $this->start_date, $this->end_date);
        //     $data['nil_reted'] = Helper::get_b2b_purchase_nil_reted_invoice(null, null, $this->start_date, $this->end_date);

        //     $data = $this->formating_data($data);
        //     $formattedData = array_map(function ($row) {
        //         return array_map(function ($value) {
        //             if(gettype($value) === 'string') {
        //                 return $value;
        //             } else {
        //                 return number_format((float)$value, 2, '.', '');
        //             }
        //         }, $row);
        //     }, $data);

        //     return $formattedData;
        // }

        return array();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true], 'height' => 30],
        ];
    }

    public function columnWidths(): array
    {
        return array();
        // return [
        //     'A' => 35,
        //     'B' => 10,
        //     'C' => 10,
        //     'D' => 10,
        //     'E' => 10,
        //     'F' => 10,
        //     'G' => 10,
        //     'H' => 10,
        //     'I' => 10,
        // ];
    }

    public function formating_data($data)
    {
        $array = [];
        $invoice_data = [];
        $fileds = ['b2b', 'b2c_large_Invoice', 'b2c_small_invoice', 'nil_reted' , 'export_invoices', 'tax_liability_on_advance', 'set_off_tax_on_advance_of_prior_period'];
        $title_fileds = ['b2b' => 'B2B', 'b2c_large_Invoice' => ' B2C (Large) Invoice', 'b2c_small_invoice' => ' B2C (Small) Invoice', 'nil_reted' => 'Nil Reted', 'export_invoices' => 'Export Invoices' , 'tax_liability_on_advance' => 'Tax Liability on Advance', 'set_off_tax_on_advance_of_prior_period' => ' Set/off Tax on Advance of prior period'];
        $total_count = 0.00;
        $total_taxable = 0.00;
        $total_CGST = 0.00;
        $total_SGST = 0.00;
        $total_IGST = 0.00;
        $total_cess = 0.00;
        $total_GST = 0.00;
        $total_amount = 0.00;
        $cat_wise_total = [];
        foreach($data as $key => $item) {
        $sr = 1;
         if(in_array($key, $fileds)) {
            $cat_wise_total[$key] = $item['total'];
            foreach($item as $p_key => $products) {
                if(!empty($products->products)) {
                    $result = [];
                    foreach($products->products as $index => $product) {
                        if($index === 0) {
                            $result[$index]['sr_no'] = $sr;
                            $result[$index]['desc'] = !empty($products->desc) ? $products->desc : ' ';
                            $result[$index]['GSTIN'] = !empty($products->GSTIN) ? $products->GSTIN : ' ';
                            $result[$index]['invoice_date'] = !empty($products->invoice_date) ? $products->invoice_date : ' ';
                            $result[$index]['invoice_no'] = !empty($products->invoice_no) ? $products->invoice_no : ' ';
                            $result[$index]['invoice_no'] = !empty($products->invoice_no) ? $products->invoice_no : ' ';
                            $result[$index]['invoice_value'] = !empty($products->invoice_value) ? $products->invoice_value : ' ';
                            $result[$index]['local_or_central'] = !empty($products->local_or_central) ? $products->local_or_central : ' ';
                            $result[$index]['invoice_type'] = !empty($products->invoice_type) ? $products->invoice_type : '';  
                            $result[$index]['HSN_code'] = !empty($product['HSN_code']) ? $product['HSN_code'] : ' ';
                            $result[$index]['quantity'] = !empty($product['quantity']) ? $product['quantity'] : 0;
                            $result[$index]['amount'] = !empty($product['amount']) ? $product['amount'] : 0;
                            $result[$index]['taxable_amount'] = !empty($product['taxable_amount']) ? $product['taxable_amount'] : 0;
                            $result[$index]['SGST_pr'] = !empty($product['SGST_pr']) ? $product['SGST_pr'] : 0;
                            $result[$index]['SGST_amount'] = !empty($product['SGST_amount']) ? $product['SGST_amount'] : 0;
                            $result[$index]['CGST_pr'] = !empty($product['CGST_pr']) ? $product['CGST_pr'] : 0;
                            $result[$index]['CGST_amount'] = !empty($product['CGST_amount']) ? $product['CGST_amount'] : 0;
                            $result[$index]['IGST_pr'] = !empty($product['IGST_pr']) ? $product['IGST_pr'] : 0;
                            $result[$index]['IGST_amount'] = !empty($product['IGST_amount']) ? $product['IGST_amount'] : 0;
                            $result[$index]['cess'] = !empty($product['cess']) ? $product['cess'] : 0;
                            $result[$index]['total_gst'] = !empty($product['total_gst']) ? $product['total_gst'] : 0;
                            // $result[$index]['category'] = $key;
                        } else {
                            $result[$index]['sr_no'] = $sr;
                            $result[$index]['desc'] =  ' ';
                            $result[$index]['GSTIN'] = ' ';
                            $result[$index]['invoice_date'] = ' ';
                            $result[$index]['invoice_no'] = ' ';
                            $result[$index]['invoice_no'] = ' ';
                            $result[$index]['invoice_value'] = ' ';
                            $result[$index]['local_or_central'] = ' ';
                            $result[$index]['invoice_type'] = ' '; 
                            $result[$index]['HSN_code'] = !empty($product['HSN_code']) ? $product['HSN_code'] : ' ';
                            $result[$index]['quantity'] = !empty($product['quantity']) ? $product['quantity'] : 0;
                            $result[$index]['amount'] = !empty($product['amount']) ? $product['amount'] : 0;
                            $result[$index]['taxable_amount'] = !empty($product['taxable_amount']) ? $product['taxable_amount'] : 0;
                            $result[$index]['SGST_pr'] = !empty($product['SGST_pr']) ? $product['SGST_pr'] : 0;
                            $result[$index]['SGST_amount'] = !empty($product['SGST_amount']) ? $product['SGST_amount'] : 0;
                            $result[$index]['CGST_pr'] = !empty($product['CGST_pr']) ? $product['CGST_pr'] : 0;
                            $result[$index]['CGST_amount'] = !empty($product['CGST_amount']) ? $product['CGST_amount'] : 0;
                            $result[$index]['IGST_pr'] = !empty($product['IGST_pr']) ? $product['IGST_pr'] : 0;
                            $result[$index]['IGST_amount'] = !empty($product['IGST_amount']) ? $product['IGST_amount'] : 0;
                            $result[$index]['cess'] = !empty($product['cess']) ? $product['cess'] : 0;
                            $result[$index]['total_gst'] = !empty($product['total_gst']) ? $product['total_gst'] : 0;
                            // $result[$index]['category'] = $key;
                        }
                        $sr = $sr + 1;
                        $total_count += $product['quantity'];
                        $total_taxable += abs($product['taxable_amount']);
                        $total_CGST += $product['CGST_amount'];
                        $total_SGST += $product['SGST_amount'];
                        $total_IGST += $product['IGST_amount'];
                        $total_cess += $product['cess'];
                        $total_GST += $product['total_gst'];
                        $total_amount += $product['amount'];
                    }
                }
                $array[] = $result; 
            }
         }
        }
        $total = [
            [
                'Gross Total',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $total_count,
                $total_taxable + $total_GST,
                $total_taxable,
                '',
                $total_CGST,
                '',
                $total_SGST,
                '',
                $total_IGST,
                $total_cess,
                $total_GST,
            ]

        ]; 
        $array = $this->spareted_array($array, $total);

        return $array;
    }

    public function formating_gstr2_data($data)
    {
        $fileds = ['business_to_business', 'nil_rated'];
        $total_count = 0.00;
        $total_taxable = 0.00;
        $total_CGST = 0.00;
        $total_SGST = 0.00;
        $total_IGST = 0.00;
        $total_cess = 0.00;
        $total_GST = 0.00;
        $total_amount = 0.00;
        foreach($data as $key => $item) {
         if(in_array($key, $fileds)) {
            $total_count += $item['count'];
            $total_taxable += $item['taxable'];
            $total_CGST += $item['CGST'];
            $total_SGST += $item['SGST'];
            $total_IGST += $item['IGST'];
            $total_cess += $item['cess'];
            $total_GST += $item['TotalGST'];
            $total_amount += $item['InvoiceAmount'];
         }
        }

        $result = [
            [
                'B2B', 
                !empty($data['business_to_business']['count']) ? $data['business_to_business']['count'] : "0.00", 
                $data['business_to_business']['taxable'], 
                $data['business_to_business']['CGST'], 
                $data['business_to_business']['SGST'], 
                $data['business_to_business']['IGST'], 
                $data['business_to_business']['cess'], 
                $data['business_to_business']['TotalGST'], 
                $data['business_to_business']['InvoiceAmount']
            ],
            
            [
                'Nil rated', 
                $data['nil_rated']['count'], 
                $data['nil_rated']['taxable'], 
                $data['nil_rated']['CGST'], 
                $data['nil_rated']['SGST'], 
                $data['nil_rated']['IGST'], 
                $data['nil_rated']['cess'], 
                $data['nil_rated']['TotalGST'], 
                $data['nil_rated']['InvoiceAmount']
            ],
            [
                'Total',
                $total_count,
                $total_taxable,
                $total_CGST,
                $total_SGST,
                $total_IGST,
                $total_cess,
                $total_GST,
                $total_amount,
            ]

        ]; 

        return $result;
    }

    public function spareted_array($data, $total)
    {
        $outputArray = [];
        foreach ($data as $subArray) {
            foreach ($subArray as $item) {
                $outputArray[] = $item;
            }
        }
        // dd($total);
        $outputArray[sizeof($outputArray)] = $total[0];
        return $outputArray;
    }
    
    // public function final_array_data($data, $cat_wise_total)
    // {
    //     $get_data = [];
    //     $fileds = ['b2b', 'b2c_large_Invoice', 'b2c_small_invoice', 'nil_reted' , 'export_invoices', 'tax_liability_on_advance', 'set_off_tax_on_advance_of_prior_period'];
    //     $title_fileds = ['b2b' => 'B2B', 'b2c_large_Invoice' => ' B2C (Large) Invoice', 'b2c_small_invoice' => ' B2C (Small) Invoice', 'nil_reted' => 'Nil Reted', 'export_invoices' => 'Export Invoices' , 'tax_liability_on_advance' => 'Tax Liability on Advance', 'set_off_tax_on_advance_of_prior_period' => ' Set/off Tax on Advance of prior period'];
    //     foreach($data as $item) {
    //         foreach($fileds as $filed) {
    //             if($filed === $item['category']) {
    //                 $get_data[$filed][] = $item;
    //             } 
    //         }
    //     }

    //     $result = [];
    //     $index = 0;
    //     $d_key = '';
    //     foreach($get_data as $key => $item) {
    //         $d_key = $key;
    //         if($d_key ===$key) {
    //             $result[$index] = [
    //                 $title_fileds[$key],
    //                 '',
    //                 '',
    //                 '',
    //                 '',
    //                 '',
    //                 '',
    //                 '',
    //                 '',
    //                 $cat_wise_total[$key]['total_quantity'],
    //                 $cat_wise_total[$key]['total_amount'],
    //                 $cat_wise_total[$key]['total_taxable_amount'],
    //                 '',
    //                 $cat_wise_total[$key]['total_sgst'],
    //                 '',
    //                 $cat_wise_total[$key]['total_cgst'],
    //                 '',
    //                 $cat_wise_total[$key]['total_igst'],
    //                 $cat_wise_total[$key]['total_cess'],
    //                 $cat_wise_total[$key]['total_gst'],
    //             ];
    //         }
    //     }
    //     dd($result);
    // }
}
