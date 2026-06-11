<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Request $request, Attachment $attachment): JsonResponse
    {
        $user = $request->user();
        $attachment->increment('download_count');

        $publicDisk = Storage::disk('public');

        if (!$publicDisk->exists($attachment->file_path)) {
            return response()->json(['message' => '文件不存在'], 404);
        }

        $url = $publicDisk->url($attachment->file_path);

        return response()->json([
            'data' => [
                'download_url' => $url,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
            ],
        ]);
    }

    public function batchDownload(Request $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'attachment_ids' => 'required|array|min:1',
            'attachment_ids.*' => 'integer|exists:attachments,id',
            'series_name' => 'nullable|string|max:200',
        ]);

        $attachments = Attachment::whereIn('id', $validated['attachment_ids'])->get();

        if ($attachments->isEmpty()) {
            return response()->json(['message' => '没有找到附件'], 404);
        }

        $publicDisk = Storage::disk('public');

        // 创建临时目录
        $tempDir = storage_path('app/temp/batch_' . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // 创建 zip 文件
        $zipPath = $tempDir . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => '无法创建压缩文件'], 500);
        }

        $addedCount = 0;
        foreach ($attachments as $attachment) {
            $filePath = $publicDisk->path($attachment->file_path);
            if (file_exists($filePath)) {
                // 如果有重名文件，添加序号
                $fileName = $attachment->file_name;
                $zip->addFile($filePath, $fileName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            return response()->json(['message' => '没有找到可下载的文件'], 404);
        }

        // 更新下载计数
        Attachment::whereIn('id', $validated['attachment_ids'])->increment('download_count');

        // 生成文件名：系列名称_附件.zip
        $seriesName = $validated['series_name'] ?? '附件';
        $fileName = preg_replace('/[^\w\x{4e00}-\x{9fa5}]/u', '_', $seriesName);
        $fileName = preg_replace('/_+/', '_', $fileName);
        $fileName = trim($fileName, '_') . '_附件.zip';

        // 返回 zip 文件
        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $fileName, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
