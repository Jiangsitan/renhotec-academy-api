<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileConvertService
{
    protected static array $convertibleExtensions = ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * 检查 LibreOffice 是否可用
     */
    public static function isLibreOfficeAvailable(): bool
    {
        $output = [];
        exec('which soffice 2>/dev/null || which libreoffice 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * 检查文件是否需要转换为 PDF
     */
    public static function needsConversion(string $fileName): bool
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($ext, self::$convertibleExtensions);
    }

    /**
     * 将 Office 文件转换为 PDF
     *
     * @param string $sourcePath 相对于 public 磁盘的文件路径，如 courses/2026/06/xxx.pptx
     * @return string|null 转换后的 PDF 相对路径，失败返回 null
     */
    public static function convertToPdf(string $sourcePath): ?string
    {
        if (!self::isLibreOfficeAvailable()) {
            Log::warning('LibreOffice not available, skipping conversion', ['file' => $sourcePath]);
            return null;
        }

        $publicDisk = Storage::disk('public');
        $fullSourcePath = $publicDisk->path($sourcePath);

        if (!$publicDisk->exists($sourcePath)) {
            Log::error('Source file not found for conversion', ['file' => $sourcePath]);
            return null;
        }

        // 输出目录与源文件相同
        $outputDir = dirname($fullSourcePath);
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $pdfFileName = $baseName . '_preview.pdf';
        $pdfRelativePath = dirname($sourcePath) . '/' . $pdfFileName;

        // 如果已存在转换后的 PDF，直接返回
        if ($publicDisk->exists($pdfRelativePath)) {
            return $pdfRelativePath;
        }

        // 执行转换（使用默认字体配置）
        $escapedSource = escapeshellarg($fullSourcePath);
        $escapedOutputDir = escapeshellarg($outputDir);

        // 创建临时 LibreOffice 配置目录，强制使用默认字体
        $profileDir = storage_path('app/temp/libreoffice_profile');
        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }

        // 设置字体替换配置（强制使用系统默认字体）
        $registryFile = $profileDir . '/user/registrymodifications.xcu';
        if (!file_exists($registryFile)) {
            $registryDir = $profileDir . '/user';
            if (!is_dir($registryDir)) {
                mkdir($registryDir, 0755, true);
            }
            file_put_contents($registryFile, self::getRegistryConfig());
        }

        // macOS 使用 soffice，Linux 使用 libreoffice
        $homeDir = storage_path('app/temp');
        // 对包含空格的路径进行 URL 编码
        $encodedProfileDir = str_replace(' ', '%20', $profileDir);
        $command = "HOME=" . escapeshellarg($homeDir) . " SAL_USE_VCLPLUGIN=svp soffice '-env:UserInstallationURL=file://{$encodedProfileDir}' --headless --convert-to pdf --outdir {$escapedOutputDir} {$escapedSource} 2>&1";
        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('LibreOffice conversion failed', [
                'file' => $sourcePath,
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
            ]);
            return null;
        }

        // LibreOffice 转换后的文件名是原文件名.pdf（不是 _preview.pdf）
        // 需要重命名为 _preview.pdf
        $defaultPdfPath = dirname($fullSourcePath) . '/' . $baseName . '.pdf';
        $targetPdfPath = dirname($fullSourcePath) . '/' . $pdfFileName;

        if (file_exists($defaultPdfPath) && $defaultPdfPath !== $targetPdfPath) {
            rename($defaultPdfPath, $targetPdfPath);
        }

        if (!file_exists($targetPdfPath)) {
            Log::error('Converted PDF not found after conversion', [
                'file' => $sourcePath,
                'expected' => $targetPdfPath,
            ]);
            return null;
        }

        Log::info('File converted successfully', [
            'source' => $sourcePath,
            'pdf' => $pdfRelativePath,
        ]);

        return $pdfRelativePath;
    }

    /**
     * 获取文件的预览路径（如果存在转换后的 PDF）
     *
     * @param string $filePath 相对于 public 磁盘的文件路径
     * @return string 预览用的相对路径（可能是转换后的 PDF 或原文件）
     */
    public static function getPreviewPath(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // PPT/PPTX 返回转换后的 PDF 路径
        if (in_array($ext, ['ppt', 'pptx'])) {
            $baseName = pathinfo($filePath, PATHINFO_FILENAME);
            $pdfPath = dirname($filePath) . '/' . $baseName . '_preview.pdf';

            if (Storage::disk('public')->exists($pdfPath)) {
                return $pdfPath;
            }

            // 如果 PDF 不存在，尝试即时转换
            $converted = self::convertToPdf($filePath);
            if ($converted) {
                return $converted;
            }

            return $filePath; // 转换失败，返回原文件
        }

        return $filePath;
    }

    /**
     * 获取 LibreOffice 注册表配置（强制使用默认字体）
     */
    protected static function getRegistryConfig(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<oor:items xmlns:oor="urn:openoffice:names:experimental:ooo:1.0:office"
           xmlns:xs="http://www.w3.org/2001/XMLSchema"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <oor:item oor:path="/org.openoffice.Office.Common/Font/Substitution">
    <oor:prop oor:name="Replacement" oor:op="fuse">
      <value>true</value>
    </oor:prop>
  </oor:item>
  <oor:item oor:path="/org.openoffice.Office.Common/Font/Font">
    <oor:prop oor:name="FontList" oor:op="fuse">
      <value>
        <prop oor:name="Arial" oor:op="fuse"><value>Arial</value></prop>
        <prop oor:name="Times New Roman" oor:op="fuse"><value>Times New Roman</value></prop>
        <prop oor:name="Courier New" oor:op="fuse"><value>Courier New</value></prop>
      </value>
    </oor:prop>
  </oor:item>
</oor:items>';
    }
}
