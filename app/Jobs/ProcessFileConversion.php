<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Attachment;
use App\Services\DocumentCompressService;
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

    // 需要 LibreOffice 转 PDF 的扩展名（DOC/DOCX/XLS/XLSX）
    private static array $officeConvertExtensions = ['doc', 'docx', 'xls', 'xlsx'];

    public function __construct(
        private string $path,
        private string $fileName
    ) {}

    public function handle(): void
    {
        Log::info("开始处理文件: {$this->fileName} ({$this->path})");

        try {
            $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));

            if (in_array($ext, self::$officeConvertExtensions)) {
                // DOC/DOCX/XLS/XLSX → 使用 LibreOffice 转 PDF
                $this->handleOfficeConversion($ext);
            } else {
                // PPT/PPTX/PDF → 使用 DocumentCompressService（图片压缩）
                $this->handleCompressConversion();
            }

        } catch (\Exception $e) {
            Log::error("文件处理异常: {$this->fileName} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 处理 Office 文件转换（DOC/DOCX/XLS/XLSX → PDF）
     */
    private function handleOfficeConversion(string $ext): void
    {
        $convertedPath = FileConvertService::convertToPdf($this->path);

        if ($convertedPath && Storage::disk('oss')->exists($convertedPath)) {
            // 获取文件大小
            $fileSize = Storage::disk('oss')->size($convertedPath);
            $this->updateDatabase($this->path, $convertedPath, 'pdf', null, $fileSize);

            Log::info("Office 文件转换完成: {$this->fileName} -> {$convertedPath} ({$fileSize} bytes)");
        } else {
            // 转换失败，保留原文件，记录日志
            Log::warning("Office 文件转换失败，保留原文件: {$this->fileName}");

            // 更新 content_type 为原始扩展名，让前端知道文件类型
            Course::where('content_url', $this->path)->update([
                'content_type' => $ext,
            ]);
        }
    }

    /**
     * 处理压缩转换（PPT/PPTX/PDF → 图片序列或压缩 PDF）
     */
    private function handleCompressConversion(): void
    {
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
