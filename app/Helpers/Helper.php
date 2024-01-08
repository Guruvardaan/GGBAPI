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
use Illuminate\Support\Carbon;
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

    public static function inventory_threshold($idproduct_master, $idstore_warehouse, $quantity, $idproduct_batch)
    {
        $inventory_threshold = DB::table('inventory_threshold')->where('idproduct_master', $idproduct_master)->first();
        $inventory = DB::table('inventory')->select('quantity')->where('idproduct_master', $idproduct_master)->first();
        if(!empty($inventory_threshold) && !empty($inventory)){
            if($inventory_threshold->threshold_quantity <= $inventory->quantity) {
                try{
                    $request_data = [
                        'idstore_warehouse_to' => $inventory_threshold->idstore_warehouse,
                        'idstore_warehouse_from' => $idstore_warehouse,
                        'request_type' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'status' => 1
                    ];
    
                    $idstore_request = DB::table('store_request')->insertGetId($request_data);

                    $request_detail_data = [
                        'idstore_request' => $idstore_request,
                        'idproduct_master' => $idproduct_master,
                        'idproduct_batch' => $idproduct_batch,
                        'quantity' => $inventory_threshold->sent_quantity,
                        'quantity_sent' => $inventory_threshold->sent_quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'status' => 1
                    ];
                   DB::table('store_request_detail')->insertGetId($request_detail_data);
                } catch(\Exception $e) {
                    return response()->json(["statusCode" => 1, 'message' => $e->getMessage()], 500);
                }
            } 
        }
    }

    public static function getBatchesAndMemberPrices($productList, $idStore) {
        $allProds = [];
            $mplans = DB::table('membership_plan')
                ->where('status', 1)
                ->where('instant_discount', 0)
                ->get();
            foreach ($productList as $pro) {
                $pro->sellingPriceForInstantDisc = $pro->selling_price - ($pro->selling_price * ($pro->instant_discount_percent) / 100);
                $pro->batches = DB::table('product_batch')->where('idstore_warehouse', $idStore)
                    ->where('idproduct_master', $pro->idproduct_master)
                    ->where('status', 1)
                    ->get();
                $pro->selected_batch = null;
                if (count($pro->batches) == 1) {
                    $pro->selected_batch = $pro->batches[0];
                }
                $disc = [];
                foreach ($mplans as $membership) {
                    $curDesc = [];
                    $curDesc['idmembership_plan'] = $membership->idmembership_plan;
                    $curDesc['name'] = $membership->name;
                    $curDesc['commission'] = $membership->commission;
                    $curDesc['selling_price'] = $pro->selling_price - ($pro->selling_price * ($membership->commission) / 100);
                    $disc[] = $curDesc;
                }
                $pro->member_price = $disc;
                $allProds[] = $pro;
            }
            return $allProds;
    }

    public static function gstr1_report($year, $month, $last_six_month = 0)
    {
        $data_without_date = DB::table('order_detail')
                    ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                    ->select('customer_order.idcustomer_order', 'order_detail.quantity', 'order_detail.total_price', 'order_detail.total_cgst', 'order_detail.total_sgst', 'customer_order.created_at')
                    ->where('order_detail.total_cgst', '<>', 0)
                    ->where('order_detail.total_sgst', '<>', 0);
        
        if(!empty($last_six_month)) {
            $data_without_date->whereBetween('customer_order.created_at', [
                Carbon::now()->subMonths(6)->startOfDay(),
                Carbon::now()->endOfDay()
            ]);
        } else {
            $data_without_date->whereYear('customer_order.created_at', $year);
            $data_without_date->whereMonth('customer_order.created_at', $month);
        }
        $get_data = $data_without_date->get();
        
         $get_data_nil_reted_data = DB::table('order_detail')
                                ->leftJoin('customer_order', 'customer_order.idcustomer_order', '=', 'order_detail.idcustomer_order')
                                ->select('customer_order.idcustomer_order', 'order_detail.quantity', 'order_detail.total_price', 'order_detail.total_cgst', 'order_detail.total_sgst', 'customer_order.created_at')
                                ->where('order_detail.total_cgst', '=', 0)
                                ->where('order_detail.total_sgst', '=', 0);
                                
        if(!empty($last_six_month)) {
            $get_data_nil_reted_data->whereBetween('customer_order.created_at', [
                Carbon::now()->subMonths(6)->startOfDay(),
                Carbon::now()->endOfDay()
            ]);
        } else {
            $get_data_nil_reted_data->whereYear('customer_order.created_at', $year);
            $get_data_nil_reted_data->whereMonth('customer_order.created_at', $month);
        }
        $get_data_nil_reted = $get_data_nil_reted_data->get();                       

        $data = [];
        $data['period']['start_date'] = empty($last_six_month) ? Carbon::create($year, $month)->startOfMonth()->format('d/m/Y') : Carbon::now()->subMonths(6)->startOfDay()->format('d/m/Y');
        $data['period']['end_date'] = empty($last_six_month) ? Carbon::create($year, $month)->lastOfMonth()->format('d/m/Y') : Carbon::now()->endOfDay()->format('d/m/Y');   
        
        $data['business_to_bisiness'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];

        $gross_counter = 0;
        $gross_amount = 0;
        $gross_cgst = 0;
        $gross_sgst = 0;
        $gross_igst = 0;
        $gross_cess = 0;            
        foreach($get_data as $order) {
            $gross_counter += $order->quantity;
            $gross_amount += $order->total_price;
            $gross_cgst += $order->total_cgst;
            $gross_sgst += $order->total_sgst;
        }
        $total_gst = $gross_cgst + $gross_sgst;
        $invoice_amount = $gross_amount + $total_gst;

        $data['business_to_customer_small'] = [
            'count' => ($total_gst < 250000) ? $gross_counter : 0,
            'taxable' => ($total_gst < 250000) ? round($gross_amount, 4) : 0,
            'CGST' => ($total_gst < 250000) ? round($gross_cgst,4) : 0,
            'SGST' => ($total_gst < 250000) ? round($gross_sgst, 4) : 0,
            'IGST' => ($total_gst < 250000) ? $gross_igst : 0,
            'cess' => ($total_gst < 250000) ? $gross_cess : 0,
            'TotalGST' => ($total_gst < 250000) ? round($total_gst, 4) : 0,
            'InvoiceAmount' => ($total_gst < 250000) ? round($invoice_amount, 4) : 0,
        ]; 

        $data['business_to_customer_large'] = [
            'count' => ($total_gst > 250000) ? $gross_counter : 0,
            'taxable' => ($total_gst > 250000) ? round($gross_amount, 4) : 0,
            'CGST' => ($total_gst > 250000) ? round($gross_cgst,4) : 0,
            'SGST' => ($total_gst > 250000) ? round($gross_sgst, 4) : 0,
            'IGST' => ($total_gst > 250000) ? $gross_igst : 0,
            'cess' => ($total_gst > 250000) ? $gross_cess : 0,
            'TotalGST' => ($total_gst > 250000) ? round($total_gst, 4) : 0,
            'InvoiceAmount' => ($total_gst > 250000) ? round($invoice_amount, 4) : 0,
        ]; 
        
        $gross__nil_reted_counter = 0;
        $gross_nil_reted_amount = 0;
        foreach($get_data_nil_reted as $order) {
            $gross__nil_reted_counter += $order->quantity;
            $gross_nil_reted_amount += $order->total_price;
        }

        $data['nil_rated'] = [
            'count' => $gross__nil_reted_counter,
            'taxable' => round($gross_nil_reted_amount, 4),
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => round($gross_nil_reted_amount, 4),
        ]; 
        $data['exempted'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['export_invoices'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['tax_iability_on_advance'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['set_off_tax_on_advance_of_prior_period'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['credit_debit_Note_and_refund_voucher'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['registered_arties'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['unregistered_parties'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];
        $data['refund_from_advance'] = [
            'count' => 0,
            'taxable' => 0,
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => 0
        ];

        return $data;
    }

    public static function gstr2_report($year, $month, $last_six_month = 0)
    {
        $data_without_date = DB::table('vendor_purchases_detail')
                    ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                    ->select('vendor_purchases_detail.idproduct_master','vendor_purchases_detail.quantity', 'vendor_purchases_detail.unit_purchase_price', 'product_master.cgst', 'product_master.sgst')
                    ->where('product_master.cgst', '<>', 0)
                    ->where('product_master.sgst', '<>', 0);

        if(!empty($last_six_month)) {
            $data_without_date->whereBetween('vendor_purchases_detail.created_at', [
                Carbon::now()->subMonths(6)->startOfDay(),
                Carbon::now()->endOfDay()
            ]);
        } else {
            $data_without_date->whereYear('vendor_purchases_detail.created_at', $year);
            $data_without_date->whereMonth('vendor_purchases_detail.created_at', $month);
        }

        $get_data = $data_without_date->get();

        $data = [];
        $data['period']['start_date'] = empty($last_six_month) ? Carbon::create($year, $month)->startOfMonth()->format('d/m/Y') : Carbon::now()->subMonths(6)->startOfDay()->format('d/m/Y');
        $data['period']['end_date'] = empty($last_six_month) ? Carbon::create($year, $month)->lastOfMonth()->format('d/m/Y') : Carbon::now()->endOfDay()->format('d/m/Y');            
        
        $gross_counter = 0;
        $gross_amount = 0;
        $gross_cgst = 0;
        $gross_sgst = 0;
        $gross_igst = 0;
        $gross_cess = 0;            
        foreach($get_data as $order) {
            $gross_counter += $order->quantity;
            $gross_amount += $order->unit_purchase_price;
            $gross_cgst += $order->cgst;
            $gross_sgst += $order->sgst;
        }
        $total_gst = $gross_cgst + $gross_sgst;
        $invoice_amount = $gross_amount + $total_gst;
        $data['business_to_business'] = [
            'count' => $gross_counter,
            'taxable' => round($gross_amount, 4),
            'CGST' => round($gross_cgst,4),
            'SGST' => round($gross_sgst, 4),
            'IGST' => $gross_igst,
            'cess' => $gross_cess,
            'TotalGST' => round($total_gst, 4),
            'InvoiceAmount' => round($invoice_amount, 4),
        ];

        $data_without_nil_reted_date = DB::table('vendor_purchases_detail')
                    ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'vendor_purchases_detail.idproduct_master')
                    ->select('vendor_purchases_detail.idproduct_master','vendor_purchases_detail.quantity', 'vendor_purchases_detail.unit_purchase_price', 'product_master.cgst', 'product_master.sgst')
                    ->where('product_master.cgst', '=', 0)
                    ->where('product_master.sgst', '=', 0);

        if(!empty($last_six_month)) {
            $data_without_nil_reted_date->whereBetween('vendor_purchases_detail.created_at', [
                Carbon::now()->subMonths(6)->startOfDay(),
                Carbon::now()->endOfDay()
            ]);
        } else {
            $data_without_nil_reted_date->whereYear('vendor_purchases_detail.created_at', $year);
            $data_without_nil_reted_date->whereMonth('vendor_purchases_detail.created_at', $month);
        }

        $get_nil_reted_data = $data_without_nil_reted_date->get();

        $gross__nil_reted_counter = 0;
        $gross_nil_reted_amount = 0;
        foreach($get_nil_reted_data as $order) {
            $gross__nil_reted_counter += $order->quantity;
            $gross_nil_reted_amount += $order->unit_purchase_price;
        }

        $data['nil_rated'] = [
            'count' => $gross__nil_reted_counter,
            'taxable' => round($gross_nil_reted_amount, 4),
            'CGST' =>0,
            'SGST' => 0,
            'IGST' => 0,
            'cess' => 0,
            'TotalGST' => 0,
            'InvoiceAmount' => round($gross_nil_reted_amount, 4),
        ]; 

        return $data;
    }
    
    public static function get_b2c_invoice($year, $month, $start_date, $end_date)
    {
        $B2C_invoice_data = DB::table('customer_order')
                                   ->leftJoin('users', 'users.id', '=', 'customer_order.idcustomer') 
                                   ->select('users.name as desc', 'customer_order.created_at as invoice_date', 'customer_order.idcustomer_order as invoice_no', 'customer_order.total_price as invoice_value');
                                 
        if(!empty($start_date) &&  !empty($end_date)) {
            $B2C_invoice_data->whereBetween('customer_order.created_at',[$start_date, $end_date]);
        } else {
            $B2C_invoice_data->whereYear('customer_order.created_at', $year);
            $B2C_invoice_data->whereMonth('customer_order.created_at', $month);
        } 
        $B2C_invoice = $B2C_invoice_data->get();                        

        $total_quantity = 0.00;
        $total_amount = 0.00;
        $total_taxable_amount = 0.00;
        $total_sgst = 0.00;
        $total_cgst = 0.00;
        $total_igst = 0.00;
        $total_cess = 0.00;
        $total_gst = 0.00;
        foreach($B2C_invoice as $order)
        {
            $date = Carbon::parse($order->invoice_date);
            $order->invoice_date = $date->format('d-M-y');
            $order->desc = !empty($order->desc) ? $order->desc : 'Cash Sales and Purchase';
            $order->local_or_central = 'Local';
            $order->invoice_type = 'Inventory';
            $order->GSTIN = '';
            $products = self::get_order_detail_b2c_invoice($order->invoice_no);
            $product_data = [];
            foreach($products as $key => $product){
                $product_data[$key]['HSN_code'] = $product->HSN_code;
                $product_data[$key]['quantity'] = $product->quantity;
                $product_data[$key]['amount'] = $product->amount;
                $sgst_amount = !empty($product->SGST) ? ($product->amount * $product->SGST)/100 : 0;
                $cgst_amount = !empty($product->CGST) ? ($product->amount * $product->CGST)/100 : 0;
                $taxable_amount = $product->amount - $cgst_amount - $sgst_amount;
                $product_data[$key]['taxable_amount'] = $taxable_amount;
                $product_data[$key]['SGST_pr'] = $product->SGST;
                $product_data[$key]['SGST_amount'] = $sgst_amount;
                $product_data[$key]['CGST_pr'] = $product->CGST;
                $product_data[$key]['CGST_amount'] = $cgst_amount;
                $product_data[$key]['IGST_pr'] = 0.00;
                $product_data[$key]['IGST_amount'] = 0.00;
                $product_data[$key]['cess'] = 0.00;
                $product_data[$key]['total_gst'] = $sgst_amount + $sgst_amount;
                $total_quantity += $product->quantity;
                $total_amount += $product->amount;
                $total_taxable_amount += $taxable_amount;
                $total_sgst += $sgst_amount;
                $total_cgst += $cgst_amount;
                $total_gst += $sgst_amount + $sgst_amount;
            }
            $order->products = $product_data;
        }    
        $total = [
            'total_quantity' => $total_quantity,
            'total_amount' => $total_amount,
            'total_taxable_amount' => $total_taxable_amount,
            'total_taxable_amount' => $total_sgst,
            'total_cgst' => $total_cgst,
            'total_igst' => $total_igst,
            'total_cess' => $total_cess,
            'total_gst' => $total_gst,
        ];
        if(!empty($B2C_invoice->toArray())) {
            $B2C_invoice['total'] = $total;
        }
        $b2c_small_invoice = [];
        $b2c_large_invoice = [];
        if($total_gst <= 250000) {
            $b2c_small_invoice = $B2C_invoice->toArray();
        } else {
            $b2c_large_invoice = $B2C_invoice;
        }

        $data['b2c_large_invoice'] = $b2c_large_invoice;
        $data['b2c_small_invoice'] = $b2c_small_invoice;
        return $data;
    }

    public static function get_order_detail_b2c_invoice($id)
    {
        $order_detail = DB::table('order_detail')
                        ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                        ->select('product_master.hsn as HSN_code', 'order_detail.quantity', 'order_detail.total_price as amount', 'order_detail.total_sgst as SGST', 'order_detail.total_cgst as CGST')
                        ->where('order_detail.idcustomer_order', $id)
                        ->where('total_sgst', '<>', 0)
                        ->where('total_cgst', '<>', 0)
                        ->get();
        return $order_detail;                 
    }

    public static function get_nil_reted_invoice($year, $month, $start_date, $end_date)
    {
        $nil_reted_data = DB::table('customer_order')
                                   ->leftJoin('users', 'users.id', '=', 'customer_order.idcustomer') 
                                   ->select('users.name as desc', 'customer_order.created_at as invoice_date', 'customer_order.idcustomer_order as invoice_no', 'customer_order.total_price as invoice_value');
        // $h = 1;
        if(!empty($start_date) &&  !empty($end_date)) {
            $nil_reted_data->whereBetween('customer_order.created_at',[$start_date, $end_date]);
        } else {
            $nil_reted_data->whereYear('customer_order.created_at', $year);
            $nil_reted_data->whereMonth('customer_order.created_at', $month);
        } 
        $nil_reted = $nil_reted_data->get();                           

        $total_quantity = 0.00;
        $total_amount = 0.00;
        $total_taxable_amount = 0.00;
        $total_sgst = 0.00;
        $total_cgst = 0.00;
        $total_igst = 0.00;
        $total_cess = 0.00;
        $total_gst = 0.00;
        foreach($nil_reted as $order)
        {
            $date = Carbon::parse($order->invoice_date);
            $order->invoice_date = $date->format('d-M-y');
            $order->desc = !empty($order->desc) ? $order->desc : 'Cash Sales and Purchase';
            $order->local_or_central = 'Local';
            $order->invoice_type = 'Inventory';
            $order->GSTIN = '';
            $products = self::get_order_detail_nil_reted($order->invoice_no);
            $product_data = [];
            foreach($products as $key => $product){
                $product_data[$key]['HSN_code'] = $product->HSN_code;
                $product_data[$key]['quantity'] = $product->quantity;
                $product_data[$key]['amount'] = $product->amount;
                $sgst_amount = !empty($product->SGST) ? ($product->amount * $product->SGST)/100 : 0;
                $cgst_amount = !empty($product->CGST) ? ($product->amount * $product->CGST)/100 : 0;
                $taxable_amount = $product->amount - $cgst_amount - $sgst_amount;
                $product_data[$key]['taxable_amount'] = $taxable_amount;
                $product_data[$key]['SGST_pr'] = $product->SGST;
                $product_data[$key]['SGST_amount'] = $sgst_amount;
                $product_data[$key]['CGST_pr'] = $product->CGST;
                $product_data[$key]['CGST_amount'] = $cgst_amount;
                $product_data[$key]['IGST_pr'] = 0.00;
                $product_data[$key]['IGST_amount'] = 0.00;
                $product_data[$key]['cess'] = 0.00;
                $product_data[$key]['total_gst'] = $sgst_amount + $sgst_amount;
                $total_quantity += $product->quantity;
                $total_amount += $product->amount;
                $total_taxable_amount += $taxable_amount;
                $total_sgst += $sgst_amount;
                $total_cgst += $cgst_amount;
                $total_gst += $sgst_amount + $sgst_amount;
            }
            $order->products = $product_data;
        }    
        $total = [
            'total_quantity' => $total_quantity,
            'total_amount' => $total_amount,
            'total_taxable_amount' => $total_taxable_amount,
            'total_taxable_amount' => $total_sgst,
            'total_cgst' => $total_cgst,
            'total_igst' => $total_igst,
            'total_cess' => $total_cess,
            'total_gst' => $total_gst,
        ];
        $nil_reted_data = [];
        foreach($nil_reted as $order)
        {
            if(!empty($order->products)){
                $nil_reted_data[] = $order;
            }
        }
        if(!empty($nil_reted_data)) {
            $nil_reted_data['total'] = $total;
        }
        return $nil_reted_data;
    }

    public static function get_order_detail_nil_reted($id)
    {
        $order_detail = DB::table('order_detail')
                        ->leftJoin('product_master', 'product_master.idproduct_master', '=', 'order_detail.idproduct_master')
                        ->select('product_master.hsn as HSN_code', 'order_detail.quantity', 'order_detail.total_price as amount', 'order_detail.total_sgst as SGST', 'order_detail.total_cgst as CGST')
                        ->where('order_detail.idcustomer_order', $id)
                        ->where('total_sgst', '=', 0)
                        ->where('total_cgst', '=', 0)
                        ->get();
        return $order_detail;
    }
}