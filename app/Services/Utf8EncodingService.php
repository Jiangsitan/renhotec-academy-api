<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class Utf8EncodingService
{
    /**
     * 检测字符串编码
     */
    public static function detectEncoding(string $content): string
    {
        if (empty($content)) {
            return 'UTF-8';
        }

        // 1. 检查 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return 'UTF-8';
        }
        if (str_starts_with($content, "\xFF\xFE")) {
            return 'UTF-16LE';
        }
        if (str_starts_with($content, "\xFE\xFF")) {
            return 'UTF-16BE';
        }

        // 2. 如果已经是有效 UTF-8，直接返回
        if (mb_check_encoding($content, 'UTF-8')) {
            return 'UTF-8';
        }

        // 3. 依次尝试常见编码
        $encodings = ['GBK', 'GB2312', 'BIG5', 'ISO-8859-1', 'Windows-1252'];

        foreach ($encodings as $encoding) {
            $converted = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $encoding;
            }
        }

        return 'UNKNOWN';
    }

    /**
     * 确保字符串为有效 UTF-8 编码
     */
    public static function ensureUtf8(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // 已经是有效 UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // 检测源编码并转换
        $sourceEncoding = self::detectEncoding($content);

        if ($sourceEncoding === 'UTF-8') {
            return $content;
        }

        if ($sourceEncoding === 'UNKNOWN') {
            // 无法识别编码，尝试清理非法字节
            Log::warning('[Utf8EncodingService] 无法识别编码，尝试清理非法字节', [
                'content_length' => strlen($content),
                'sample' => substr($content, 0, 100),
            ]);
            return self::sanitize($content);
        }

        $converted = mb_convert_encoding($content, 'UTF-8', $sourceEncoding);

        if ($converted === false || !mb_check_encoding($converted, 'UTF-8')) {
            Log::warning('[Utf8EncodingService] 编码转换失败', [
                'source_encoding' => $sourceEncoding,
                'content_length' => strlen($content),
            ]);
            return self::sanitize($content);
        }

        return $converted;
    }

    /**
     * 校验字符串是否为有效 UTF-8
     */
    public static function isValidUtf8(string $content): bool
    {
        return mb_check_encoding($content, 'UTF-8');
    }

    /**
     * 清理非法 UTF-8 字节
     * 移除或替换无效的 UTF-8 序列
     */
    public static function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return $value;
        }

        // 如果已经是有效 UTF-8，直接返回
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // 尝试用 iconv 转换，替换非法字符
        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // 最后手段：用正则移除非法字节
        $sanitized = preg_replace('/[\x80-\x9F\xC0-\xFF]{2,}/', '', $value);
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $sanitized);

        return $sanitized ?: '';
    }

    /**
     * 为 CSV/文本内容添加 UTF-8 BOM 头
     */
    public static function addBom(string $content): string
    {
        // 只有非空内容才添加 BOM
        if (empty($content)) {
            return $content;
        }

        // 避免重复添加 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return $content;
        }

        return "\xEF\xBB\xBF" . $content;
    }

    /**
     * 从上传的文本文件中检测编码并转换为 UTF-8
     */
    public static function detectAndConvertFile(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new \RuntimeException("无法读取文件内容: {$file->getClientOriginalName()}");
        }

        return self::ensureUtf8($content);
    }

    /**
     * 对 JSON 编码进行安全包装
     * 确保输入为有效 UTF-8 后再编码
     */
    public static function safeJsonEncode(mixed $value, int $flags = JSON_UNESCAPED_UNICODE): string|false
    {
        // 确保数组中的所有字符串值都是有效 UTF-8
        if (is_array($value)) {
            $value = self::sanitizeArray($value);
        } elseif (is_string($value)) {
            $value = self::ensureUtf8($value);
        }

        return json_encode($value, $flags);
    }

    /**
     * 递归清理数组中的字符串值为有效 UTF-8
     */
    public static function sanitizeArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = self::ensureUtf8($value);
            } elseif (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
