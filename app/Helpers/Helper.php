<?php
namespace App\Helpers;
use DB;
use App\Models\Support;
use App\Models\SupportCategoryMaster;
use App\Models\ContactCategoryMaster;
use App\Models\ShippingChargeMaster;
use App\Models\smsTemplateMaster;
use App\Models\emailTemplateMaster;
use Mail;

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

    public static function getIssues($support_id=''){
        $issueDetail = Support::select();
        if($support_id!=''){
            $issueDetail->where('id',$support_id);
        }
        $issueDetails = $issueDetail->with('details')->orderBy('id','desc')->get();
        return $issueDetails;
    }
    public static function getIssuesByCustomer($idcustomer=''){
        $issueDetail = Support::select();
        if($idcustomer!=''){
            $issueDetail->where('idcustomer',$idcustomer);
        }
        $issueDetails = $issueDetail->with('details')->orderBy('id','desc')->get();
        return $issueDetails;
    }
    public static function getSupportCategories(){
        $support_categories = SupportCategoryMaster::where('status',1)->orderBy('id','asc')->get();
        return $support_categories;
    }
    public static function getContactCategories(){
        $contact_categories = ContactCategoryMaster::where('status',1)->orderBy('id','asc')->with('subcategory')->get();
        return $contact_categories;
    }

    public static function getShippingCharge($orderAmount){
        $chargedetails = ShippingChargeMaster::where('status',1)->orderBy('order_amount','asc')->get();
        if($chargedetails){
            foreach($chargedetails as $c){
                if($c->order_amount>=$orderAmount){
                    return $c->shipping_charge;
                }
            }
        }
        return 0;
    }
    public static function sendSMSWithtemplateData($template_id,$idcustomer,$variables){
        $smsTemplate=smsTemplateMaster::where('id',$template_id)->where('status',1)->first();
        $userinfo=DB::table('users')->where('id',$idcustomer)->first();
        if($smsTemplate){
            $template_content = $smsTemplate->body;
            foreach($variables as $var=>$val) {
                $template_content = str_replace('{'.$var.'}', $val, $template_content);
            }
            return $template_content;
        }
    }

    public static function sendEmailWithtemplateData($template_id,$idcustomer,$variables){
        $emailTemplate=emailTemplateMaster::where('id',$template_id)->where('status',1)->first();
        if($emailTemplate){
            $emailData=[];
            $userinfo=DB::table('users')->where('id',$idcustomer)->first();
            if($userinfo){
                $subject = $emailTemplate->subject;          
                $template_content = $emailTemplate->body;
                foreach($variables as $var=>$val) {
                    $template_content = str_replace('{'.$var.'}', $val, $template_content);
                    $subject = str_replace('{'.$var.'}', $val, $subject);
                }
                
                $data = array('subject'=>$subject,'email'=>$userinfo->email,'body_text'=>$template_content);

                // send email
                $send=Mail::send('email/template', $data, function($message) use ($data) {
                    $message->to($data['email'])->subject($data['subject']);
                    $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
                });
                if($send){
                    return [
                        'statusCode' => '0',
                        'message' => 'success'
                    ];
                }else{
                    return [
                        'statusCode' => '1',
                        'message' => 'email sending failed'
                    ];
                }
            }else{
                return [
                    'statusCode' => '1',
                    'message' => 'no user data found'
                ];
            }
        }else{
            return [
                'statusCode' => '1',
                'message' => 'no template data found'
            ];
        }
    }
}