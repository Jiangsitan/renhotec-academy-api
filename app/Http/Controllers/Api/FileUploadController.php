<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileConvertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    // 允许的视频 MIME 类型
    private array $videoMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

    // 允许的文档 MIME 类型
    private array $documentMimes = [
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * 普通文件上传（适用于文档/小文件）
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 最大 10MB
            'type' => 'required|in:video,document',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        // 验证文件类型
        if ($type === 'video' && !in_array($file->getMimeType(), $this->videoMimes)) {
            return response()->json(['message' => '不支持的视频格式，请上传 MP4 格式'], 422);
        }

        if ($type === 'document' && !in_array($file->getMimeType(), $this->documentMimes)) {
            return response()->json(['message' => '不支持的文档格式'], 422);
        }

        // 生成存储路径
        $extension = $file->getClientOriginalExtension();
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;
        $path = $file->storeAs('courses/' . date('Y/m'), $fileName, 'public');

        // 如果是图片，自动转换为 WEBP
        if (str_starts_with($file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/webp') {
            $webpPath = $this->convertToWebp($path);
            if ($webpPath) {
                $path = $webpPath;
            }
        }

        // 如果是 PPT/PPTX，自动转换为 PDF
        $previewPath = null;
        if (FileConvertService::needsConversion($file->getClientOriginalName())) {
            $previewPath = FileConvertService::convertToPdf($path);
        }

        return response()->json([
            'data' => [
                'url' => '/storage/' . $path,
                'path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'preview_path' => $previewPath,
                'preview_url' => $previewPath ? '/storage/' . $previewPath : null,
            ],
        ]);
    }

    /**
     * 将图片转换为 WEBP 格式
     */
    private function convertToWebp(string $path): ?string
    {
        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) {
            return null;
        }

        $image = imagecreatefromstring(file_get_contents($fullPath));
        if (!$image) {
            return null;
        }

        $webpPath = preg_replace('/\.\w+$/', '.webp', $path);
        $fullWebpPath = Storage::disk('public')->path($webpPath);

        // 确保目录存在
        $dir = dirname($fullWebpPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $success = imagewebp($image, $fullWebpPath, 85);
        imagedestroy($image);

        if ($success && file_exists($fullWebpPath)) {
            // 删除原文件
            unlink($fullPath);
            return $webpPath;
        }

        return null;
    }

    /**
     * 分片上传初始化
     */
    public function uploadInit(Request $request): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string',
            'file_size' => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1',
            'type' => 'required|in:video,document',
        ]);

        $uploadId = Str::uuid()->toString();
        $chunkDir = "temp/uploads/{$uploadId}";

        Storage::disk('local')->makeDirectory($chunkDir);

        // 保存上传元数据
        Storage::disk('local')->put("{$chunkDir}/meta.json", json_encode([
            'file_name' => $request->input('file_name'),
            'file_size' => $request->input('file_size'),
            'total_chunks' => $request->input('total_chunks'),
            'type' => $request->input('type'),
            'uploaded_chunks' => [],
            'created_at' => now()->toISOString(),
        ]));

        return response()->json([
            'data' => [
                'upload_id' => $uploadId,
            ],
        ]);
    }

    /**
     * 上传单个分片
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->input('upload_id');
        $chunkIndex = $request->input('chunk_index');
        $chunkDir = "temp/uploads/{$uploadId}";
        $metaPath = "{$chunkDir}/meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json(['message' => '上传会话不存在'], 404);
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);

        // 保存分片
        $chunkFile = $request->file('chunk');
        $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}";
        $chunkFile->storeAs($chunkDir, "chunk_{$chunkIndex}", 'local');

        // 更新已上传分片列表
        if (!in_array($chunkIndex, $meta['uploaded_chunks'])) {
            $meta['uploaded_chunks'][] = $chunkIndex;
            sort($meta['uploaded_chunks']);
            Storage::disk('local')->put($metaPath, json_encode($meta));
        }

        $uploadedCount = count($meta['uploaded_chunks']);
        $totalCount = $meta['total_chunks'];

        return response()->json([
            'data' => [
                'upload_id' => $uploadId,
                'chunk_index' => $chunkIndex,
                'uploaded' => $uploadedCount,
                'total' => $totalCount,
                'progress' => round(($uploadedCount / $totalCount) * 100, 1),
                'completed' => $uploadedCount >= $totalCount,
            ],
        ]);
    }

    /**
     * 合并分片并完成上传
     */
    public function uploadComplete(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->input('upload_id');
        $chunkDir = "temp/uploads/{$uploadId}";
        $metaPath = "{$chunkDir}/meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json(['message' => '上传会话不存在'], 404);
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);

        if (count($meta['uploaded_chunks']) < $meta['total_chunks']) {
            return response()->json([
                'message' => '分片未全部上传完成',
                'uploaded' => count($meta['uploaded_chunks']),
                'total' => $meta['total_chunks'],
            ], 422);
        }

        // 生成最终文件路径
        $extension = pathinfo($meta['file_name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;
        $finalDir = 'courses/' . date('Y/m');
        $finalPath = "{$finalDir}/{$fileName}";

        // 流式合并分片到临时文件，避免内存溢出
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
        $handle = fopen($tempFile, 'w');
        for ($i = 0; $i < $meta['total_chunks']; $i++) {
            $chunkPath = "{$chunkDir}/chunk_{$i}";
            if (Storage::disk('local')->exists($chunkPath)) {
                $chunkStream = Storage::disk('local')->readStream($chunkPath);
                stream_copy_to_stream($chunkStream, $handle);
                fclose($chunkStream);
            }
        }
        fclose($handle);

        // 将临时文件写入 public 磁盘
        $writeStream = fopen($tempFile, 'r');
        Storage::disk('public')->writeStream($finalPath, $writeStream);
        fclose($writeStream);
        unlink($tempFile);

        // 清理临时分片
        Storage::disk('local')->deleteDirectory($chunkDir);

        // 如果是 PPT/PPTX，自动转换为 PDF
        $previewPath = null;
        if (FileConvertService::needsConversion($meta['file_name'])) {
            $previewPath = FileConvertService::convertToPdf($finalPath);
        }

        return response()->json([
            'data' => [
                'url' => '/storage/' . $finalPath,
                'path' => $finalPath,
                'file_name' => $meta['file_name'],
                'file_size' => $meta['file_size'],
                'preview_path' => $previewPath,
                'preview_url' => $previewPath ? '/storage/' . $previewPath : null,
            ],
        ]);
    }

    /**
     * 查询上传进度
     */
    public function uploadStatus(Request $request, string $uploadId): JsonResponse
    {
        $metaPath = "temp/uploads/{$uploadId}/meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json(['message' => '上传会话不存在'], 404);
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);
        $uploadedCount = count($meta['uploaded_chunks']);

        return response()->json([
            'data' => [
                'upload_id' => $uploadId,
                'file_name' => $meta['file_name'],
                'file_size' => $meta['file_size'],
                'uploaded' => $uploadedCount,
                'total' => $meta['total_chunks'],
                'progress' => round(($uploadedCount / $meta['total_chunks']) * 100, 1),
                'completed' => $uploadedCount >= $meta['total_chunks'],
            ],
        ]);
    }
}
