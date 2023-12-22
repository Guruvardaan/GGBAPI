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
        $exportcartdata = DB::table('export_cat_data')
                ->select('*')
                ->get();        
        $barcodes = [];      
        foreach($exportcartdata as $item) {
            $barcodes[]= $item->barcode;
            
        }    
       
        $product_data = $this->get_product_data(array_unique($barcodes)); 
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
        $categories = DB::table('export_cat_data')
                ->select('category As name')
                ->groupBy('category')
                ->get();         

        $categories_with_ids = $this->get_categories_with_id($categories);
        $categories_without_ids = $this->get_categories_without_id($categories, $categories_with_ids);

        $cat_map = $this->category_barcode_map($categories);
        
        $barcode_cat_map = [];
        foreach($cat_map as $key => $cat_barcode) {
            foreach($cat_barcode as $barcodes) {
               foreach($barcodes as $barcode) {
                $barcode_cat_map[$key][] = $barcode->barcode; 
               }
            }
        }

        $categories_with_ids_map_data = $this->get_map_with_id_data($barcode_cat_map, $categories_with_ids);
        $categories_without_ids_map_data = $this->get_map_without_id_data($barcode_cat_map, $categories_without_ids);
        foreach($categories_with_ids_map_data as $data) {
            if(!empty($data['barcodes']) && !empty($data['id'])) {
                $this->modify_product_cat_ids($data['barcodes'], $data['id']);
            }
        }

        foreach($categories_without_ids_map_data as $key => $data) {
            $cat_id = DB::table('category')->select('idcategory')->where('name', $key)->first();
            if(empty($cat_id)) {
                $cat_id = $this->insert_category($key);
            } 
            $this->modify_product_cat_ids($data, $cat_id);
        }

        return response()->json(["statusCode" => 0, "message" => "Records Category Updated Successfully",], 200);
        
    }

    public function get_categories_with_id($categories) 
    {
        $data = [];
        foreach($categories as $category) {
            $id = DB::table('category')
                    ->select('idcategory')
                    ->where('name', $category->name)
                    ->first();
            if(!empty($id->idcategory)) {
                $data[$category->name] = $id->idcategory; 
            }            
        } 
        return $data; 
    }

    public function get_categories_without_id($categories, $categories_with_ids) 
    {
        $data = [];
        foreach($categories as $category) {
            if(!array_key_exists($category->name, $categories_with_ids)) {
                $data[] = $category->name;
            }
        }
        return $data; 
    }

    public function category_barcode_map($categories)
    {
        $get_barcode = DB::table('export_cat_data')
        ->select('barcode')
        ->distinct()
        ->get();

        $barcode_unique = [];

        foreach($get_barcode as $barcode) {
            $barcode_unique[] = $barcode->barcode;
        }
        
        $data = [];
        
        foreach($categories as $category) {
            $barcodes =  DB::table('export_cat_data')
                        ->select('barcode')
                        ->distinct()
                        ->where('category', $category->name)
                        ->whereIn('barcode', $barcode_unique)
                        ->get();            
            $data[$category->name]['barcode'] = $barcodes;                   
        }
        // $total = 0;
        // foreach($data as $item) {
        //     $total += sizeof($item['barcode']);
        // }
        // dd($total);
        return $data;
    }

    public function get_map_with_id_data($barcode_cat_map, $array)
    {
        $data = [];
        foreach($barcode_cat_map as $key => $cat_data) {
            if(array_key_exists($key, $array)) {
                $data[$key]['barcodes'] = $cat_data;
                $data[$key]['id'] = $array[$key];
            }
        }
        return $data;
    }

    public function get_map_without_id_data($barcode_cat_map, $array)
    {
        $data = [];
        foreach($barcode_cat_map as $key => $cat_data) {
            if(in_array($key, $array)) {
                $data[$key] = $cat_data;
            }
        }
        return $data;
    }

    public function get_category_id_form_product($data) {
        $cat_ids = DB::table('product_master')
                    ->select('idcategory')
                    ->distinct()
                    ->whereIn('barcode', $data)
                    ->get();
        return $cat_ids;            
    }

    public function modify_product_cat_ids($barcodes, $id)
    {
        // dd($barcodes, $id);
        $data = DB::table('product_master')->whereIn('barcode',$barcodes)->update(array(
            'idcategory'=>$id,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ));
    }

    public function insert_category($cat)
    {
        $insert_data = [
            'name' => $cat,
            'image' => 'no-name.png',
            'cat_icon' => 'no-name.png',
            'description' => '',
            'category_for' => 1,
            'created_by' => -1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
            'updated_by' => -1,
            'status' => 1
        ];

       $id =  DB::table('category')->insertGetId($insert_data);
       return $id;
    }
    
    public function update_brands_records()
    {
        $exportcartdata = DB::table('export_cat_data')
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
        $sub_categories = DB::table('export_cat_data')
            ->select('sub_category As name')
            ->groupBy('sub_category')
            ->get();    

        $sub_categories_with_ids = $this->get_sub_categories_with_id($sub_categories);
        $sub_categories_without_ids = $this->get_sub_categories_without_id($sub_categories, $sub_categories_with_ids);
 
        // dd($sub_categories_with_ids);

        $sub_cat_map = $this->sub_category_barcode_map($sub_categories);
        // dd($sub_cat_map);

        $barcode_sub_cat_map = [];
        foreach($sub_cat_map as $key => $sub_cat_barcode) {
            foreach($sub_cat_barcode as $barcodes) {
               foreach($barcodes as $barcode) {
                $barcode_sub_cat_map[$key][] = $barcode->barcode; 
               }
            }
        }

        $categories_with_ids_map_data = $this->get_sub_map_with_id_data($barcode_sub_cat_map, $sub_categories_with_ids);
        $categories_without_ids_map_data = $this->get_sub_map_without_id_data($barcode_sub_cat_map, $sub_categories_without_ids);

        foreach($categories_with_ids_map_data as $data) {
            if(!empty($data['barcodes']) && !empty($data['id']) && !empty($data['category'])) {
                $cat_id = DB::table('category')->select('idcategory')->where('name', $data['category'])->first();
                $this->modify_product_sub_cat_ids($data['barcodes'], $data['id'], $cat_id->idcategory);
            }
        }

        foreach($categories_without_ids_map_data as $key => $data) {
            $sub_cat_id = DB::table('sub_category')->select('idsub_category')->where('name', $key)->first();
            $cat_id = DB::table('category')->select('idcategory')->where('name', $data['category']['category'])->first();
            if(empty($sub_cat_id)) {
                $sub_cat_id = $this->insert_sub_category($key, $cat_id->idcategory);
            } 
            $this->modify_product_sub_cat_ids($data['barcodes'], $sub_cat_id, $cat_id->idcategory);
        }
        
        return response()->json(["statusCode" => 0, "message" => "Records Sub Category Updated Successfully",], 200);
        
    }

    public function get_sub_categories_with_id($sub_categories) 
    {
        $data = [];
        foreach($sub_categories as $sub_category) {
            $id = DB::table('sub_category')
                    ->select('idsub_category', 'name', 'idcategory')
                    ->where('name', $sub_category->name)
                    ->first();
            if(!empty($id->idsub_category)) {
                $category = DB::table('export_cat_data')
                    ->select('category As name')
                    ->distinct()
                    ->where('sub_category', $sub_category->name)
                    ->first(); 
                    $cat_id = DB::table('category')->select('idcategory')->where('name', $category->name)->first();
                    if($id->idcategory === $cat_id->idcategory) {
                        $data[$sub_category->name]['id'] = $id->idsub_category;
                        $data[$sub_category->name]['category'] =  $category->name;
                    } else {
                        DB::table('sub_category')->where('idsub_category', $id->idsub_category)->update(array(
                            'idcategory' => $cat_id->idcategory,
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ));
                    }   
            }
        } 
        // dd($data);
        return $data; 
    }

    public function get_sub_categories_without_id($sub_categories, $sub_categories_with_ids) 
    {
        
        $data = [];
        foreach($sub_categories as $sub_category) {
            if(!array_key_exists($sub_category->name, $sub_categories_with_ids)) {
                $category = DB::table('export_cat_data')
                    ->select('category As name')
                    ->distinct()
                    ->where('sub_category', $sub_category->name)
                    ->first();
                $data[$sub_category->name]['category'] = $category->name;
                
            }
        }
        // dd($data);
        return $data; 
    }

    public function sub_category_barcode_map($sub_categories)
    {
        $data = [];
        
        foreach($sub_categories as $sub_category) {
            $barcodes =  DB::table('export_cat_data')
                        ->select('barcode')
                        ->distinct()
                        ->where('sub_category', $sub_category->name)
                        ->get();            
            $data[$sub_category->name]['barcode'] = $barcodes;                   
        }
        return $data;
    }

    public function get_sub_map_with_id_data($barcode_sub_cat_map, $array)
    {
        $data = [];
        foreach($barcode_sub_cat_map as $key => $sub_cat_data) {
            if(array_key_exists($key, $array)) {
                $data[$key]['barcodes'] = $sub_cat_data;
                $data[$key]['id'] = $array[$key]['id'];
                $data[$key]['category'] = $array[$key]['category'];
                
            }
        }
        return $data;
    }

    public function modify_product_sub_cat_ids($barcodes, $id, $category)
    {
        // dd($barcodes, $id, $category);
        $data = DB::table('product_master')->whereIn('barcode',$barcodes)->where('idcategory', $category)->update(array(
            'idsub_category'=>$id,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ));
    }


    public function get_sub_map_without_id_data($barcode_sub_cat_map, $array)
    {
        $data = [];
        // dd($array);
        foreach($barcode_sub_cat_map as $key => $sub_cat_data) {
            if(array_key_exists($key, $array)) {
                $data[$key]['barcodes'] = $sub_cat_data;
                $data[$key]['category'] = $array[$key];
            }
        }
        return $data;
    }

    public function insert_sub_category($sub_cat, $cat_id)
    {
        $insert_data = [
            'name' => $sub_cat,
            'idcategory' => $cat_id,
            'image' => 'no-name.png',
            'cat_icon' => 'no-name.png',
            'description' => '',
            'category_for' => 1,
            'created_by' => -1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
            'updated_by' => -1,
            'status' => 1
        ];

       $id =  DB::table('sub_category')->insertGetId($insert_data);
       return $id;
    }

    public function update_sub_sub_category_records()
    {
        ini_set('max_execution_time', 14000);
        $barcodes = DB::table('export_cat_data')
            ->select('barcode')
            ->distinct()
            ->get();
        $barcodes_unique = [];
        foreach($barcodes as $barcode) {
            $barcodes_unique[] = $barcode->barcode;
        }   

        $sub_sub_categories = DB::table('product_master')
            ->select('idsub_sub_category', 'idsub_category','idcategory', 'barcode')
            ->whereIn('barcode', $barcodes_unique)
            ->get();    
            
         $data = [];   
        foreach($sub_sub_categories as $sub_sub_category) {
            $export_data = DB::table('export_cat_data')
            ->select('sub_sub_category', 'sub_category')
            ->where('barcode', $sub_sub_category->barcode)
            ->first();

            $sub_sub_category_data = DB::table('sub_sub_category')
            ->select('idsub_sub_category')
            ->where('name', $export_data->sub_sub_category)
            ->where('idsub_category', $sub_sub_category->idsub_category)
            ->where('idcategory', $sub_sub_category->idcategory)
            ->first();    

            DB::table('product_master')->where('barcode', $sub_sub_category->barcode)->update(array(
                'idsub_sub_category' => !empty($sub_sub_category_data->idsub_sub_category) ? $sub_sub_category_data->idsub_sub_category : null,
                'updated_at' => Carbon::now()->toDateTimeString(),
            ));

            $data[$sub_sub_category->barcode]['idcategory'] = $sub_sub_category->idcategory;
            $data[$sub_sub_category->barcode]['idsub_category'] = $sub_sub_category->idsub_category;
            $data[$sub_sub_category->barcode]['idsub_sub_category'] = !empty($sub_sub_category_data->idsub_sub_category) ? $sub_sub_category_data->idsub_sub_category : null;
            $data[$sub_sub_category->barcode]['name'] = $export_data->sub_category;
        }  
       foreach($data as $key => $update_category) {
        if(!empty($update_category->idsub_sub_category)) {
            $this->sub_sub_category_update($update_category->idsub_sub_category, $update_category->name, $update_category->idcategory, $update_category->idsub_category);
        } else {
            $insert_data = [
                'name' => $update_category->name,
                'idcategory' => $update_category->idcategory,
                'idsub_sub_category' => $update_category->idsub_category,
                'image' => 'no-name.png',
                'cat_icon' => 'no-name.png',
                'description' => '',
                'category_for' => 1,
                'created_by' => -1,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
                'updated_by' => -1,
                'status' => 1
            ];
    
           $id =  DB::table('sub_sub_category')->insertGetId($insert_data);
           DB::table('product_master')->where('barcode', $key)->update(array(
            'idsub_sub_category' => $id,
            'updated_at' => Carbon::now()->toDateTimeString(),
          ));
            }
       }
        

        return response()->json(["statusCode" => 0, "message" => "Records Sub Sub Category Updated Successfully",], 200);
        
    }

    public function sub_sub_category_update($id, $name, $cat_id, $sub_cat_id) {
        DB::table('sub_sub_category')->where('idcategory', $cat_id)->where('idsub_category', $sub_cat_id)->update(array(
            'name' => $name,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ));
    }


    public function data_update($table, $filed, $id, $name)
    {
        $data = DB::table($table)->where($filed,$id)->update(array(
            'name'=>$name,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ));
    }

    // public function category_data_update($table, $data)
    // {
    //     foreach($data as $key=>$category_data) 
    //     $data = DB::table($table)->where($filed,$id)->update(array(
    //         'name'=>$name,
    //         'updated_at' => Carbon::now()->toDateTimeString(),
    //     ));
    // }

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

    public function get_product_data($barcodes)
    {
        $sub_sub_category = DB::table('product_master')
                   ->select('idproduct_master', 'barcode')
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

    public function get_brand_data($barcodes)
    {
        $brands = DB::table('product_master')
                   ->select('idbrand')
                   ->distinct()
                   ->whereIn('barcode', $barcodes)
                   ->get();
        return $brands; 
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

}
