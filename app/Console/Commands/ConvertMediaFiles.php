<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConvertMediaFiles extends Command
{
    protected $signature = 'media:convert {--type=all : 图片(image)、视频(video)或全部(all)}';
    protected $description = '批量转换现有图片和视频文件（图片转 WebP，视频转 WebM）';

    private string $prefix;

    public function __construct()
    {
        parent::__construct();
        $this->prefix = env('OSS_PREFIX', 'academy/dev');
    }

    public function handle(): int
    {
        $type = $this->option('type');

        $this->info("开始批量转换媒体文件 (类型: {$type})...");
        $this->info("OSS 前缀: {$this->prefix}");

        // 获取所有文件
        $disk = Storage::disk('oss');
        $allFiles = $disk->allFiles($this->prefix);
        $this->info("找到 " . count($allFiles) . " 个文件");

        if ($type === 'all' || $type === 'image') {
            $this->convertImages($allFiles);
        }

        if ($type === 'all' || $type === 'video') {
            $this->convertVideos($allFiles);
        }

        $this->info("批量转换完成！");
        return Command::SUCCESS;
    }

    /**
     * 批量转换图片为 WebP
     */
    private function convertImages(array $allFiles): void
    {
        $this->info("开始转换图片为 WebP...");

        $disk = Storage::disk('oss');
        $imageExtensions = '/\.(jpg|jpeg|png|gif|bmp)$/i';

        // 筛选图片文件
        $images = array_filter($allFiles, function($file) use ($imageExtensions) {
            return preg_match($imageExtensions, $file);
        });

        $this->info("找到 " . count($images) . " 个图片文件");

        $converted = 0;
        $failed = 0;

        foreach ($images as $file) {
            try {
                // 跳过已经是 WebP 的文件
                if (str_ends_with(strtolower($file), '.webp')) {
                    continue;
                }

                $this->line("转换图片: {$file}");

                // 读取图片
                $imageContent = $disk->get($file);
                if (!$imageContent) {
                    $this->warn("  跳过：无法读取文件");
                    $failed++;
                    continue;
                }

                // 使用 Intervention Image 转换
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($imageContent);
                $webpData = $image->toWebp(85)->toString();

                // 生成 WebP 路径
                $webpPath = preg_replace('/\.\w+$/', '.webp', $file);

                // 上传到 OSS
                $disk->put($webpPath, $webpData);

                // 删除原文件
                $disk->delete($file);

                $this->info("  ✓ 转换成功: {$webpPath}");
                $converted++;

            } catch (\Exception $e) {
                $this->error("  ✗ 转换失败: {$e->getMessage()}");
                Log::error("图片转换失败: {$file} - " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("图片转换完成：成功 {$converted}，失败 {$failed}");
    }

    /**
     * 批量转换视频为 WebM
     */
    private function convertVideos(array $allFiles): void
    {
        $this->info("开始转换视频为 WebM...");

        $disk = Storage::disk('oss');
        $videoExtensions = '/\.(mp4|avi|mov|mkv)$/i';

        // 筛选视频文件
        $videos = array_filter($allFiles, function($file) use ($videoExtensions) {
            return preg_match($videoExtensions, $file);
        });

        $this->info("找到 " . count($videos) . " 个视频文件");

        $converted = 0;
        $failed = 0;

        foreach ($videos as $file) {
            try {
                // 跳过已经是 WebM 的文件
                if (str_ends_with(strtolower($file), '.webm')) {
                    continue;
                }

                $this->line("转换视频: {$file}");

                // 下载原始视频到临时文件
                $tempInput = tempnam(sys_get_temp_dir(), 'video_input_');
                $videoContent = $disk->get($file);
                file_put_contents($tempInput, $videoContent);

                // 生成输出路径
                $webmPath = preg_replace('/\.\w+$/', '.webm', $file);
                $tempOutput = tempnam(sys_get_temp_dir(), 'video_output_') . '.webm';

                // 使用 FFmpeg 转换
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
                $disk->put($webmPath, $webmContent);

                // 删除原文件
                $disk->delete($file);

                // 清理临时文件
                @unlink($tempInput);
                @unlink($tempOutput);

                $this->info("  ✓ 转换成功: {$webmPath}");
                $converted++;

            } catch (\Exception $e) {
                $this->error("  ✗ 转换失败: {$e->getMessage()}");
                Log::error("视频转换失败: {$file} - " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("视频转换完成：成功 {$converted}，失败 {$failed}");
    }
}
