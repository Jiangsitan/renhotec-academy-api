<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanFiles extends Command
{
    protected $signature = 'files:cleanup-orphans {--dry-run : 只显示要删除的文件，不实际删除}';
    protected $description = '清理 OSS 中孤立的文件';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('开始扫描孤立文件...');
        $this->newLine();
        
        // 1. 获取数据库中所有引用的文件
        $referencedFiles = $this->getReferencedFiles();
        $this->info("数据库引用文件数: " . count($referencedFiles));
        
        // 2. 获取 OSS 中所有文件
        $ossFiles = $this->getOssFiles();
        $this->info("OSS 文件总数: " . count($ossFiles));
        
        // 3. 找出孤立文件
        $orphanFiles = array_diff($ossFiles, $referencedFiles);
        $this->info("孤立文件数: " . count($orphanFiles));
        $this->newLine();
        
        if (empty($orphanFiles)) {
            $this->info("✅ 没有孤立文件需要清理");
            return 0;
        }
        
        // 4. 显示孤立文件列表
        $this->warn("孤立文件列表:");
        foreach ($orphanFiles as $file) {
            $this->line("  - {$file}");
        }
        $this->newLine();
        
        // 5. 如果不是 dry-run，删除文件
        if ($dryRun) {
            $this->info("ℹ️  这是预览模式，未实际删除文件。使用 --no-dry-run 参数执行删除。");
        } else {
            $confirmed = $this->confirm("确定要删除这些文件吗？", false);
            if ($confirmed) {
                $bar = $this->output->createProgressBar(count($orphanFiles));
                $bar->start();
                
                foreach ($orphanFiles as $file) {
                    Storage::disk('oss')->delete($file);
                    $bar->advance();
                }
                
                $bar->finish();
                $this->newLine();
                $this->info("✅ 已删除 " . count($orphanFiles) . " 个孤立文件");
            } else {
                $this->info("❌ 已取消删除操作");
            }
        }
        
        return 0;
    }

    /**
     * 获取数据库中所有引用的文件
     */
    protected function getReferencedFiles(): array
    {
        $files = [];
        
        // 课程文件
        $courses = Course::select('content_url', 'images')->get();
        foreach ($courses as $course) {
            if ($course->content_url) {
                $files[] = $course->content_url;
            }
            if ($course->images && is_array($course->images)) {
                $files = array_merge($files, $course->images);
            }
        }
        
        // 附件文件
        $attachments = Attachment::select('file_path')->get();
        foreach ($attachments as $attachment) {
            if ($attachment->file_path) {
                $files[] = $attachment->file_path;
            }
        }
        
        return array_unique(array_filter($files));
    }

    /**
     * 获取 OSS 中所有文件
     */
    protected function getOssFiles(): array
    {
        $prefix = env('OSS_PREFIX', 'academy');
        $files = [];
        
        try {
            // 获取所有文件
            $allFiles = Storage::disk('oss')->allFiles($prefix);
            $files = $allFiles;
        } catch (\Exception $e) {
            $this->error("获取 OSS 文件列表失败: " . $e->getMessage());
        }
        
        return $files;
    }
}
