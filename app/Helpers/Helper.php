<?php
namespace App\Helpers;
use DB;

class Helper
{
    public static function getBanners($banner_type='',$type='',$type_id='')
    {
        $bannerDetail = DB::table('banners');
        if($banner_type!=''){
            $bannerDetail->where('banner_type',$banner_type);
        }
        if($type!=''){
            $bannerDetail->where('type',$type);
        }
        if($type_id!=''){
            $bannerDetail->where('type_id',$type_id);
        }
        $bannerDetails = $bannerDetail->get();
        return $bannerDetails;
    }

    public static function prepareProductQuery() {
        $productmaster = DB::table('product_master')
                ->leftJoin('sub_sub_category', 'sub_sub_category.idsub_sub_category', '=', 'product_master.idsub_sub_category')
                ->leftJoin('sub_category', 'sub_category.idsub_category', '=', 'product_master.idsub_category')
                ->leftJoin('category', 'category.idcategory', '=', 'product_master.idcategory')
                ->leftJoin('brands', 'brands.idbrand', '=', 'product_master.idbrand')
                ->leftJoin('inventory', 'inventory.idproduct_master', '=', 'product_master.idproduct_master')
                ->select(
                    'product_master.idbrand',
                    'brands.name AS brand',
                    'product_master.idproduct_master',
                    'product_master.idcategory',
                    'category.name AS category',
                    'product_master.idsub_category',
                    'sub_category.name AS scategory',
                    'product_master.idsub_sub_category',
                    'sub_sub_category.name AS sscategory',
                    'product_master.name AS prod_name',
                    'product_master.description',
                    'product_master.barcode',
                    'product_master.hsn',
                    'product_master.sgst',
                    'product_master.cgst',
                    'product_master.status',
                    'inventory.quantity',
                    'inventory.idinventory',
                    'inventory.selling_price',
                    'inventory.mrp',
                    'inventory.discount',
                    'inventory.product',
                    'inventory.copartner',
                    'inventory.land',
                    'inventory.instant_discount_percent',
                    'inventory.listing_type',
                    'inventory.listing_type AS origListType'
                );
        return $productmaster;
    }
}