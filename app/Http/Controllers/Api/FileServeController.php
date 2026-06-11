<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileConvertService;
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
        $publicDisk = Storage::disk('public');

        // 检查文件是否存在
        if (!$publicDisk->exists($path)) {
            return response()->json(['message' => '文件不存在'], 404);
        }

        // 对于 PPT/PPTX，返回转换后的 PDF
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['ppt', 'pptx'])) {
            $previewPath = FileConvertService::getPreviewPath($path);
            if ($previewPath !== $path && $publicDisk->exists($previewPath)) {
                $path = $previewPath;
            }
        }

        $filePath = $publicDisk->path($path);
        $mimeType = $publicDisk->mimeType($path);
        $fileSize = $publicDisk->size($path);

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
            return $this->handleRangeRequest($filePath, $fileSize, $mimeType, $range, $headers);
        }

        return new StreamedResponse(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    /**
     * 处理 Range 请求
     */
    protected function handleRangeRequest(
        string $filePath,
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

        return new StreamedResponse(function () use ($filePath, $start, $length) {
            $stream = fopen($filePath, 'rb');
            fseek($stream, $start);
            $remaining = $length;
            $bufferSize = 8192;

            while ($remaining > 0 && !feof($stream)) {
                $readSize = min($bufferSize, $remaining);
                echo fread($stream, $readSize);
                $remaining -= $readSize;
            }

            fclose($stream);
        }, 206, array_merge($headers, [
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $length,
        ]));
    }
}
