<?php

namespace App\Http\Middleware;

use App\Services\Utf8EncodingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateUtf8Request
{
    public function handle(Request $request, Closure $next): Response
    {
        // 仅校验 application/json 请求体
        if ($request->isJson() && $request->getContent() !== false && strlen($request->getContent()) > 0) {
            $content = $request->getContent();

            if (!Utf8EncodingService::isValidUtf8($content)) {
                // 尝试自动转换编码
                $converted = Utf8EncodingService::ensureUtf8($content);

                if (Utf8EncodingService::isValidUtf8($converted)) {
                    // 替换请求内容
                    $request->merge(json_decode($converted, true) ?? []);
                } else {
                    return response()->json([
                        'message' => '请求内容包含无效字符，请使用 UTF-8 编码',
                    ], 422);
                }
            }
        }

        return $next($request);
    }
}
