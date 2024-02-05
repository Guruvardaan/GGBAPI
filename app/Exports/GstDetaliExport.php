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
    Public $array_data = null;
    public function __construct($start_date, $end_date, $report)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->report = $report;
    }

    public function get_data()
    {
        return $this->array_data;
    }

    public function set_data($data)
    {
        $this->array_data = $data;
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
            $data = Helper::get_gst_report($this->start_date, $this->end_date);
            $data = $this->formating_data($data);
            $this->set_data($data);
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

        if($this->report === 'gstr2_detail') {
            // $data['b2c_small_invoice'] =Helper::get_b2b_purchase_invoice(null, null, $this->start_date, $this->end_date);
            // $data['nil_reted'] = Helper::get_b2b_purchase_nil_reted_invoice(null, null, $this->start_date, $this->end_date);
            // // dd($data);
            $data = Helper::get_gstr2_report($this->start_date, $this->end_date);
            // dd($data);
            $data = $this->formating_data($data);
            // dd($data);
            $this->set_data($data);
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

        return array();
    }

    public function styles(Worksheet $sheet)
    {
        $last = $this->get_last_line() + 1;
        return [
            1    => ['font' => ['bold' => true], 'height' => 30],
            $last => ['font' => ['bold' => true]]
        ];
    }

    public function columnWidths(): array
    {
        // return array();
        return [
            'A' => 10,
            'B' => 25,
            'C' => 10,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 10,
            'K' => 10,
            'L' => 10,
            'M' => 10,
            'N' => 10,
            'O' => 10,
            'P' => 10,
            'Q' => 10,
            'R' => 10,
            'S' => 10,
            'T' => 10,
        ];
    } 

    public function formating_data($data)
    {
        $array = [];
        // dd($data);
        $total_count = 0.00;
        $total_taxable = 0.00;
        $total_CGST = 0.00;
        $total_SGST = 0.00;
        $total_IGST = 0.00;
        $total_cess = 0.00;
        $total_GST = 0.00;
        $total_amount = 0.00;
        $sr = 1;
        foreach($data as $key => $order) {
            $array[$sr]['sr'] = $sr;
            $array[$sr]['desc'] = !empty($order->desc) ? $order->desc : ' ';
            $array[$sr]['GSTIN'] = !empty($order->GSTIN) ? $order->GSTIN : ' ';
            $array[$sr]['invoice_date'] = !empty($order->invoice_date) ? $order->invoice_date : ' ';
            $array[$sr]['invoice_no'] = !empty($order->invoice_no) ? $order->invoice_no : ' ';
            $array[$sr]['invoice_no'] = !empty($order->invoice_no) ? $order->invoice_no : ' ';
            $array[$sr]['invoice_value'] = !empty($order->invoice_value) ? $order->invoice_value : ' ';
            $array[$sr]['local_or_central'] = !empty($order->local_or_central) ? $order->local_or_central : ' ';
            $array[$sr]['invoice_type'] = !empty($order->invoice_type) ? $order->invoice_type : '';  
            $array[$sr]['HSN_code'] = '';
            $array[$sr]['quantity'] = 0;
            $array[$sr]['amount'] = 0;
            $array[$sr]['taxable_amount'] = 0;
            $array[$sr]['SGST_pr'] = 0;
            $array[$sr]['SGST_amount'] = 0;
            $array[$sr]['CGST_pr'] = 0;
            $array[$sr]['CGST_amount'] = 0;
            $array[$sr]['IGST_pr'] = 0;
            $array[$sr]['IGST_amount'] = 0;
            $array[$sr]['cess'] = 0;
            $array[$sr]['total_gst'] = 0;
            $amount_total = 0;
            $taxble_amount_total = 0;
            $total_quantity = 0;
            $sgst_total = 0;
            $cgst_total = 0;
            $total_gst = 0;
            $key_sr = $sr;
            $sr = $sr + 1;

            if(!empty($order->products)) {
                foreach($order->products as $product) {
                    // dd($product);
                    $array[$sr]['sr'] = $sr;
                    $array[$sr]['desc'] = '';
                    $array[$sr]['GSTIN'] = '';
                    $array[$sr]['invoice_date'] = '';
                    $array[$sr]['invoice_no'] = '';
                    $array[$sr]['invoice_no'] = '';
                    $array[$sr]['invoice_value'] = '';
                    $array[$sr]['local_or_central'] = '';
                    $array[$sr]['invoice_type'] = '';  
                    $array[$sr]['HSN_code'] = !empty($product['HSN_code']) ? $product['HSN_code'] : '';
                    $array[$sr]['quantity'] = $product['quantity'];
                    $array[$sr]['amount'] = $product['amount'];
                    $array[$sr]['taxable_amount'] = $product['taxable_amount'];
                    $array[$sr]['SGST_pr'] = $product['SGST_pr'];
                    $array[$sr]['SGST_amount'] = $product['SGST_amount'];
                    $array[$sr]['CGST_pr'] = $product['CGST_pr'];
                    $array[$sr]['CGST_amount'] = $product['CGST_amount'];
                    $array[$sr]['IGST_pr'] = $product['IGST_pr'];
                    $array[$sr]['IGST_amount'] = $product['IGST_amount'];
                    $array[$sr]['cess'] = $product['cess'];
                    $array[$sr]['total_gst'] = $product['total_gst'];
                    $sr = $sr + 1;
                    $total_quantity = $total_quantity + $product['quantity'];
                    $amount_total = $amount_total + $product['amount'];
                    $taxble_amount_total = $taxble_amount_total + $product['taxable_amount'];
                    $sgst_total = $sgst_total + $product['SGST_amount'];
                    $cgst_total = $cgst_total + $product['CGST_amount'];
                    $total_gst = $total_gst + $product['total_gst'];
                }
                $array[$key_sr]['quantity'] = $total_quantity;
                $array[$key_sr]['amount'] = $amount_total;
                $array[$key_sr]['taxable_amount'] = $taxble_amount_total;
                $array[$key_sr]['SGST_amount'] = $sgst_total;
                $array[$key_sr]['CGST_amount'] = $cgst_total;
                $array[$key_sr]['total_gst'] = $total_gst;

                $total_count = $total_count + $total_quantity;
                $total_taxable = $total_taxable + $taxble_amount_total;
                $total_CGST = $total_CGST + $cgst_total;
                $total_SGST = $total_SGST + $sgst_total;
                $total_IGST = 0.00;
                $total_cess = 0.00;
                $total_GST = $total_GST + $total_gst;
                $total_amount = $total_amount + $amount_total;
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

        $array[sizeof($array)] = $total[0];
        // dd($array[545]);
        // $array = $this->spareted_array($array, $total);

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
        $outputArray[sizeof($outputArray)] = $total[0];
        return $outputArray;
    }

    public function add_index($data)
    {
        $this->set_data($data);
        $sr = 1;
        foreach($data as $key => $product) {
            $data[$key]['sr_no'] = $sr;
            $sr = $sr + 1;
        }

        return $data;
    }

    public function get_last_line() {
        $data = $this->get_data();
        return sizeof($data);
    }
}
