<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 每天凌晨 2 点清理 30 天前的通知
Schedule::call(function () {
    $deleted = DB::table('notifications')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
    if ($deleted > 0) {
        Log::info("已清理 {$deleted} 条过期通知");
    }
})->daily()->at('02:00');

// 每天凌晨 3 点限制每用户最多 50 条通知
Schedule::call(function () {
    $users = DB::table('users')->pluck('id');
    $totalDeleted = 0;
    
    foreach ($users as $userId) {
        $count = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->count();
        
        if ($count > 50) {
            $toDelete = DB::table('notifications')
                ->where('notifiable_id', $userId)
                ->orderBy('created_at', 'asc')
                ->limit($count - 50)
                ->pluck('id');
            
            DB::table('notifications')->whereIn('id', $toDelete)->delete();
            $totalDeleted += count($toDelete);
        }
    }
    
    if ($totalDeleted > 0) {
        Log::info("已清理 {$totalDeleted} 条超量通知");
    }
})->daily()->at('03:00');
