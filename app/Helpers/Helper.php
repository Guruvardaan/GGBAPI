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
}