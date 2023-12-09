<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class APIController extends Controller
{
    public function getSampleData()
    {
        // Simulating sample data for the API response
        $data = [
            'message' => 'This is a sample GET API response',
            'timestamp' => now()->toDateTimeString(),
        ];

        return response()->json($data);
    }
}
