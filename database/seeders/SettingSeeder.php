<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $settings = [
            ['key' => 'system_name', 'value' => 'Renhotec Academy', 'group' => 'general'],
            ['key' => 'system_subtitle', 'value' => '员工培训与考试系统', 'group' => 'general'],
            ['key' => 'system_logo', 'value' => '', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
