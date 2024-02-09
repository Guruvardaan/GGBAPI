<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExcalExportClass;
use App\Exports\GstDetaliExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
    public function download_excel_gstr1_detail()
    {
        try {
            $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : Carbon::now()->startOfMonth()->format('Y-m-d');;
            $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  Carbon::now()->format('Y-m-d');
            $time = Carbon::now()->format('H_i_s');
            $file_name = 'GSTR1_detail_' . str_replace("-","_",$start_date)  . '_to_' . str_replace("-","_",$end_date) . '_' . $time . '.xlsx';
            $report = 'gstr1_detail';
    
            $diskPath = 'gstr1_detail';
            
            Storage::disk('public')->put($diskPath . '/' .$file_name, Excel::raw(new GstDetaliExport($start_date, $end_date, $report), \Maatwebsite\Excel\Excel::XLSX));
            return response()->json(["statusCode" => 0, "message" => $file_name . " file imported sucessfully,"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to order data', 'details' => $e->getMessage()], 500);
        }
    }    
    public function download_excel_gstr2_detail()
    {
        try {
            $start_date =  !empty($_GET['start_date']) ? $_GET['start_date'] : Carbon::now()->startOfMonth()->format('Y-m-d');;
            $end_date = !empty($_GET['end_date'])? $_GET['end_date'] :  Carbon::now()->format('Y-m-d');
            $time = Carbon::now()->format('H_i_s');
            $file_name = 'GSTR2_detail_' . str_replace("-","_",$start_date)  . '_to_' . str_replace("-","_",$end_date) . '_' . $time . '.xlsx';
            $report = 'gstr2_detail';
    
            $diskPath = 'gstr2_detail';
            
            Storage::disk('public')->put($diskPath . '/' .$file_name, Excel::raw(new GstDetaliExport($start_date, $end_date, $report), \Maatwebsite\Excel\Excel::XLSX));
            return response()->json(["statusCode" => 0, "message" => $file_name . " file imported sucessfully,"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to order data', 'details' => $e->getMessage()], 500);
        }
    }
}