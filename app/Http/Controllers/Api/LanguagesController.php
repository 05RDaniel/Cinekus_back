<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguagesController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        return response()->json($languages);
    }
}
