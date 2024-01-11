<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExcalExportClass;

class ExcelController extends Controller
{
    public function download_excel_gstr1($year, $month, $last_six_month = 0)
    {
        $file_name = empty($last_six_month) ? 'GSTR1_' . $month . '_' . $year . '.xlsx' : 'GSTR1_last_six_month.xlsx';
        $report = 'gstr1';
        return Excel::download(new ExcalExportClass($year, $month, $last_six_month, $report), $file_name);
    }

    public function download_excel_gstr2($year, $month, $last_six_month = 0)
    {
        $file_name = empty($last_six_month) ? 'GSTR2_' . $month . '_' . $year . '.xlsx' : 'GSTR2_last_six_month.xlsx';
        $report = 'gstr2';
        return Excel::download(new ExcalExportClass($year, $month, $last_six_month, $report), $file_name);
    }
}