<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\OssHelper;
use App\Jobs\ProcessFileConversion;
use App\Jobs\ProcessVideoConversion;
use App\Models\Course;
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
            'file' => 'required|file|max:524288', // 最大 500MB
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
        $path = OssHelper::path($type, $fileName);

        // 上传到 OSS
        $fileContent = file_get_contents($file->getRealPath());
        Storage::disk('oss')->put($path, $fileContent);

        // 如果是图片，自动转换为 WEBP（同步，耗时较短）
        if (str_starts_with($file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/webp') {
            $webpPath = $this->convertToWebp($path);
            if ($webpPath) {
                $path = $webpPath;
            }
        }

        // 如果是视频，触发异步转换为 WebM
        if (str_starts_with($file->getMimeType(), 'video/') && $file->getMimeType() !== 'video/webm') {
            ProcessVideoConversion::dispatch($path, $file->getClientOriginalName());
        }

        // 如果是 PPT/PPTX，异步转换（避免同步阻塞导致 504 超时）
        $contentType = 'pdf';
        $images = null;
        $needsConversion = FileConvertService::needsConversion($file->getClientOriginalName());

        if ($needsConversion) {
            // 异步处理转换，立即返回
            ProcessFileConversion::dispatch($path, $file->getClientOriginalName());
            // content_type 和 images 设为 null，表示转换中
            $contentType = null;
            $images = null;
        }

        return response()->json([
            'data' => [
                'url' => OssHelper::url($path),
                'path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'content_type' => $contentType,
                'images' => $images,
                'converting' => $needsConversion,
            ],
        ]);
    }

    /**
     * 将图片转换为 WEBP 格式（支持 OSS）
     */
    private function convertToWebp(string $path): ?string
    {
        try {
            // 从 OSS 读取图片
            $imageContent = Storage::disk('oss')->get($path);
            if (!$imageContent) {
                return null;
            }

            // 使用 Intervention Image 转换
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($imageContent);
            $webpData = $image->toWebp(85)->toString();

            // 生成 WebP 路径
            $webpPath = preg_replace('/\.\w+$/', '.webp', $path);

            // 上传到 OSS
            Storage::disk('oss')->put($webpPath, $webpData);

            // 删除原文件
            Storage::disk('oss')->delete($path);

            return $webpPath;
        } catch (\Exception $e) {
            \Log::error('WebP 转换失败: ' . $e->getMessage());
            return null;
        }
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
     * 合并分片并完成上传（异步处理）
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
        $type = $meta['type'] ?? 'document';
        $finalPath = OssHelper::path($type, $fileName);

        // 异步处理合并和上传
        \App\Jobs\MergeUploadChunks::dispatch($uploadId, $finalPath, $meta);

        // 立即返回
        return response()->json([
            'data' => [
                'path' => $finalPath,
                'file_name' => $meta['file_name'],
                'file_size' => $meta['file_size'],
                'status' => 'processing',
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

    /**
     * 查询文件转换状态
     */
    public function conversionStatus(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // 查找包含此路径的课程（可能是原路径或转换后的路径）
        $course = Course::where('content_url', $path)
            ->orWhere('content_url', 'like', str_replace('.', '_page_%.', $path))
            ->first();

        if (!$course) {
            return response()->json([
                'data' => [
                    'converted' => false,
                    'content_type' => null,
                    'images' => null,
                ],
            ]);
        }

        $converted = !is_null($course->content_type) && $course->content_url !== $path;

        return response()->json([
            'data' => [
                'converted' => $converted,
                'content_type' => $course->content_type,
                'images' => $course->images,
                'content_url' => $course->content_url,
            ],
        ]);
    }

    /**
     * 取消分片上传，清理已上传的分片
     */
    public function cancelUpload(string $uploadId): JsonResponse
    {
        $chunkDir = "temp/uploads/{$uploadId}";
        
        if (Storage::disk('local')->exists($chunkDir)) {
            Storage::disk('local')->deleteDirectory($chunkDir);
        }

        return response()->json(['message' => '已取消']);
    }
}
