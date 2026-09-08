<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeatType;

class SeatTypesController extends Controller
{
    public function index()
    {
        return response()->json(
            SeatType::query()->orderBy('id')->get(['id', 'name'])
        );
    }
}
