<?php

namespace App\Traits;

use App\Services\Utf8EncodingService;

/**
 * 自动将请求中的文本字段转换为有效 UTF-8 编码
 * 用于数据库存储前的编码规范化
 */
trait EnsuresUtf8
{
    /**
     * 清理请求中的文本字段，确保 UTF-8 编码
     */
    protected function ensureUtf8Fields(array $data, array $textFields = []): array
    {
        if (empty($textFields)) {
            // 自动检测所有字符串值
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = Utf8EncodingService::ensureUtf8($value);
                }
            }
        } else {
            // 仅清理指定的文本字段
            foreach ($textFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = Utf8EncodingService::ensureUtf8($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * 清理嵌套数组中的文本字段
     */
    protected function ensureUtf8Nested(array $data, array $textFieldMap = []): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // 如果指定了字段映射，检查是否需要清理
                if (!empty($textFieldMap[$key])) {
                    $data[$key] = Utf8EncodingService::ensureUtf8($value);
                } elseif (empty($textFieldMap)) {
                    // 未指定字段映射，自动清理所有字符串
                    $data[$key] = Utf8EncodingService::ensureUtf8($value);
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->ensureUtf8Nested($value, $textFieldMap[$key] ?? []);
            }
        }

        return $data;
    }
}
