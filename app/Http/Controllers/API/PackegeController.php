<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PackageProductList;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

class PackegeController extends Controller
{
    public function store(Request $request)
    {
        $req = json_decode($request->getContent());
        try {
            $user = auth()->guard('api')->user();
            $userAccess = DB::table('staff_access')
                ->join('store_warehouse', 'staff_access.idstore_warehouse', '=', 'store_warehouse.idstore_warehouse')
                ->select(
                    'staff_access.idstore_warehouse',
                    'staff_access.idstaff_access',
                    'store_warehouse.is_store',
                    'staff_access.idstaff'
                )
                ->where('staff_access.idstaff', $user->id)
                ->first();
            $stores = DB::table('store_warehouse')->select('idstore_warehouse')->where('is_store',1)->get();
            $pkgs = [];
            foreach($stores as $store) {
                $id = Package::create(array(
                    'idpackage_master' => $req->idpackage_master,
                    'idstore_warehouse' => $store->idstore_warehouse,
                    'applicable_on' => $req->applicable_on,
                    'frequency' => $req->frequency,
                    'name' => $req->name,
                    'base_trigger_amount' => $req->base_trigger_amount,
                    'additional_tag_amount' => $req->additional_tag_amount,
                    'bypass_make_gen' => $req->bypass_make_gen,
                    'valid_from' => date('Y-m-d', strtotime($req->valid_from)),
                    'valid_till' => date('Y-m-d', strtotime($req->valid_till)),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'status' => 1
                ));
                $pkgs[] = $id->idpackage;
            }
            foreach($pkgs as $pkg) {
                $triggeringProds = [];
                $taggingProds = [];
    
                if (count($req->triggeringProds) > 0) {
                    foreach ($req->triggeringProds as $tprod) {
                        $triggeringProds[] = [
                            'idpackage' => $pkg,
                            'idproduct_master' => $tprod->idproduct_master,
                            'quantity' => $tprod->quantity,
                            'is_triggerer_tag_along' => 1,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                            'status' => 1
                        ];
                    }
                    PackageProductList::insert($triggeringProds);
                }
                if (count($req->tagAlongProds) > 0) {
                    foreach ($req->tagAlongProds as $tprod) {
                        $taggingProds[] = [
                            'idpackage' => $pkg,
                            'idproduct_master' => $tprod->idproduct_master,
                            'quantity' => $tprod->quantity,
                            'is_triggerer_tag_along' => 0,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                            'status' => 1
                        ];
                    }
                    PackageProductList::insert($taggingProds);
                }
            }
            return response()->json(["statusCode" => 0, "message" => "Success"], 200);
        } catch (Exception $e) {
            return response()->json(["statusCode" => 1, "message" => "Error", "err" => $e->getMessage()], 200);
        }
    }
}
