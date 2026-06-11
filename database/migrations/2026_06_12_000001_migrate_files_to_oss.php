<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('OSS_PREFIX', 'academy/dev');
        
        // 1. 迁移课程文件路径
        $courses = DB::table('courses')->whereNotNull('content_url')->get();
        foreach ($courses as $course) {
            if (str_starts_with($course->content_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $course->content_url);
                $newPath = $this->getNewPath($oldPath, $course->type);
                DB::table('courses')->where('id', $course->id)->update([
                    'content_url' => $newPath
                ]);
            }
        }

        // 2. 迁移附件文件路径
        $attachments = DB::table('attachments')->whereNotNull('file_path')->get();
        foreach ($attachments as $attachment) {
            if (str_starts_with($attachment->file_path, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $attachment->file_path);
                $newPath = $prefix . '/attachments/' . basename($oldPath);
                DB::table('attachments')->where('id', $attachment->id)->update([
                    'file_path' => $newPath
                ]);
            }
        }

        // 3. 迁移系统设置中的 Logo
        $settings = DB::table('settings')->where('key', 'system_logo')->get();
        foreach ($settings as $setting) {
            if (str_starts_with($setting->value, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $setting->value);
                $newPath = $prefix . '/logos/' . basename($oldPath);
                DB::table('settings')->where('id', $setting->id)->update([
                    'value' => $newPath
                ]);
            }
        }
    }

    private function getNewPath(string $oldPath, string $type): string
    {
        $prefix = env('OSS_PREFIX', 'academy/dev');
        $typeDir = match($type) {
            'video' => 'videos',
            'document' => 'documents',
            default => 'others'
        };
        return $prefix . '/' . $typeDir . '/' . $oldPath;
    }

    public function down(): void
    {
        // 回滚：将 OSS 路径还原为本地路径
        $prefix = env('OSS_PREFIX', 'academy/dev');
        
        $courses = DB::table('courses')->whereNotNull('content_url')->get();
        foreach ($courses as $course) {
            if (str_starts_with($course->content_url, $prefix)) {
                $oldPath = str_replace($prefix . '/', '', $course->content_url);
                DB::table('courses')->where('id', $course->id)->update([
                    'content_url' => '/storage/' . $oldPath
                ]);
            }
        }
    }
};
