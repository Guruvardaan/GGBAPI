<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function checkConnection()
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['message' => 'Database connection established successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not connect to the database', 'details' => $e->getMessage()], 500);
        }
    }
}
