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

        // 执行转换（使用更高质量的参数）
        $escapedSource = escapeshellarg($localSourcePath);
        $escapedOutputDir = escapeshellarg($tempDir);
        $encodedProfileDir = str_replace(' ', '%20', $profileDir);
        
        $homeDir = $tempDir;
        $command = "HOME=" . escapeshellarg($homeDir) . " SAL_USE_VCLPLUGIN=svp soffice '-env:UserInstallationURL=file://{$encodedProfileDir}' --headless --convert-to pdf:writer_pdf_Export --outdir {$escapedOutputDir} {$escapedSource} 2>&1";
        
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
        // $disk->delete($sourcePath); // Disabled: keep original for client-side fallback

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

    /**
     * 将 PPT 转换为 WebP 图片序列
     *
     * @param string $sourcePath OSS 上的文件路径
     * @return array|null 图片路径数组，失败返回 null
     */
    public static function pptToWebpImages(string $sourcePath): ?array
    {
        Log::info('开始转换 PPT 为 WebP 图片', ['source' => $sourcePath]);
        
        // 1. 使用 LibreOffice 将 PPT 转换为 PDF
        $pdfPath = self::convertToPdf($sourcePath);
        if (!$pdfPath) {
            Log::error('PPT 转 PDF 失败', ['source' => $sourcePath]);
            return null;
        }

        // 2. 使用 GD 库将 PDF 转换为 WebP 图片
        $images = self::pdfToWebpImages($pdfPath);
        if (!$images || empty($images)) {
            Log::error('PDF 转 WebP 失败', ['pdf' => $pdfPath]);
            return null;
        }

        // 3. 删除临时 PDF 文件
        Storage::disk('oss')->delete($pdfPath);

        Log::info('PPT 转 WebP 完成', [
            'source' => $sourcePath,
            'images' => $images,
            'count' => count($images),
        ]);

        return $images;
    }

    /**
     * 将 PDF 转换为 WebP 图片序列（使用 GD 库）
     *
     * @param string $pdfPath OSS 上的 PDF 文件路径
     * @return array|null 图片路径数组，失败返回 null
     */
    protected static function pdfToWebpImages(string $pdfPath): ?array
    {
        try {
            $disk = Storage::disk('oss');
            $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
            $pdfContent = $disk->get($pdfPath);
            file_put_contents($tempPdf, $pdfContent);

            $images = [];
            
            // 使用 Ghostscript 将 PDF 转换为 PNG
            $tempDir = sys_get_temp_dir();
            $command = "gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -sOutputFile={$tempDir}/page_%d.png {$tempPdf} 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // 查找生成的 PNG 文件
                $pngFiles = glob($tempDir . '/page_*.png');
                
                // 按页码数字排序（避免字符串排序导致 page_10 排在 page_2 前面）
                usort($pngFiles, function ($a, $b) {
                    preg_match('/page_(\d+)\.png/', basename($a), $matchesA);
                    preg_match('/page_(\d+)\.png/', basename($b), $matchesB);
                    return intval($matchesA[1] ?? 0) - intval($matchesB[1] ?? 0);
                });
                
                foreach ($pngFiles as $index => $pngFile) {
                    // 将 PNG 转换为 WebP
                    $image = imagecreatefrompng($pngFile);
                    ob_start();
                    imagewebp($image, null, 85);
                    $webpData = ob_get_clean();
                    imagedestroy($image);
                    
                    // 上传到 OSS
                    $imagePath = str_replace('.pdf', "_page_{$index}.webp", $pdfPath);
                    $disk->put($imagePath, $webpData);
                    $images[] = $imagePath;
                    
                    // 删除临时 PNG 文件
                    @unlink($pngFile);
                }
            }
            
            @unlink($tempPdf);
            return $images;
            
        } catch (\Exception $e) {
            \Log::error('PDF to WebP images conversion failed: ' . $e->getMessage());
            return null;
        }
    }
}
