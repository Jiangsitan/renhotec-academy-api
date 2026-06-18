<?php

namespace App\Helpers;

class OssHelper
{
    /**
     * 获取 OSS 路径前缀
     */
    public static function getPrefix(): string
    {
        return env('OSS_PREFIX', 'academy/dev');
    }

    /**
     * 生成 OSS 完整路径
     */
    public static function path(string $type, string $fileName): string
    {
        $prefix = self::getPrefix();
        $typeDir = self::getTypeDirectory($type);

        // 简化路径：academy/dev/documents/202606/xxx.pdf
        return $prefix . '/' . $typeDir . '/' . date('Ym') . '/' . $fileName;
    }

    /**
     * 根据文件类型获取目录名
     */
    public static function getTypeDirectory(string $type): string
    {
        return match($type) {
            'video' => 'videos',
            'document' => 'documents',
            'image' => 'images',
            'attachment' => 'attachments',
            'cover' => 'covers',
            'logo' => 'logos',
            default => 'others'
        };
    }

    /**
     * 获取 OSS 完整 URL
     */
    public static function url(string $path): string
    {
        $domain = env('OSS_CDN_DOMAIN', 'rh-wh.oss-cn-shanghai.aliyuncs.com');
        $ssl = env('OSS_SSL', true);
        $protocol = $ssl ? 'https' : 'http';
        return $protocol . '://' . $domain . '/' . $path;
    }

    /**
     * 获取 OSS 客户端实例
     */
    public static function getClient(): \OSS\OssClient
    {
        $client = new \OSS\OssClient(
            env('OSS_ACCESS_KEY_ID'),
            env('OSS_ACCESS_KEY_SECRET'),
            env('OSS_ENDPOINT')
        );
        $client->setTimeout(300); // 5 分钟超时
        $client->setConnectTimeout(30); // 30 秒连接超时
        return $client;
    }

    /**
     * 获取 Bucket 名称
     */
    public static function getBucket(): string
    {
        return env('OSS_BUCKET', 'rh-wh');
    }
}
