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
        $disk = Storage::disk('oss');

        // 检查文件是否存在
        if (!$disk->exists($path)) {
            return response()->json(['message' => '文件不存在'], 404);
        }

        // 对于 PPT/PPTX，返回转换后的 PDF
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['ppt', 'pptx'])) {
            $previewPath = FileConvertService::getPreviewPath($path);
            if ($previewPath !== $path && $disk->exists($previewPath)) {
                $path = $previewPath;
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
     * 处理 Range 请求
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

        return new StreamedResponse(function () use ($disk, $path, $start, $length) {
            $content = $disk->get($path);
            echo substr($content, $start, $length);
        }, 206, array_merge($headers, [
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $length,
        ]));
    }
}
