<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::whereIn('key', [
            'system_name',
            'system_subtitle',
            'system_logo',
        ])->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }
}
