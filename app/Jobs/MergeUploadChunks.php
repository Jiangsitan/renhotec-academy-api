<?php

namespace App\Jobs;

use App\Helpers\OssHelper;
use App\Services\FileConvertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MergeUploadChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 分钟

    public function __construct(
        private string $uploadId,
        private string $finalPath,
        private array $meta
    ) {}

    public function handle(): void
    {
        Log::info("开始合并分片: {$this->uploadId}");

        try {
            $chunkDir = "temp/uploads/{$this->uploadId}";
            $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
            $handle = fopen($tempFile, 'w');

            // 合并分片
            for ($i = 0; $i < $this->meta['total_chunks']; $i++) {
                $chunkPath = "{$chunkDir}/chunk_{$i}";
                if (Storage::disk('local')->exists($chunkPath)) {
                    $chunkStream = Storage::disk('local')->readStream($chunkPath);
                    stream_copy_to_stream($chunkStream, $handle);
                    fclose($chunkStream);
                }
            }
            fclose($handle);

            // 上传到 OSS
            $fileContent = file_get_contents($tempFile);
            Storage::disk('oss')->put($this->finalPath, $fileContent);
            unlink($tempFile);

            // 清理临时分片
            Storage::disk('local')->deleteDirectory($chunkDir);

            // 如果是 PPT/PPTX，自动转换为 PDF 并删除原文件
            if (FileConvertService::needsConversion($this->meta['file_name'])) {
                $pdfPath = FileConvertService::convertToPdf($this->finalPath);
                if ($pdfPath) {
                    // 删除原 PPT 文件
                    Storage::disk('oss')->delete($this->finalPath);
                    // 更新路径为 PDF 路径
                    $this->finalPath = $pdfPath;
                }
            }

            // 如果是视频，触发异步转换为 WebM
            $extension = strtolower(pathinfo($this->meta['file_name'], PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'avi', 'mov', 'mkv'];
            if (in_array($extension, $videoExtensions)) {
                \App\Jobs\ProcessVideoConversion::dispatch($this->finalPath, $this->meta['file_name']);
            }

            Log::info("分片合并完成: {$this->uploadId} -> {$this->finalPath}");

        } catch (\Exception $e) {
            Log::error("分片合并失败: {$this->uploadId} - " . $e->getMessage());
            throw $e;
        }
    }
}
