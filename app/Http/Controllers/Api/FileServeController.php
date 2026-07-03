<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\OssHelper;
use App\Services\FileConvertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileServeController extends Controller
{
    /**
     * 认证文件预览服务
     * Content-Disposition: inline（预览，非下载）
     */
    public function preview(Request $request, string $path): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $disk = Storage::disk('oss');

        // 检查文件是否存在
        if (!$disk->exists($path)) {
            return response()->json(['message' => '文件不存在'], 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // 对于 Office 文件，返回转换后的 PDF（PPT 除外，PPT 使用图片序列预览）
        // 只检查 PDF 是否已存在，不触发同步转换（避免 HTTP 超时）
        $officeExtensions = ['docx', 'doc', 'xlsx', 'xls', 'odt', 'ods', 'odp'];
        if (in_array($ext, $officeExtensions)) {
            $baseName = pathinfo($path, PATHINFO_FILENAME);
            $pdfPath = str_replace('.' . $ext, '.pdf', $path);
            if ($disk->exists($pdfPath)) {
                $path = $pdfPath;
                $ext = 'pdf';
            }
        }

        $mimeType = $disk->mimeType($path);
        $fileSize = $disk->size($path);

        // 设置响应头：inline 预览，不下载
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ];

        // 支持 Range 请求（大文件分段加载）
        $range = $request->header('Range');
        if ($range) {
            return $this->handleRangeRequest($disk, $path, $fileSize, $mimeType, $range, $headers);
        }

        return new StreamedResponse(function () use ($disk, $path) {
            echo $disk->get($path);
        }, 200, $headers);
    }

    /**
     * Office 文件预览（DOCX、PPTX、XLSX）
     * 检查 PDF 是否已转换，返回重定向或错误信息
     * 不触发同步转换（由异步 Job 处理）
     */
    public function previewOffice(Request $request, string $path): JsonResponse
    {
        $disk = Storage::disk('oss');

        if (!$disk->exists($path)) {
            return response()->json(['message' => '文件不存在'], 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $officeExtensions = ['docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'odt', 'ods', 'odp'];

        if (!in_array($ext, $officeExtensions)) {
            return response()->json(['message' => '不支持的文件类型'], 400);
        }

        // 检查是否有对应的 PDF 文件
        $pdfPath = str_replace('.' . $ext, '.pdf', $path);

        if ($disk->exists($pdfPath)) {
            // PDF 已存在，重定向到 PDF 预览
            return response()->json([
                'data' => [
                    'redirect' => route('files.preview', ['path' => $pdfPath]),
                ]
            ]);
        }

        // PDF 不存在，返回错误信息（异步 Job 正在转换中）
        return response()->json([
            'data' => [
                'error' => true,
                'message' => '文档转换中，请稍后再试',
            ]
        ]);
    }

    /**
     * 使用 Ghostscript 压缩 PDF（提高加载速度）
     */
    private function compressPdf(string $path): ?string
    {
        try {
            // 生成压缩后的路径
            $compressedPath = preg_replace('/\.pdf$/i', '_compressed.pdf', $path);

            // 检查是否已有压缩版本
            $disk = Storage::disk('oss');
            if ($disk->exists($compressedPath)) {
                return $compressedPath;
            }

            // 下载原始 PDF 到临时文件
            $tempInput = tempnam(sys_get_temp_dir(), 'pdf_input_') . '.pdf';
            $pdfContent = $disk->get($path);
            file_put_contents($tempInput, $pdfContent);

            // 生成输出路径
            $tempOutput = tempnam(sys_get_temp_dir(), 'pdf_output_') . '.pdf';

            // 使用 Ghostscript 压缩
            $command = sprintf(
                'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook '
                . '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellarg($tempOutput),
                escapeshellarg($tempInput)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempOutput) || filesize($tempOutput) === 0) {
                // 压缩失败，返回原文件
                @unlink($tempInput);
                return null;
            }

            // 上传压缩后的文件到 OSS
            $compressedContent = file_get_contents($tempOutput);
            $disk->put($compressedPath, $compressedContent);

            // 清理临时文件
            @unlink($tempInput);
            @unlink($tempOutput);

            return $compressedPath;
        } catch (\Exception $e) {
            \Log::error('PDF 压缩失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 处理 Range 请求（使用 OSS 原生 Range 支持，只下载需要的字节）
     */
    protected function handleRangeRequest(
        $disk,
        string $path,
        int $fileSize,
        string $mimeType,
        string $range,
        array $headers
    ): StreamedResponse {
        $ranges = explode('-', substr($range, 6));
        $start = intval($ranges[0]);
        $end = isset($ranges[1]) && $ranges[1] !== '' ? intval($ranges[1]) : $fileSize - 1;

        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            return new StreamedResponse(function () {}, 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }

        $length = $end - $start + 1;

        return new StreamedResponse(function () use ($path, $start, $end) {
            $client = \App\Helpers\OssHelper::getClient();
            $bucket = \App\Helpers\OssHelper::getBucket();
            $content = $client->getObject($bucket, $path, [
                \OSS\OssClient::OSS_RANGE => "bytes={$start}-{$end}",
            ]);
            echo $content;
        }, 206, array_merge($headers, [
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $length,
        ]));
    }
}
