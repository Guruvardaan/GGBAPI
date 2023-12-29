<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use DB;
use Helper;
use Mail;

class ContactController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function createContact(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'description'=>'required',
            'category'=>'required',
            'sub_category'=>'sometimes|required',
            'idcustomer_order'=>'sometimes|required'
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json([
                'statusCode' => '1',
                'message' => 'All fields are required',
                'data' => $errors->toJson()
            ]);
        }
        DB::beginTransaction();
        try {
            $create_contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'description'=>$request->description,
                'category'=>$request->category,
                'sub_category'=>$request->sub_category?$request->sub_category:'',
                'idcustomer_order'=>$request->idcustomer_order?$request->idcustomer_order:'',
            ]);
            
            if($create_contact){
                $data = array('name'=>$request->name,'email'=>$request->email,'phone'=>$request->phone,'description'=>$request->description,'category'=>$request->category,'sub_category'=>$request->sub_category?$request->sub_category:'','idcustomer_order'=>$request->idcustomer_order?$request->idcustomer_order:'');
                // send email to user
                $send=Mail::send('email/mail', $data, function($message) use ($data) {
                    $message->to($data['email'], 'Contact Us - GGB')->subject('Thank you for Contact with GGB');
                    $message->from(env('MAIL_FROM_ADDRESS'),'GGB');
                });
                // send email to user
                $sendAdmin=Mail::send('email/admin_mail', $data, function($message) use ($data) {
                    $message->to(env('MAIL_ADMIN_ADDRESS'), 'Contact Us - GGB')->subject('New Contact Inquery - GGB');
                    $message->from(env('MAIL_FROM_ADDRESS'),'GGB');
                });
                if($send){
                   DB::commit();
                }else{
                    DB::rollback();
                    return response()->json([
                        'statusCode' => '1',
                        'message' => 'email not send!'
                    ]);
                }
            }
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $create_contact
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the list of created contact categories.
     */
    public function getContactCategories()
    {
        try {
            $contactCategories = Helper::getContactCategories();
            
            return response()->json([
                'statusCode' => '0',
                'message' => 'success',
                'data' => $contactCategories
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
        
    }
}
