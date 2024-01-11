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

class ExcalExportClass implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    Private $year = null;
    Private $month = null;
    Private $last_six_month = null;
    Private $report = null;

    public function __construct($year, $month, $last_six_month, $report)
    {
        $this->year = $year;
        $this->month = $month;
        $this->last_six_month = $last_six_month;
        $this->report = $report;
    }

    public function headings(): array
    {
        return [
            'Description',
            'Count',
            'Taxable',
            'CGST',
            'SGST',
            'IGST',
            'Cess',
            'Total GST',
            'Invoice Amount'
        ];
    }

    public function array(): array
    {
        if($this->report === 'gstr1') {
            $data = Helper::gstr1_report($this->year, $this->month, $this->last_six_month);
            $data = $this->formating_data($data);
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

        if($this->report === 'gstr2') {
            $data = Helper::gstr2_report($this->year, $this->month, $this->last_six_month);
            $data = $this->formating_gstr2_data($data);
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
        return [
            1    => ['font' => ['bold' => true], 'height' => 30],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 10,
            'C' => 10,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 10,
        ];
    }

    public function formating_data($data)
    {
        $fileds = ['business_to_customer_small', 'nil_rated', 'exempted', 'export_invoices', 'tax_iability_on_advance', 'set_off_tax_on_advance_of_prior_period', 'credit_debit_Note_and_refund_voucher', 'registered_arties', 'unregistered_parties', 'refund_from_advance'];
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
                !empty($data['business_to_bisiness']['count']) ? $data['business_to_bisiness']['count'] : "0.00", 
                $data['business_to_bisiness']['taxable'], 
                $data['business_to_bisiness']['CGST'], 
                $data['business_to_bisiness']['SGST'], 
                $data['business_to_bisiness']['IGST'], 
                $data['business_to_bisiness']['cess'], 
                $data['business_to_bisiness']['TotalGST'], 
                $data['business_to_bisiness']['InvoiceAmount']
            ],
            [
                'B2C (Large) Invoice', 
                $data['business_to_customer_large']['count'], 
                $data['business_to_customer_large']['taxable'], 
                $data['business_to_customer_large']['CGST'], 
                $data['business_to_customer_large']['SGST'], 
                $data['business_to_customer_large']['IGST'], 
                $data['business_to_customer_large']['cess'], 
                $data['business_to_customer_large']['TotalGST'], 
                $data['business_to_customer_large']['InvoiceAmount']
            ],
            [
                'B2C (Small) Invoice', 
                $data['business_to_customer_small']['count'], 
                $data['business_to_customer_small']['taxable'], 
                $data['business_to_customer_small']['CGST'], 
                $data['business_to_customer_small']['SGST'], 
                $data['business_to_customer_small']['IGST'], 
                $data['business_to_customer_small']['cess'], 
                $data['business_to_customer_small']['TotalGST'], 
                $data['business_to_customer_small']['InvoiceAmount']
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
                '-Nil rated', 
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
                '-Exempted', 
                $data['exempted']['count'], 
                $data['exempted']['taxable'], 
                $data['exempted']['CGST'], 
                $data['exempted']['SGST'], 
                $data['exempted']['IGST'], 
                $data['exempted']['cess'], 
                $data['exempted']['TotalGST'], 
                $data['exempted']['InvoiceAmount']
            ],
            [
                'Export Invoices', 
                $data['export_invoices']['count'], 
                $data['export_invoices']['taxable'], 
                $data['export_invoices']['CGST'], 
                $data['export_invoices']['SGST'], 
                $data['export_invoices']['IGST'], 
                $data['export_invoices']['cess'], 
                $data['export_invoices']['TotalGST'], 
                $data['export_invoices']['InvoiceAmount']
            ],
            [
                'Tax Liability on Advance', 
                $data['tax_iability_on_advance']['count'], 
                $data['tax_iability_on_advance']['taxable'], 
                $data['tax_iability_on_advance']['CGST'], 
                $data['tax_iability_on_advance']['SGST'], 
                $data['tax_iability_on_advance']['IGST'], 
                $data['tax_iability_on_advance']['cess'], 
                $data['tax_iability_on_advance']['TotalGST'], 
                $data['tax_iability_on_advance']['InvoiceAmount']
            ],
            [
                'Set/off Tax on Advance of prior period', 
                $data['set_off_tax_on_advance_of_prior_period']['count'], 
                $data['set_off_tax_on_advance_of_prior_period']['taxable'], 
                $data['set_off_tax_on_advance_of_prior_period']['CGST'], 
                $data['set_off_tax_on_advance_of_prior_period']['SGST'], 
                $data['set_off_tax_on_advance_of_prior_period']['IGST'], 
                $data['set_off_tax_on_advance_of_prior_period']['cess'], 
                $data['set_off_tax_on_advance_of_prior_period']['TotalGST'], 
                $data['set_off_tax_on_advance_of_prior_period']['InvoiceAmount']
            ],
            [
                'Less: Credit/Debit Note & Refund Voucher', 
                $data['credit_debit_Note_and_refund_voucher']['count'], 
                $data['credit_debit_Note_and_refund_voucher']['taxable'], 
                $data['credit_debit_Note_and_refund_voucher']['CGST'], 
                $data['credit_debit_Note_and_refund_voucher']['SGST'], 
                $data['credit_debit_Note_and_refund_voucher']['IGST'], 
                $data['credit_debit_Note_and_refund_voucher']['cess'], 
                $data['credit_debit_Note_and_refund_voucher']['TotalGST'], 
                $data['credit_debit_Note_and_refund_voucher']['InvoiceAmount']
            ],
            [
                ' - Registered Parties', 
                $data['registered_arties']['count'], 
                $data['registered_arties']['taxable'], 
                $data['registered_arties']['CGST'], 
                $data['registered_arties']['SGST'], 
                $data['registered_arties']['IGST'], 
                $data['registered_arties']['cess'], 
                $data['registered_arties']['TotalGST'], 
                $data['registered_arties']['InvoiceAmount']
            ],
            [
                ' - Unregistered Parties', 
                $data['unregistered_parties']['count'], 
                $data['unregistered_parties']['taxable'], 
                $data['unregistered_parties']['CGST'], 
                $data['unregistered_parties']['SGST'], 
                $data['unregistered_parties']['IGST'], 
                $data['unregistered_parties']['cess'], 
                $data['unregistered_parties']['TotalGST'], 
                $data['unregistered_parties']['InvoiceAmount']
            ],
            [
                ' - Refund from Advance', 
                $data['refund_from_advance']['count'], 
                $data['refund_from_advance']['taxable'], 
                $data['refund_from_advance']['CGST'], 
                $data['refund_from_advance']['SGST'], 
                $data['refund_from_advance']['IGST'], 
                $data['refund_from_advance']['cess'], 
                $data['refund_from_advance']['TotalGST'], 
                $data['refund_from_advance']['InvoiceAmount']
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
}
