<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $types = VehicleType::orderBy('category')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $types
        ]);
    }
}
