<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideoConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        private string $path,
        private string $fileName
    ) {}

    public function handle(): void
    {
        Log::info("开始转换视频: {$this->fileName}");

        try {
            // 从 OSS 下载原始视频到临时文件
            $tempInput = tempnam(sys_get_temp_dir(), 'video_input_');
            $videoContent = Storage::disk('oss')->get($this->path);
            file_put_contents($tempInput, $videoContent);

            // 生成输出路径
            $webmPath = preg_replace('/\.\w+$/', '.webm', $this->path);
            $tempOutput = tempnam(sys_get_temp_dir(), 'video_output_') . '.webm';

            // 使用 FFmpeg 转换为 WebM (VP9)
            $command = sprintf(
                'ffmpeg -i %s -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -y %s 2>&1',
                escapeshellarg($tempInput),
                escapeshellarg($tempOutput)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('FFmpeg 转换失败: ' . implode("\n", $output));
            }

            // 上传转换后的文件到 OSS
            $webmContent = file_get_contents($tempOutput);
            Storage::disk('oss')->put($webmPath, $webmContent);

            // 删除原 MP4 文件
            Storage::disk('oss')->delete($this->path);

            // 更新数据库中的路径为 WebM 路径
            $this->updateDatabasePath($this->path, $webmPath);

            // 清理临时文件
            @unlink($tempInput);
            @unlink($tempOutput);

            Log::info("视频转换完成: {$this->fileName} -> {$webmPath}");

        } catch (\Exception $e) {
            Log::error("视频转换失败: {$this->fileName} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新数据库中的视频路径
     */
    private function updateDatabasePath(string $oldPath, string $newPath): void
    {
        // 更新课程表中的 content_url
        $courseUpdated = Course::where('content_url', $oldPath)
            ->update(['content_url' => $newPath]);

        // 更新附件表中的 file_path（如果有）
        $attachmentUpdated = Attachment::where('file_path', $oldPath)
            ->update(['file_path' => $newPath]);

        Log::info("数据库路径已更新", [
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'courses_updated' => $courseUpdated,
            'attachments_updated' => $attachmentUpdated,
        ]);
    }
}
