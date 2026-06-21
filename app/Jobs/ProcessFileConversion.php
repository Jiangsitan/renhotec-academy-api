<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Attachment;
use App\Services\FileConvertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFileConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 分钟

    public function __construct(
        private string $path,
        private string $fileName
    ) {}

    public function handle(): void
    {
        Log::info("开始转换文件: {$this->fileName} ({$this->path})");

        try {
            // PPT → WebP 图片序列
            $webpImages = FileConvertService::pptToWebpImages($this->path);

            if ($webpImages) {
                // 删除原 PPT 文件
                Storage::disk('oss')->delete($this->path);

                // 更新数据库
                $this->updateDatabase($this->path, $webpImages[0], 'images', $webpImages);

                Log::info("文件转换完成（WebP）: {$this->fileName}", [
                    'images_count' => count($webpImages),
                ]);
                return;
            }

            // WebP 转换失败，回退到 PDF
            $pdfPath = FileConvertService::convertToPdf($this->path);

            if ($pdfPath) {
                // 删除原 PPT 文件
                Storage::disk('oss')->delete($this->path);

                // 更新数据库
                $this->updateDatabase($this->path, $pdfPath, 'pdf', null);

                Log::info("文件转换完成（PDF）: {$this->fileName}");
                return;
            }

            // 转换全部失败，保留原文件，更新 content_type 为 pdf（兜底）
            Log::warning("文件转换失败，保留原文件: {$this->fileName}");

        } catch (\Exception $e) {
            Log::error("文件转换异常: {$this->fileName} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新数据库中的文件信息
     */
    private function updateDatabase(string $oldPath, string $newPath, string $contentType, ?array $images): void
    {
        // 更新课程表
        $courseUpdated = Course::where('content_url', $oldPath)->update([
            'content_url' => $newPath,
            'content_type' => $contentType,
            'images' => $images ? json_encode($images) : null,
        ]);

        // 更新附件表
        $attachmentUpdated = Attachment::where('file_path', $oldPath)->update([
            'file_path' => $newPath,
        ]);

        Log::info("数据库已更新", [
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'content_type' => $contentType,
            'courses_updated' => $courseUpdated,
            'attachments_updated' => $attachmentUpdated,
        ]);
    }
}
