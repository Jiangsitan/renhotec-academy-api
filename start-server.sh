#!/bin/bash
# 启动 Laravel 后端服务器（带 PHP 配置）
cd "$(dirname "$0")"
export PHPRC="$(pwd)"

pkill -f "php artisan serve" 2>/dev/null
sleep 1

nohup php artisan serve --port 8000 > /tmp/laravel-serve.log 2>&1 &
echo "Backend started with PID: $!"

# 验证配置
sleep 2
echo "PHP upload_max_filesize: $(php -r 'echo ini_get("upload_max_filesize");')"
echo "PHP post_max_size: $(php -r 'echo ini_get("post_max_size");')"
