<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\StoreRequest;
use App\Models\StoreRequestDetails;
use App\Models\Inventory;
use App\Models\product_batch;
use App\Models\VendorPurchasesDetail;
use Illuminate\Support\Facades\Validator;

class LogsController extends Controller
{
    public function PurchaseDetailsLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_purchases' => 'required|array',
            'vendor_purchases_details' => 'required|array',
            'user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $vendorPurchases = $request->vendor_purchases[0] ?? null;
            $vendorPurchasesDetails = $request->vendor_purchases_details ?? null;
            $vendorID = $request->idvendor ?? null;
            $user_id = $request->user_id ?? null;
            if ($vendorPurchasesDetails) {
                    foreach ($vendorPurchasesDetails as $VendorPurDet) {
                        $checkVendorDetails = VendorPurchasesDetail::where('idvendor_purchases', $VendorPurDet['idvendor_purchases'])
                                            ->where('idproduct_master', $VendorPurDet['idproduct_master'])
                                            ->first();
                        if($checkVendorDetails != null){
                            if ($VendorPurDet['mrp'] != $checkVendorDetails->mrp || $VendorPurDet['selling_price'] != $checkVendorDetails->selling_price || $VendorPurDet['unit_purchase_price'] != $checkVendorDetails->unit_purchase_price) {
                                DB::table('vendor_purchases_detail_logs')->insert([
                                    'idvendor' => $vendorID,
                                    'idvendor_purchases' => $VendorPurDet['idvendor_purchases'],
                                    'idproduct_master' => $VendorPurDet['idproduct_master'],
                                    'update_mrp' => $VendorPurDet['mrp'],
                                    'update_product' => $VendorPurDet['product'],
                                    'update_copartner' => $VendorPurDet['copartner'],
                                    'update_land' => $VendorPurDet['land'],
                                    'update_selling_price' => $VendorPurDet['selling_price'],
                                    'update-hsn' => $VendorPurDet['hsn'],
                                    'update_unit_purchase_price' => $VendorPurDet['unit_purchase_price'],
                                    'update-expiry' => $VendorPurDet['expiry'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                    'created_by' => $user_id,
                                    'updated_by' => $user_id,
                                ]);
                            }
                        }
                        
                    }
                DB::commit();
                return response()->json(['message' => 'Data successfully inserted'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Could not connect to the database', 'details' => $e->getMessage()], 500);
        }
    }
}