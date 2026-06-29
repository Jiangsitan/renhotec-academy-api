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
            'exam_anti_cheat_enabled',
        ])->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }
}
