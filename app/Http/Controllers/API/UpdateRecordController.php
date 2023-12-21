<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpdateRecordController extends Controller
{
    public function update_product_records()
    {
        ini_set('max_execution_time', 14000);
        $exportcartdata = DB::table('exportcartdata_sample')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $product_data = $this->get_product_data(array_unique($barcodes)); 
        // dd($product_data);
        $data = [];
        foreach($product_data as $product) {
            $data[$product->barcode] = $product->idproduct_master;
        }
        $total_number_of_category =sizeof($product_data);
        $this->update_product_name($exportcartdata, $data);
        
        return response()->json(["statusCode" => 0, "message" => "Records Product Updated Successfully",], 200);
        
    }

    public function update_category_records()
    {
        $exportcartdata = DB::table('exportcartdata_sample')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $category_data = $this->get_category_data(array_unique($barcodes)); 
        $total_number_of_category =sizeof($category_data);
        $data = $this->barcode_category_map($barcodes, $category_data);
        $this->update_category_name($exportcartdata, $data);
        
        return response()->json(["statusCode" => 0, "message" => "Records Category Updated Successfully",], 200);
        
    }

    public function update_brands_records()
    {
        $exportcartdata = DB::table('exportcartdata_sample')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $brands_data = $this->get_brand_data(array_unique($barcodes)); 
        $total_number_of_brands =sizeof($brands_data);
        $data = $this->barcode_brand_map($barcodes, $brands_data);
        $this->update_brand_name($exportcartdata, $data);
        
        return response()->json(["statusCode" => 0, "message" => "Records Brands Updated Successfully",], 200);
        
    }

    public function update_sub_category_records()
    {
        $exportcartdata = DB::table('exportcartdata_sample')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $sub_category_data = $this->get_sub_category_data(array_unique($barcodes)); 
        $total_number_of_sub_category =sizeof($sub_category_data);
        $data = $this->barcode_sub_category_map($barcodes, $sub_category_data);
        $this->update_sub_category_name($exportcartdata, $data);
        

        return response()->json(["statusCode" => 0, "message" => "Records Sub Category Updated Successfully",], 200);
        
    }

    public function update_sub_sub_category_records()
    {
        $exportcartdata = DB::table('exportcartdata_sample')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $sub_sub_category_data = $this->get_sub_sub_category_data(array_unique($barcodes)); 
        $total_number_of_sub_sub_category =sizeof($sub_sub_category_data);
        $data = $this->barcode_sub_sub_category_map($barcodes, $sub_sub_category_data);
        $this->update_sub_sub_category_name($exportcartdata, $data);
        

        return response()->json(["statusCode" => 0, "message" => "Records Sub Sub Category Updated Successfully",], 200);
        
    }

    public function get_product_data($barcodes)
    {
        $sub_sub_category = DB::table('product_master')
                   ->select('idproduct_master', 'barcode')
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $sub_sub_category;           
    }


    public function get_brand_data($barcodes)
    {
        $brands = DB::table('product_master')
                   ->select('idbrand')
                   ->distinct()
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $brands; 
    }

    public function get_category_data($barcodes)
    {
        $category = DB::table('product_master')
                   ->select('idcategory')
                   ->distinct()
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $category;           
    }

    public function get_sub_category_data($barcodes)
    {
        $category = DB::table('product_master')
                   ->select('idsub_category')
                   ->distinct()
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $category;           
    }

    public function get_sub_sub_category_data($barcodes)
    {
        $sub_sub_category = DB::table('product_master')
                   ->select('idsub_sub_category')
                   ->distinct()
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $sub_sub_category;           
    }

    public function update_product_name($exportcartdata, $product_data)
    {
        foreach($exportcartdata as $item) {
            foreach($product_data as $key => $id){
                if((string)$key === $item->barcode)
                {
                    $update = $this->data_update('product_master', 'idproduct_master', $id, $item->product_name);
                }
            }
        }
    }

    public function update_category_name($exportcartdata, $category_data)
    {
        foreach($exportcartdata as $item) {
            foreach($category_data as $key => $id){
                if((string)$key === $item->barcode)
                {
                    $update = $this->data_update('category', 'idcategory', $id, $item->category);
                }
            }
        }
    }

    public function update_brand_name($exportcartdata, $brand_data)
    {
        foreach($exportcartdata as $item) {
            foreach($brand_data as $key => $id){
                if((string)$key === $item->barcode)
                {
                    $update = $this->data_update('brands', 'idbrand', $id, $item->brands);
                }
            }
        }
    }

    public function update_sub_sub_category_name($exportcartdata, $sub_sub_category_data)
    {
        foreach($exportcartdata as $item) {
            foreach($sub_sub_category_data as $key => $id){
                if((string)$key === $item->barcode)
                {
                    $update = $this->data_update('sub_sub_category', 'idsub_sub_category', $id, $item->sub_sub_category);
                }
            }
        }
    }

    public function update_sub_category_name($exportcartdata, $sub_category_data)
    {
        foreach($exportcartdata as $item) {
            foreach($sub_category_data as $key => $id){
                if((string)$key === $item->barcode)
                {
                    $update = $this->data_update('sub_category', 'idsub_category', $id, $item->sub_category);
                }
            }
        }
    }

    public function data_update($table, $filed, $id, $name)
    {
        $data = DB::table($table)->where($filed,$id)->update(array(
            'name'=>$name,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ));
    }

    public function barcode_category_map($barcodes, $category_data)
    {
        $barcode_with_category =  DB::table('product_master')
            ->select('barcode', 'idcategory')
            ->whereIn('barcode', $barcodes)
            ->get();
        $map_data = [];    

        foreach($barcode_with_category as $category) {
            foreach($category_data as $item) {
                if($category->idcategory === $item->idcategory) {
                    $map_data[$category->barcode] = $item->idcategory;
                }
            }
        }    
       return array_unique($map_data);
    }

    public function barcode_brand_map($barcodes, $brand_data)
    {
        $barcode_with_category =  DB::table('product_master')
            ->select('barcode', 'idbrand')
            ->whereIn('barcode', $barcodes)
            ->get();
        
        $map_data = [];    

        foreach($barcode_with_category as $brand) {
            foreach($brand_data as $item) {
                if($brand->idbrand === $item->idbrand) {
                    $map_data[$brand->barcode] = $item->idbrand;
                }
            }
        }    
       return array_unique($map_data);
    }

    public function barcode_sub_category_map($barcodes, $sub_category_data)
    {
        $barcode_with_sub_category =  DB::table('product_master')
            ->select('barcode', 'idsub_category')
            ->whereIn('barcode', $barcodes)
            ->get();
        $map_data = [];    

        foreach($barcode_with_sub_category as $sub_category) {
            foreach($sub_category_data as $item) {
                if($sub_category->idsub_category === $item->idsub_category) {
                    $map_data[$sub_category->barcode] = $item->idsub_category;
                }
            }
        }    
       return array_unique($map_data);
    }

    public function barcode_sub_sub_category_map($barcodes, $sub_sub_category_data)
    {
        $barcode_with_sub_sub_category =  DB::table('product_master')
            ->select('barcode', 'idsub_sub_category')
            ->whereIn('barcode', $barcodes)
            ->get();
        $map_data = [];    

        foreach($barcode_with_sub_sub_category as $sub_sub_category) {
            foreach($sub_sub_category_data as $item) {
                if($sub_sub_category->idsub_sub_category === $item->idsub_sub_category) {
                    $map_data[$sub_sub_category->barcode] = $item->idsub_sub_category;
                }
            }
        }    
       return array_unique($map_data);
    }
}
