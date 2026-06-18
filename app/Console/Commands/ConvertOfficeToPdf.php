<?php

namespace App\Console\Commands;

use App\Services\FileConvertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertOfficeToPdf extends Command
{
    protected $signature = 'file:office-to-pdf 
                            {--path= : 指定单个文件路径转换}
                            {--force : 强制重新转换已存在的 PDF}';

    protected $description = '批量转换 OSS 上的 Office 文件为 PDF';

    private const OFFICE_EXTENSIONS = ['docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'odt', 'ods', 'odp'];

    public function handle()
    {
        $path = $this->option('path');
        $force = $this->option('force');

        if ($path) {
            // 转换指定文件
            $this->convertFile($path, $force);
        } else {
            // 批量转换所有 Office 文件
            $this->convertAll($force);
        }

        return 0;
    }

    /**
     * 批量转换所有 Office 文件
     */
    protected function convertAll(bool $force = false): void
    {
        $disk = Storage::disk('oss');
        
        // 获取所有文件
        $this->info('正在扫描 OSS 文件...');
        $files = $disk->allFiles('');
        
        // 过滤 Office 文件
        $officeFiles = array_filter($files, function ($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, self::OFFICE_EXTENSIONS);
        });

        if (empty($officeFiles)) {
            $this->info('未找到需要转换的 Office 文件');
            return;
        }

        $this->info("找到 " . count($officeFiles) . " 个 Office 文件");
        
        if (!$force) {
            // 过滤掉已转换的文件
            $officeFiles = array_filter($officeFiles, function ($file) use ($disk) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $pdfPath = str_replace('.' . $ext, '.pdf', $file);
                return !$disk->exists($pdfPath);
            });
            
            $this->info("其中 " . count($officeFiles) . " 个文件需要转换");
        }

        if (empty($officeFiles)) {
            $this->info('所有 Office 文件已转换为 PDF');
            return;
        }

        $bar = $this->output->createProgressBar(count($officeFiles));
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($officeFiles as $file) {
            $bar->advance();
            
            try {
                $result = FileConvertService::convertToPdf($file);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->newLine();
                    $this->error("转换失败: {$file}");
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->newLine();
                $this->error("转换异常: {$file} - " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("转换完成！");
        $this->info("成功: {$successCount} 个");
        if ($failCount > 0) {
            $this->error("失败: {$failCount} 个");
        }
    }

    /**
     * 转换单个文件
     */
    protected function convertFile(string $path, bool $force = false): void
    {
        $disk = Storage::disk('oss');

        // 检查文件是否存在
        if (!$disk->exists($path)) {
            $this->error("文件不存在: {$path}");
            return;
        }

        // 检查是否为 Office 文件
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, self::OFFICE_EXTENSIONS)) {
            $this->warn("跳过非 Office 文件: {$path}");
            return;
        }

        // 检查是否已转换
        $pdfPath = str_replace('.' . $ext, '.pdf', $path);
        if (!$force && $disk->exists($pdfPath)) {
            $this->warn("PDF 已存在，跳过: {$path}");
            $this->info("PDF 路径: {$pdfPath}");
            return;
        }

        $this->info("开始转换: {$path}");
        
        try {
            $result = FileConvertService::convertToPdf($path);
            
            if ($result) {
                $this->info("转换成功！");
                $this->info("PDF 路径: {$result}");
                $this->info("原文件已删除");
            } else {
                $this->error("转换失败");
            }
        } catch (\Exception $e) {
            $this->error("转换异常: " . $e->getMessage());
        }
    }
}
