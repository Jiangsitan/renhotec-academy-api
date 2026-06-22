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
        return in_array($ext, ['ppt', 'pptx', 'pdf']);
    }

    /**
     * 压缩文档文件
     *
     * @param string $ossPath OSS 上的文件路径
     * @param int $quality 图片质量 (1-100)
     * @return string|null 压缩后的 PDF 路径，失败返回 null
     */
    public function compress(string $ossPath, int $quality = 85): ?string
    {
        $disk = Storage::disk('oss');

        if (!$disk->exists($ossPath)) {
            Log::error('文件不存在', ['path' => $ossPath]);
            return null;
        }

        // 创建临时目录
        $tempDir = storage_path('app/temp/doc_compress');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // 从 OSS 下载文件到本地
        $localInput = $tempDir . '/' . basename($ossPath);
        file_put_contents($localInput, $disk->get($ossPath));

        // 生成输出路径
        $ext = strtolower(pathinfo($ossPath, PATHINFO_EXTENSION));
        $outputFilename = pathinfo($ossPath, PATHINFO_FILENAME) . '.pdf';
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
        $ossOutputPath = str_replace(
            ['.' . $ext, '.PPT', '.PPTX', '.PDF'],
            '.pdf',
            $ossPath
        );
        if ($ossOutputPath === $ossPath) {
            // 如果路径没有变化，添加 _compressed 后缀
            $ossOutputPath = str_replace('.pdf', '_compressed.pdf', $ossPath);
        }

        // 上传压缩后的文件到 OSS
        $compressedContent = file_get_contents($localOutput);
        $disk->put($ossOutputPath, $compressedContent);

        // 删除原文件
        if ($ext !== 'pdf') {
            $disk->delete($ossPath);
        }

        // 清理本地文件
        $this->cleanup($localInput, $localOutput);

        Log::info('文档压缩完成', [
            'input' => $ossPath,
            'output' => $ossOutputPath,
            'original_size' => filesize($localInput) ?? 0,
            'compressed_size' => strlen($compressedContent),
        ]);

        return $ossOutputPath;
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
}
