<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Attachment;
use App\Services\DocumentCompressService;
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
        Log::info("开始处理文件: {$this->fileName} ({$this->path})");

        try {
            $compressService = app(DocumentCompressService::class);
            $result = $compressService->compress($this->path);

            if ($result) {
                // 更新数据库（包含文件大小）
                $this->updateDatabase($this->path, $result['path'], 'pdf', null, $result['size']);

                Log::info("文件处理完成: {$this->fileName} -> {$result['path']} ({$result['size']} bytes)");
            } else {
                // 压缩失败，保留原文件，更新 content_type 为 pdf（兜底）
                Log::warning("文件压缩失败，保留原文件: {$this->fileName}");

                // 更新数据库，将 content_type 设为 pdf
                Course::where('content_url', $this->path)->update([
                    'content_type' => 'pdf',
                ]);
            }

        } catch (\Exception $e) {
            Log::error("文件处理异常: {$this->fileName} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新数据库中的文件信息
     */
    private function updateDatabase(string $oldPath, string $newPath, string $contentType, ?array $images, ?int $fileSize = null): void
    {
        // 更新课程表
        $courseUpdateData = [
            'content_url' => $newPath,
            'content_type' => $contentType,
            'images' => $images ? json_encode($images) : null,
        ];
        
        if ($fileSize !== null) {
            $courseUpdateData['file_size'] = $fileSize;
        }
        
        $courseUpdated = Course::where('content_url', $oldPath)->update($courseUpdateData);

        // 更新附件表
        $attachmentUpdateData = [
            'file_path' => $newPath,
        ];
        
        if ($fileSize !== null) {
            $attachmentUpdateData['file_size'] = $fileSize;
        }
        
        $attachmentUpdated = Attachment::where('file_path', $oldPath)->update($attachmentUpdateData);

        Log::info("数据库已更新", [
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'content_type' => $contentType,
            'file_size' => $fileSize,
            'courses_updated' => $courseUpdated,
            'attachments_updated' => $attachmentUpdated,
        ]);
    }
}
