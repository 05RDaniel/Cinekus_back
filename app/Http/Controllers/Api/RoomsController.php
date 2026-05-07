<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sala;

class RoomsController extends Controller
{
    public function index()
    {
        return response()->json(Sala::query()->orderBy('id')->get());
    }
}
