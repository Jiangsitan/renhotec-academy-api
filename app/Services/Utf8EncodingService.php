<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

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

        // 2. 如果已经是有效 UTF-8，检查是否是双重编码
        if (mb_check_encoding($content, 'UTF-8')) {
            if (self::isDoubleEncodedUtf8($content)) {
                return 'UTF-8_DOUBLE';
            }
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
     * 连接字符集已设为 utf8mb4，双重编码不再可能发生
     * 此方法仅验证有效性，无效时清理非法字节
     */
    public static function ensureUtf8(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // 已经是有效 UTF-8，直接返回
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // 非法字节，清理后返回
        return self::sanitize($content);
    }

    /**
     * 校验字符串是否为有效 UTF-8
     */
    public static function isValidUtf8(string $content): bool
    {
        return mb_check_encoding($content, 'UTF-8');
    }


    /**
     * 检测字符串是否是双重编码的 UTF-8
     * 典型场景: GBK 字节被错误解读为 ISO-8859-1 后再编码为 UTF-8
     */
    public static function isDoubleEncodedUtf8(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // 步骤 1: UTF-8 → ISO-8859-1（还原第二层编码，得到原始字节）
        $rawBytes = @mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
        if ($rawBytes === false || strlen($rawBytes) === 0) {
            return false;
        }

        // 步骤 2: 检查还原后的字节是否是合法的 GBK 编码
        $gbkCheck = @mb_convert_encoding($rawBytes, 'UTF-8', 'GBK');
        if ($gbkCheck === false) {
            return false;
        }

        // 步骤 3: 如果转换成功且包含中文字符（非纯 ASCII），则认为是双重编码
        // 且还原后的 UTF-8 应该和原始内容不同（说明确实发生了编码错误）
        if ($gbkCheck === $content) {
            return false; // 相同，不是双重编码
        }

        // 包含中文字符才认为是双重编码（避免误判纯英文数据）
        return preg_match('/[\x{4e00}-\x{9fff}]/u', $gbkCheck) === 1;
    }

    /**
     * 解码双重编码的 UTF-8 字符串
     * 某些系统会将已有的 UTF-8 字符串再次按 Latin-1/ISO-8859-1 编码为 UTF-8
     * 导致双重编码。此方法尝试解码这种双重编码。
     */
    public static function decodeDoubleUtf8(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        if (!self::isDoubleEncodedUtf8($content)) {
            return $content;
        }

        // 步骤 1: UTF-8 → ISO-8859-1（还原第二层编码，得到原始 GBK 字节）
        $rawBytes = @mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
        if ($rawBytes === false || strlen($rawBytes) === 0) {
            return $content;
        }

        // 步骤 2: GBK → UTF-8（还原第一层编码，得到正确的中文文本）
        $decoded = @mb_convert_encoding($rawBytes, 'UTF-8', 'GBK');
        if ($decoded === false || !mb_check_encoding($decoded, 'UTF-8')) {
            return $content;
        }

        return $decoded;
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
     * 失败时返回空数组 JSON "[]" 而非 false，避免调用方未检查返回值导致数据损坏
     */
    public static function safeJsonEncode(mixed $value, int $flags = JSON_UNESCAPED_UNICODE): string
    {
        // 确保数组中的所有字符串值都是有效 UTF-8
        if (is_array($value)) {
            $value = self::sanitizeArray($value);
        } elseif (is_string($value)) {
            $value = self::ensureUtf8($value);
        }

        $result = json_encode($value, $flags);

        if ($result === false) {
            $byteDetails = [];
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_string($v)) {
                        $byteDetails[$k] = [
                            'value' => $v,
                            'hex' => bin2hex($v),
                            'bytes' => strlen($v),
                            'utf8' => mb_check_encoding($v, 'UTF-8'),
                            'codepoints' => self::getCodepoints($v),
                        ];
                    } else {
                        $byteDetails[$k] = ['value' => $v, 'type' => gettype($v)];
                    }
                }
            }
            \Log::error('safeJsonEncode failed', [
                'error' => json_last_error_msg(),
                'error_code' => json_last_error(),
                'value_type' => gettype($value),
                'value_count' => is_array($value) ? count($value) : null,
                'byte_details' => $byteDetails,
            ]);
            return is_array($value) ? '[]' : '{}';
        }

        return $result;
    }

    /**
     * 获取字符串中每个字符的 Unicode 码点
     */
    private static function getCodepoints(string $str): array
    {
        $codepoints = [];
        $len = strlen($str);
        $i = 0;
        while ($i < $len) {
            $byte = ord($str[$i]);
            if ($byte < 0x80) {
                $codepoints[] = sprintf('U+%04X', $byte);
                $i += 1;
            } elseif ($byte < 0xE0) {
                $codepoints[] = sprintf('U+%04X', (($byte & 0x1F) << 6) | (ord($str[$i + 1]) & 0x3F));
                $i += 2;
            } elseif ($byte < 0xF0) {
                $codepoints[] = sprintf('U+%04X', (($byte & 0x0F) << 12) | ((ord($str[$i + 1]) & 0x3F) << 6) | (ord($str[$i + 2]) & 0x3F));
                $i += 3;
            } else {
                $cp = (($byte & 0x07) << 18) | ((ord($str[$i + 1]) & 0x3F) << 12) | ((ord($str[$i + 2]) & 0x3F) << 6) | (ord($str[$i + 3]) & 0x3F);
                $codepoints[] = sprintf('U+%04X', $cp);
                $i += 4;
            }
        }
        return $codepoints;
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
