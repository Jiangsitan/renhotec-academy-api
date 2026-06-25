<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\OssHelper;
use App\Jobs\ProcessFileConversion;
use App\Jobs\ProcessVideoConversion;
use App\Models\Course;
use App\Services\DocumentCompressService;
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

        // 如果是 PPT/PPTX/PDF，异步压缩（避免同步阻塞导致 504 超时）
        $contentType = 'pdf';
        $images = null;
        $needsCompression = DocumentCompressService::needsCompression($file->getClientOriginalName());

        if ($needsCompression) {
            // 异步处理压缩，立即返回
            ProcessFileConversion::dispatch($path, $file->getClientOriginalName());
            // content_type 和 images 设为 null，表示压缩中
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
                'converting' => $needsCompression,
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
                'converting' => DocumentCompressService::needsCompression($meta['file_name']),
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

    // ==================== OSS 直传 ====================

    /**
     * 生成 OSS 签名 PUT URL（小文件直传）
     */
    public function presign(Request $request): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:1073741824', // 最大 1GB
            'type' => 'required|in:video,document,image,attachment,cover,logo',
        ]);

        $extension = pathinfo($request->input('file_name'), PATHINFO_EXTENSION);
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;
        $path = OssHelper::path($request->input('type'), $fileName);

        $client = OssHelper::getClient();
        $bucket = OssHelper::getBucket();

        // 生成签名 URL（PUT 方法，1小时有效）
        $signedUrl = $client->signUrl($bucket, $path, 3600, 'PUT');

        return response()->json([
            'data' => [
                'upload_url' => $signedUrl,
                'oss_path' => $path,
                'file_name' => $request->input('file_name'),
                'file_size' => $request->input('file_size'),
            ],
        ]);
    }

    /**
     * 初始化 Multipart Upload（大文件分片直传）
     */
    public function multipartInit(Request $request): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:10737418240', // 最大 10GB
            'type' => 'required|in:video,document',
        ]);

        $extension = pathinfo($request->input('file_name'), PATHINFO_EXTENSION);
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;
        $path = OssHelper::path($request->input('type'), $fileName);

        $client = OssHelper::getClient();
        $bucket = OssHelper::getBucket();

        // 初始化 Multipart Upload
        $uploadId = $client->initiateMultipartUpload($bucket, $path);

        return response()->json([
            'data' => [
                'upload_id' => $uploadId,
                'oss_path' => $path,
                'file_name' => $request->input('file_name'),
                'file_size' => $request->input('file_size'),
            ],
        ]);
    }

    /**
     * 为 Multipart Upload 的单个分片生成签名 URL
     */
    public function multipartSign(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'oss_path' => 'required|string',
            'part_number' => 'required|integer|min:1|max:10000',
        ]);

        $accessKeyId = config('filesystems.disks.oss.access_key_id');
        $accessKeySecret = config('filesystems.disks.oss.access_key_secret');
        $bucket = OssHelper::getBucket();
        $endpoint = config('filesystems.disks.oss.endpoint');
        $path = $request->input('oss_path');
        $uploadId = $request->input('upload_id');
        $partNumber = $request->input('part_number');
        $expires = time() + 3600;

        // 手动构造签名（OSS Signature V1，presigned PUT URL 不含 Content-Type）
        $resource = "/{$bucket}/{$path}?partNumber={$partNumber}&uploadId={$uploadId}";
        $stringToSign = "PUT\n\n\n{$expires}\n{$resource}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret, true));

        $signedUrl = sprintf(
            'https://%s.%s/%s?partNumber=%d&uploadId=%s&Expires=%d&OSSAccessKeyId=%s&Signature=%s',
            $bucket,
            $endpoint,
            $path,
            $partNumber,
            $uploadId,
            $expires,
            $accessKeyId,
            urlencode($signature)
        );

        return response()->json([
            'data' => [
                'signed_url' => $signedUrl,
                'part_number' => $request->input('part_number'),
            ],
        ]);
    }

    /**
     * 完成 Multipart Upload（OSS 云端合并）
     */
    public function multipartComplete(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
            'oss_path' => 'required|string',
            'parts' => 'required|array|min:1',
            'parts.*.part_number' => 'required|integer',
            'parts.*.etag' => 'required|string',
        ]);

        $client = OssHelper::getClient();
        $bucket = OssHelper::getBucket();

        $parts = collect($request->input('parts'))
            ->sortBy('part_number')
            ->map(fn($p) => ['PartNumber' => $p['part_number'], 'ETag' => $p['etag']])
            ->values()
            ->toArray();

        $client->completeMultipartUpload(
            $bucket,
            $request->input('oss_path'),
            $request->input('upload_id'),
            $parts
        );

        return response()->json([
            'data' => [
                'oss_path' => $request->input('oss_path'),
                'status' => 'completed',
            ],
        ]);
    }

    /**
     * OSS 上传完成通知（触发后处理：WebP/PDF/WebM 转换）
     */
    public function ossUploadComplete(Request $request): JsonResponse
    {
        $request->validate([
            'oss_path' => 'required|string',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'type' => 'required|in:video,document,image,attachment,cover,logo',
        ]);

        $path = $request->input('oss_path');
        $fileName = $request->input('file_name');
        $type = $request->input('type');

        // 验证文件是否存在于 OSS
        if (!Storage::disk('oss')->exists($path)) {
            return response()->json(['message' => '文件不存在于 OSS'], 422);
        }

        $mimeType = Storage::disk('oss')->mimeType($path);

        // 图片转 WebP（同步）
        if (str_starts_with($mimeType ?? '', 'image/') && $mimeType !== 'image/webp') {
            $webpPath = $this->convertToWebp($path);
            if ($webpPath) {
                $path = $webpPath;
            }
        }

        // 视频转 WebM（异步）
        if (str_starts_with($mimeType ?? '', 'video/') && $mimeType !== 'video/webm') {
            ProcessVideoConversion::dispatch($path, $fileName);
        }

        // 文档压缩（异步）
        $contentType = 'pdf';
        $images = null;
        $needsCompression = DocumentCompressService::needsCompression($fileName);

        if ($needsCompression) {
            ProcessFileConversion::dispatch($path, $fileName);
            $contentType = null;
            $images = null;
        }

        return response()->json([
            'data' => [
                'url' => OssHelper::url($path),
                'path' => $path,
                'file_name' => $fileName,
                'file_size' => $request->input('file_size'),
                'mime_type' => $mimeType,
                'content_type' => $contentType,
                'images' => $images,
                'converting' => $needsCompression,
            ],
        ]);
    }
}
