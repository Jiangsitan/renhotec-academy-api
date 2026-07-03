<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentCompressService
{
    protected string $pythonBin;
    protected string $scriptPath;

    public function __construct()
    {
        $this->pythonBin = 'python3';
        $this->scriptPath = base_path('scripts/compress_document.py');
    }

    /**
     * 检查文件是否需要压缩处理
     */
    public static function needsCompression(string $fileName): bool
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($ext, ['ppt', 'pptx', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /**
     * 压缩文档文件
     *
     * @param string $ossPath OSS 上的文件路径
     * @param int $quality 图片质量 (1-100)
     * @return array|null 压缩后的文件信息 ['path' => string, 'size' => int]，失败返回 null
     */
    public function compress(string $ossPath, int $quality = 85): ?array
    {
        $disk = Storage::disk('oss');

        if (!$disk->exists($ossPath)) {
            Log::error('文件不存在', ['path' => $ossPath]);
            return null;
        }

        // 使用 /tmp 目录（宿主机和容器共享）
        $tempDir = '/tmp/doc_compress';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // 从 OSS 下载文件到 /tmp
        $localInput = $tempDir . '/' . basename($ossPath);
        file_put_contents($localInput, $disk->get($ossPath));

        // 生成输出路径（确保输入和输出路径不同）
        $ext = strtolower(pathinfo($ossPath, PATHINFO_EXTENSION));
        $outputFilename = pathinfo($ossPath, PATHINFO_FILENAME) . ($ext === 'pdf' ? '_compressed' : '') . '.pdf';
        $localOutput = $tempDir . '/' . $outputFilename;

        // 调用 Python 脚本
        $cmd = sprintf(
            '%s "%s" "%s" "%s" --quality %d 2>&1',
            $this->pythonBin,
            $this->scriptPath,
            $localInput,
            $localOutput,
            $quality
        );

        Log::info('开始压缩文档', ['input' => $ossPath, 'cmd' => $cmd]);

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('文档压缩失败', [
                'input' => $ossPath,
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
            ]);
            $this->cleanup($localInput);
            return null;
        }

        // 检查输出文件是否存在
        if (!file_exists($localOutput)) {
            Log::error('压缩后文件未生成', ['expected' => $localOutput]);
            $this->cleanup($localInput);
            return null;
        }

        // 生成 OSS 路径
        if ($ext === 'pdf') {
            // PDF 文件：保留原文件名，直接覆盖
            $ossOutputPath = $ossPath;
        } else {
            // PPT 文件：转换为 PDF
            $ossOutputPath = str_replace(
                ['.' . $ext, '.PPT', '.PPTX', '.PDF'],
                '.pdf',
                $ossPath
            );
        }

        // 上传压缩后的文件到 OSS
        $compressedContent = file_get_contents($localOutput);
        $disk->put($ossOutputPath, $compressedContent);

        // 获取原始文件大小（在删除之前）
        $originalSize = filesize($localInput) ?? 0;

        // 删除原文件（OSS）
        if ($ext !== 'pdf') {
            $disk->delete($ossPath);
        }

        // 清理本地临时文件和目录
        $this->cleanup($localInput, $localOutput);
        $this->cleanupDir($tempDir);

        Log::info('文档压缩完成', [
            'input' => $ossPath,
            'output' => $ossOutputPath,
            'original_size' => $originalSize,
            'compressed_size' => strlen($compressedContent),
        ]);

        return [
            'path' => $ossOutputPath,
            'size' => strlen($compressedContent),
        ];
    }

    /**
     * 清理临时文件
     */
    protected function cleanup(string ...$files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * 清理临时目录
     */
    protected function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        @rmdir($dir);
    }
}
