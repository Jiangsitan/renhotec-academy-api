<?php

namespace App\Console\Commands;

use App\Helpers\OssHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToOss extends Command
{
    protected $signature = 'files:migrate-to-oss';
    protected $description = '将本地文件上传到阿里云 OSS';

    public function handle()
    {
        $prefix = env('OSS_PREFIX', 'academy/dev');
        $localDisk = Storage::disk('public');
        
        // 获取 OSS 客户端
        $ossClient = OssHelper::getClient();
        $bucket = OssHelper::getBucket();

        $this->info("开始迁移文件到 OSS...");
        $this->info("OSS 前缀: {$prefix}");
        $this->info("Bucket: {$bucket}");

        // 1. 上传课程文件
        $courses = DB::table('courses')->whereNotNull('content_url')->get();
        $this->info("上传课程文件: " . $courses->count() . " 个");
        
        $courseCount = 0;
        foreach ($courses as $course) {
            // 从 OSS 路径还原原始本地路径
            $ossPath = $course->content_url;
            $oldPath = $this->getOriginalLocalPath($ossPath, $course->type);
            
            if ($localDisk->exists($oldPath)) {
                $localFilePath = $localDisk->path($oldPath);
                
                try {
                    $ossClient->uploadFile($bucket, $ossPath, $localFilePath);
                    $this->line("  ✓ {$course->title} -> {$ossPath}");
                    $courseCount++;
                } catch (\Exception $e) {
                    $this->error("  ✗ {$course->title} - 上传失败: " . $e->getMessage());
                }
            } else {
                $this->warn("  ✗ {$course->title} - 文件不存在: {$oldPath}");
            }
        }
        $this->info("课程文件上传完成: {$courseCount} 个");

        // 2. 上传附件文件
        $attachments = DB::table('attachments')->whereNotNull('file_path')->get();
        $this->info("上传附件文件: " . $attachments->count() . " 个");
        
        $attachmentCount = 0;
        foreach ($attachments as $attachment) {
            // 从 OSS 路径还原原始本地路径
            $ossPath = $attachment->file_path;
            $oldPath = str_replace($prefix . '/attachments/', '', $ossPath);
            
            if ($localDisk->exists($oldPath)) {
                $localFilePath = $localDisk->path($oldPath);
                
                try {
                    $ossClient->uploadFile($bucket, $ossPath, $localFilePath);
                    $this->line("  ✓ {$attachment->file_name} -> {$ossPath}");
                    $attachmentCount++;
                } catch (\Exception $e) {
                    $this->error("  ✗ {$attachment->file_name} - 上传失败: " . $e->getMessage());
                }
            } else {
                $this->warn("  ✗ {$attachment->file_name} - 文件不存在: {$oldPath}");
            }
        }
        $this->info("附件文件上传完成: {$attachmentCount} 个");

        // 3. 上传系统 Logo
        $settings = DB::table('settings')->where('key', 'system_logo')->get();
        $this->info("上传系统 Logo: " . $settings->count() . " 个");
        
        foreach ($settings as $setting) {
            // 从 OSS 路径还原原始本地路径
            $ossPath = $setting->value;
            $oldPath = str_replace($prefix . '/logos/', 'courses/' . date('Y/m') . '/', $ossPath);
            
            if ($localDisk->exists($oldPath)) {
                $localFilePath = $localDisk->path($oldPath);
                
                try {
                    $ossClient->uploadFile($bucket, $ossPath, $localFilePath);
                    $this->line("  ✓ Logo -> {$ossPath}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Logo - 上传失败: " . $e->getMessage());
                }
            } else {
                $this->warn("  ✗ Logo - 文件不存在: {$oldPath}");
            }
        }

        $this->info("文件迁移完成！");
        $this->info("请验证 OSS 中的文件是否正确。");

        return 0;
    }

    private function getOriginalLocalPath(string $ossPath, string $type): string
    {
        // 从 OSS 路径还原原始本地路径
        // 例如: academy/dev/videos/courses/2026/06/xxx.mp4 -> courses/2026/06/xxx.mp4
        $prefix = env('OSS_PREFIX', 'academy/dev');
        $typeDir = match($type) {
            'video' => 'videos',
            'document' => 'documents',
            default => 'others'
        };
        
        // 移除前缀和类型目录
        $path = str_replace($prefix . '/' . $typeDir . '/', '', $ossPath);
        return $path;
    }
}
