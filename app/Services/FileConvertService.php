<?php

namespace App\Services;

use App\Helpers\OssHelper;
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
     * 将 Office 文件转换为 PDF（支持 OSS）
     *
     * @param string $sourcePath OSS 上的文件路径
     * @return string|null 转换后的 PDF 路径，失败返回 null
     */
    public static function convertToPdf(string $sourcePath): ?string
    {
        if (!self::isLibreOfficeAvailable()) {
            Log::warning('LibreOffice not available, skipping conversion', ['file' => $sourcePath]);
            return null;
        }

        $disk = Storage::disk('oss');
        
        if (!$disk->exists($sourcePath)) {
            Log::error('Source file not found for conversion', ['file' => $sourcePath]);
            return null;
        }

        // 生成 PDF 路径
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $pdfPath = str_replace('.' . $ext, '.pdf', $sourcePath);

        // 如果已存在转换后的 PDF，直接返回
        if ($disk->exists($pdfPath)) {
            return $pdfPath;
        }

        // 下载文件到本地临时目录
        $tempDir = storage_path('app/temp/office_convert');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $localSourcePath = $tempDir . '/' . basename($sourcePath);
        file_put_contents($localSourcePath, $disk->get($sourcePath));

        // 创建临时 LibreOffice 配置目录
        $profileDir = $tempDir . '/libreoffice_profile';
        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }

        // 设置字体替换配置
        $registryFile = $profileDir . '/user/registrymodifications.xcu';
        if (!file_exists($registryFile)) {
            $registryDir = $profileDir . '/user';
            if (!is_dir($registryDir)) {
                mkdir($registryDir, 0755, true);
            }
            file_put_contents($registryFile, self::getRegistryConfig());
        }

        // 执行转换
        $escapedSource = escapeshellarg($localSourcePath);
        $escapedOutputDir = escapeshellarg($tempDir);
        $encodedProfileDir = str_replace(' ', '%20', $profileDir);
        
        $homeDir = $tempDir;
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
            self::cleanupTempFiles($localSourcePath);
            return null;
        }

        // 查找转换后的 PDF 文件
        $localPdfPath = $tempDir . '/' . $baseName . '.pdf';
        if (!file_exists($localPdfPath)) {
            Log::error('Converted PDF not found after conversion', [
                'file' => $sourcePath,
                'expected' => $localPdfPath,
            ]);
            self::cleanupTempFiles($localSourcePath);
            return null;
        }

        // 上传 PDF 到 OSS
        $pdfContent = file_get_contents($localPdfPath);
        $disk->put($pdfPath, $pdfContent);

        // 删除原文件
        $disk->delete($sourcePath);

        // 清理本地临时文件
        self::cleanupTempFiles($localSourcePath, $localPdfPath);

        Log::info('File converted successfully', [
            'source' => $sourcePath,
            'pdf' => $pdfPath,
        ]);

        return $pdfPath;
    }

    /**
     * 获取文件的预览路径（如果存在转换后的 PDF）
     *
     * @param string $filePath OSS 上的文件路径
     * @return string 预览用的路径（可能是转换后的 PDF 或原文件）
     */
    public static function getPreviewPath(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Office 文件返回转换后的 PDF 路径
        if (in_array($ext, self::$convertibleExtensions)) {
            $baseName = pathinfo($filePath, PATHINFO_FILENAME);
            $pdfPath = str_replace('.' . $ext, '.pdf', $filePath);

            if (Storage::disk('oss')->exists($pdfPath)) {
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
     * 清理临时文件
     */
    protected static function cleanupTempFiles(string ...$files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
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
