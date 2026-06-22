<?php

namespace App\Jobs;

use App\Helpers\OssHelper;
use App\Services\DocumentCompressService;
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

            // 如果是 PPT/PPTX/PDF，触发异步压缩（避免同步阻塞导致超时）
            if (DocumentCompressService::needsCompression($this->meta['file_name'])) {
                ProcessFileConversion::dispatch($this->finalPath, $this->meta['file_name']);
            }

            // 如果是视频，触发异步转换为 WebM
            $extension = strtolower(pathinfo($this->meta['file_name'], PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'avi', 'mov', 'mkv'];
            if (in_array($extension, $videoExtensions)) {
                ProcessVideoConversion::dispatch($this->finalPath, $this->meta['file_name']);
            }

            Log::info("分片合并完成: {$this->uploadId} -> {$this->finalPath}");

        } catch (\Exception $e) {
            Log::error("分片合并失败: {$this->uploadId} - " . $e->getMessage());
            throw $e;
        }
    }
}
